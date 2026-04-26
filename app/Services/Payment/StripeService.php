<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;

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
    public function createPaymentIntent(Model $payable, int $amountInCents, array $extraData = []): array
    {
        try {
            // Calculate fees
            $platformFee = Payment::calculatePlatformFee($amountInCents);
            $gatewayFee = Payment::calculateGatewayFee('stripe', $amountInCents);
            $ownerPayout = Payment::calculateOwnerPayout($amountInCents, $platformFee, $gatewayFee);

            $description = $payable instanceof Rental
                ? "Location de véhicule #{$payable->id}"
                : "Réservation propriété #{$payable->id}";

            $metadata = [
                'payable_type' => get_class($payable),
                'payable_id' => $payable->id,
                'platform_fee' => $platformFee,
                'gateway_fee' => $gatewayFee,
                'owner_payout' => $ownerPayout,
            ];

            if ($payable instanceof Rental) {
                $metadata['renter_id'] = $payable->renter_id;
                $metadata['vehicle_id'] = $payable->vehicle_id;
            }

            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => strtolower(config('payment.currency', 'eur')),
                'description' => $description,
                'metadata' => array_merge($metadata, $extraData['metadata'] ?? []),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            $userId = $payable instanceof Rental ? $payable->renter_id : $payable->guest_id;

            // Create payment record in database
            $payment = Payment::create([
                'payable_type' => get_class($payable),
                'payable_id' => $payable->id,
                'rental_id' => $payable instanceof Rental ? $payable->id : null,
                'user_id' => $userId,
                'payment_method' => 'stripe',
                'status' => 'pending',
                'amount' => $extraData['original_amount'] ?? $amountInCents,
                'platform_fee' => $platformFee,
                'gateway_fee' => $gatewayFee,
                'owner_payout' => $ownerPayout,
                'referral_credits_used' => ($extraData['referral_credits_used'] ?? 0) * 100,
                'final_amount' => $amountInCents,
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
            Log::error('Stripe PaymentIntent creation failed: '.$e->getMessage());

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

            if (! $payment) {
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

                // Update payable payment status
                $payable = $payment->payable;
                $payable->update([
                    'payment_status' => 'paid',
                    'status' => $payable instanceof \App\Models\Rental ? 'confirmed' : $payable->status,
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
            Log::error('Stripe payment confirmation failed: '.$e->getMessage());

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
            Log::error('Stripe payment cancellation failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process a refund
     */
    public function refund(Payment $payment, ?int $amountInCents = null): array
    {
        try {
            if (! $payment->stripe_charge_id) {
                return [
                    'success' => false,
                    'error' => 'No charge ID found for this payment',
                ];
            }

            // If no amount specified, refund full amount
            if ($amountInCents === null) {
                $amountInCents = $payment->amount - $payment->refunded_amount;
            }

            $refundMetadata = [
                'payment_id' => $payment->id,
                'payable_type' => $payment->payable_type,
                'payable_id' => $payment->payable_id,
            ];
            if ($payment->rental_id) {
                $refundMetadata['rental_id'] = $payment->rental_id;
            }

            // Create refund
            $refund = Refund::create([
                'charge' => $payment->stripe_charge_id,
                'amount' => $amountInCents,
                'reason' => 'requested_by_customer',
                'metadata' => $refundMetadata,
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

            // Update payable payment status if fully refunded
            if ($isFullRefund && $payment->payable) {
                $payment->payable->update([
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
            Log::error('Stripe refund failed: '.$e->getMessage());

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
            Log::error('Failed to retrieve payment details: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
