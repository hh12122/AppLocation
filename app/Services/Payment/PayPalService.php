<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;

class PayPalService
{
    private PayPalHttpClient $client;

    public function __construct()
    {
        $mode = config('payment.paypal.mode', 'sandbox');

        // Workaround for local SSL issues (cURL error 60/77)
        $caPath = base_path('cacert.pem');
        if (file_exists($caPath)) {
            ini_set('curl.cainfo', $caPath);
            ini_set('openssl.cafile', $caPath);
        }

        if ($mode === 'sandbox') {
            $environment = new SandboxEnvironment(
                config('payment.paypal.sandbox.client_id'),
                config('payment.paypal.sandbox.secret')
            );
        } else {
            $environment = new ProductionEnvironment(
                config('payment.paypal.live.client_id'),
                config('payment.paypal.live.secret')
            );
        }

        $this->client = new PayPalHttpClient($environment);
    }

    /**
     * Create a PayPal order for a payable entity
     */
    public function createOrder(Model $payable, int $amountInCents, array $extraData = []): array
    {
        try {
            // Calculate fees
            $platformFee = Payment::calculatePlatformFee($amountInCents);
            $gatewayFee = Payment::calculateGatewayFee('paypal', $amountInCents);
            $ownerPayout = Payment::calculateOwnerPayout($amountInCents, $platformFee, $gatewayFee);

            // Convert cents to euros
            $amountInEuros = number_format($amountInCents / 100, 2, '.', '');

            $payableType = $payable instanceof Rental ? 'rental' : 'booking';
            $description = $payable instanceof Rental
                ? "Location de véhicule #{$payable->id}"
                : "Réservation propriété #{$payable->id}";

            $itemName = $payable instanceof Rental
                ? "Location véhicule {$payable->vehicle->brand} {$payable->vehicle->model}"
                : 'Réservation propriété';

            $itemDescription = $payable instanceof Rental
                ? "Du {$payable->start_date->format('d/m/Y')} au {$payable->end_date->format('d/m/Y')}"
                : "Du {$payable->checkin_date->format('d/m/Y')} au {$payable->checkout_date->format('d/m/Y')}";

            $request = new OrdersCreateRequest;
            $request->prefer('return=representation');
            $request->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $payableType.'_'.$payable->id,
                    'description' => $description,
                    'custom_id' => (string) $payable->id,
                    'amount' => [
                        'currency_code' => config('payment.currency', 'EUR'),
                        'value' => $amountInEuros,
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => config('payment.currency', 'EUR'),
                                'value' => $amountInEuros,
                            ],
                        ],
                    ],
                    'items' => [[
                        'name' => $itemName,
                        'description' => $itemDescription,
                        'unit_amount' => [
                            'currency_code' => config('payment.currency', 'EUR'),
                            'value' => $amountInEuros,
                        ],
                        'quantity' => '1',
                        'category' => 'PHYSICAL_GOODS',
                    ]],
                ]],
                'application_context' => [
                    'brand_name' => config('app.name', 'CarLocation'),
                    'landing_page' => 'LOGIN',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('payments.paypal.success'),
                    'cancel_url' => route('payments.paypal.cancel'),
                ],
            ];

            $response = $this->client->execute($request);

            $userId = $payable instanceof Rental ? $payable->renter_id : $payable->guest_id;

            // Create payment record in database
            $payment = Payment::create([
                'payable_type' => get_class($payable),
                'payable_id' => $payable->id,
                'rental_id' => $payable instanceof Rental ? $payable->id : null,
                'user_id' => $userId,
                'payment_method' => 'paypal',
                'status' => 'pending',
                'amount' => $extraData['original_amount'] ?? $amountInCents,
                'platform_fee' => $platformFee,
                'gateway_fee' => $gatewayFee,
                'owner_payout' => $ownerPayout,
                'referral_credits_used' => ($extraData['referral_credits_used'] ?? 0) * 100,
                'final_amount' => $amountInCents,
                'currency' => config('payment.currency', 'EUR'),
                'paypal_order_id' => $response->result->id,
                'payment_details' => [
                    'order_status' => $response->result->status,
                    'links' => $response->result->links,
                ],
            ]);

            // Find the approval URL
            $approvalUrl = null;
            foreach ($response->result->links as $link) {
                if ($link->rel === 'approve') {
                    $approvalUrl = $link->href;
                    break;
                }
            }

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'order_id' => $response->result->id,
                'approval_url' => $approvalUrl,
            ];

        } catch (\Exception $e) {
            Log::error('PayPal order creation failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Capture a PayPal order after approval
     */
    public function captureOrder(string $orderId): array
    {
        try {
            $request = new OrdersCaptureRequest($orderId);
            $request->prefer('return=representation');

            $response = $this->client->execute($request);

            // Find and update payment record
            $payment = Payment::where('paypal_order_id', $orderId)->first();

            if (! $payment) {
                return [
                    'success' => false,
                    'error' => 'Payment record not found',
                ];
            }

            if ($response->result->status === 'COMPLETED') {
                $captureId = $response->result->purchase_units[0]->payments->captures[0]->id ?? null;

                $payment->update([
                    'status' => 'completed',
                    'paypal_capture_id' => $captureId,
                    'paid_at' => now(),
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'payer_email' => $response->result->payer->email_address ?? null,
                        'payer_name' => $response->result->payer->name->given_name ?? ''.' '.$response->result->payer->name->surname ?? '',
                        'capture_status' => $response->result->status,
                    ]),
                ]);

                // Update payable payment status
                $payable = $payment->payable;
                $payable->update([
                    'payment_status' => 'paid',
                    'status' => $payable instanceof Rental ? 'confirmed' : $payable->status,
                ]);

                return [
                    'success' => true,
                    'payment' => $payment,
                    'capture_id' => $captureId,
                ];
            }

            return [
                'success' => false,
                'error' => 'Payment not completed',
                'status' => $response->result->status,
            ];

        } catch (\Exception $e) {
            Log::error('PayPal order capture failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get order details
     */
    public function getOrderDetails(string $orderId): array
    {
        try {
            $request = new OrdersGetRequest($orderId);
            $response = $this->client->execute($request);

            return [
                'success' => true,
                'order' => $response->result,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to retrieve PayPal order details: '.$e->getMessage());

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
            if (! $payment->paypal_capture_id) {
                return [
                    'success' => false,
                    'error' => 'No capture ID found for this payment',
                ];
            }

            // If no amount specified, refund full amount
            if ($amountInCents === null) {
                $amountInCents = $payment->amount - $payment->refunded_amount;
            }

            // Convert cents to euros
            $amountInEuros = number_format($amountInCents / 100, 2, '.', '');

            $request = new CapturesRefundRequest($payment->paypal_capture_id);
            $request->body = [
                'amount' => [
                    'value' => $amountInEuros,
                    'currency_code' => config('payment.currency', 'EUR'),
                ],
                'note_to_payer' => 'Remboursement de votre location',
            ];

            $response = $this->client->execute($request);

            // Update payment record
            $newRefundedAmount = $payment->refunded_amount + $amountInCents;
            $isFullRefund = $newRefundedAmount >= $payment->amount;

            $payment->update([
                'status' => $isFullRefund ? 'refunded' : 'partially_refunded',
                'refunded_amount' => $newRefundedAmount,
                'paypal_refund_id' => $response->result->id,
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
                'refund_id' => $response->result->id,
                'amount_refunded' => $amountInCents,
                'is_full_refund' => $isFullRefund,
            ];

        } catch (\Exception $e) {
            Log::error('PayPal refund failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel a PayPal order
     */
    public function cancelOrder(string $orderId): array
    {
        try {
            // Update payment record
            $payment = Payment::where('paypal_order_id', $orderId)->first();

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

        } catch (\Exception $e) {
            Log::error('PayPal order cancellation failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
