<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Rental;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('payment.stripe.secret_key'));
        Stripe::setApiVersion(config('payment.stripe.api_version'));
    }

    /**
     * Create a payment intent for a rental
     */
    public function createPaymentIntent(Rental $rental, int $amountInCents): array
    {
        try {
            // Calculate fees
            $platformFee = Payment::calculatePlatformFee($amountInCents);
            $gatewayFee = Payment::calculateGatewayFee('stripe', $amountInCents);
            $ownerPayout = Payment::calculateOwnerPayout($amountInCents, $platformFee, $gatewayFee);

            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => strtolower(config('payment.currency', 'eur')),
                'description' => "Location de véhicule #{$rental->id}",
                'metadata' => [
                    'rental_id' => $rental->id,
                    'renter_id' => $rental->renter_id,
                    'vehicle_id' => $rental->vehicle_id,
                    'platform_fee' => $platformFee,
                    'gateway_fee' => $gatewayFee,
                    'owner_payout' => $ownerPayout,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            // Create payment record in database
            $payment = Payment::create([
                'rental_id' => $rental->id,
                'user_id' => $rental->renter_id,
                'payment_method' => 'stripe',
                'status' => 'pending',
                'amount' => $amountInCents,
                'platform_fee' => $platformFee,
                'gateway_fee' => $gatewayFee,
                'owner_payout' => $ownerPayout,
                'currency' => config('payment.currency', 'EUR'),
                'stripe_payment_intent_id' => $paymentIntent->id,
                'payment_details' => [
                    'client_secret' => $paymentIntent->client_secret,
                ],
            ]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe PaymentIntent creation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Confirm a payment after successful payment
     */
    public function confirmPayment(string $paymentIntentId): array
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            // Find and update payment record
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();
            
            if (!$payment) {
                return [
                    'success' => false,
                    'error' => 'Payment record not found',
                ];
            }

            if ($paymentIntent->status === 'succeeded') {
                $payment->update([
                    'status' => 'completed',
                    'stripe_charge_id' => $paymentIntent->latest_charge,
                    'paid_at' => now(),
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'payment_method' => $paymentIntent->payment_method,
                        'receipt_url' => $paymentIntent->charges->data[0]->receipt_url ?? null,
                    ]),
                ]);

                // Update rental payment status
                $payment->rental->update([
                    'payment_status' => 'paid',
                ]);

                return [
                    'success' => true,
                    'payment' => $payment,
                ];
            }

            return [
                'success' => false,
                'error' => 'Payment not completed',
                'status' => $paymentIntent->status,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe payment confirmation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel a payment intent
     */
    public function cancelPayment(string $paymentIntentId): array
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            $paymentIntent->cancel();

            // Update payment record
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();
            if ($payment) {
                $payment->update([
                    'status' => 'cancelled',
                    'failed_at' => now(),
                    'failure_reason' => 'Payment cancelled by user',
                ]);
            }

            return [
                'success' => true,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe payment cancellation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process a refund
     */
    public function refund(Payment $payment, int $amountInCents = null): array
    {
        try {
            if (!$payment->stripe_charge_id) {
                return [
                    'success' => false,
                    'error' => 'No charge ID found for this payment',
                ];
            }

            // If no amount specified, refund full amount
            if ($amountInCents === null) {
                $amountInCents = $payment->amount - $payment->refunded_amount;
            }

            // Create refund
            $refund = Refund::create([
                'charge' => $payment->stripe_charge_id,
                'amount' => $amountInCents,
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'rental_id' => $payment->rental_id,
                ],
            ]);

            // Update payment record
            $newRefundedAmount = $payment->refunded_amount + $amountInCents;
            $isFullRefund = $newRefundedAmount >= $payment->amount;

            $payment->update([
                'status' => $isFullRefund ? 'refunded' : 'partially_refunded',
                'refunded_amount' => $newRefundedAmount,
                'stripe_refund_id' => $refund->id,
                'refunded_at' => now(),
            ]);

            // Update rental payment status if fully refunded
            if ($isFullRefund) {
                $payment->rental->update([
                    'payment_status' => 'refunded',
                ]);
            }

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount_refunded' => $amountInCents,
                'is_full_refund' => $isFullRefund,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe refund failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment details from Stripe
     */
    public function getPaymentDetails(string $paymentIntentId): array
    {
        try {
            $paymentIntent = PaymentIntent::retrieve([
                'id' => $paymentIntentId,
                'expand' => ['payment_method', 'latest_charge'],
            ]);

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve payment details: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}