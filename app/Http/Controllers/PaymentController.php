<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PropertyBooking;
use App\Models\Rental;
use App\Notifications\BookingConfirmed;
use App\Notifications\NewBookingReceived;
use App\Services\Payment\PayPalService;
use App\Services\Payment\StripeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentController extends Controller
{
    private StripeService $stripeService;

    private PayPalService $paypalService;

    public function __construct(
        StripeService $stripeService,
        PayPalService $paypalService
    ) {
        $this->stripeService = $stripeService;
        $this->paypalService = $paypalService;
    }

    /**
     * Display payment history for authenticated user
     */
    public function index(Request $request)
    {
        $query = Payment::where('user_id', Auth::id())
            ->with(['rental.vehicle'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method') && $request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->paginate(10);

        // Calculate stats
        $stats = [
            'total_paid' => Payment::where('user_id', Auth::id())
                ->where('status', 'completed')
                ->sum('amount'),
            'total_refunded' => Payment::where('user_id', Auth::id())
                ->whereIn('status', ['refunded', 'partially_refunded'])
                ->sum('refunded_amount'),
            'total_payments' => Payment::where('user_id', Auth::id())->count(),
        ];

        return Inertia::render('Payment/Index', [
            'payments' => $payments,
            'stats' => $stats,
        ]);
    }

    /**
     * Show payment form for a rental
     */
    public function show(Rental $rental)
    {
        // Check if user can pay for this rental
        if ($rental->renter_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this payment.');
        }

        if ($rental->payment_status === 'paid') {
            return redirect()->route('payments.success')
                ->with('message', 'Cette location a déjà été payée.');
        }

        $user = Auth::user();

        return Inertia::render('Payment/Form', [
            'rental' => $rental->load(['vehicle.owner', 'vehicle.images', 'renter']),
            'availableCredits' => $user->getAvailableCredits(),
            'referralStats' => $user->getReferralStats(),
        ]);
    }

    /**
     * Show payment form for a property booking
     */
    public function showPropertyBooking(PropertyBooking $booking)
    {
        if ($booking->guest_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this payment.');
        }

        if ($booking->payment_status === 'paid') {
            return redirect()->route('property-bookings.show', $booking)
                ->with('message', 'Cette réservation a déjà été payée.');
        }

        $user = Auth::user();

        return Inertia::render('Payment/PropertyBookingForm', [
            'booking' => $booking->load(['property.owner', 'property.images', 'guest']),
            'availableCredits' => $user->getAvailableCredits(),
            'referralStats' => $user->getReferralStats(),
        ]);
    }

    /**
     * Create Stripe payment intent
     */
    public function createStripeIntent(Request $request)
    {
        $request->validate([
            'payable_type' => 'required|string|in:rental,property_booking',
            'payable_id' => 'required|integer',
            'amount' => 'required|integer|min:50',
            'referral_credits' => 'nullable|numeric|min:0',
        ]);

        try {
            $payable = $this->resolvePayable($request->payable_type, $request->payable_id);
            $user = Auth::user();

            if (! $payable || ! $this->verifyPayableOwnership($payable, $user)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé à ce paiement.',
                ], 403);
            }

            if ($payable->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'error' => 'Ce paiement a déjà été effectué.',
                ], 400);
            }

            $originalAmount = $request->amount;
            $referralCreditsUsed = 0;
            $finalAmount = $originalAmount;

            if ($request->referral_credits && $request->referral_credits > 0) {
                $creditsToUse = min($request->referral_credits, $user->getAvailableCredits());
                $creditsToUse = min($creditsToUse, $originalAmount / 100);

                if ($creditsToUse > 0 && $user->canUseReferralCredits($creditsToUse)) {
                    $referralCreditsUsed = $creditsToUse;
                    $finalAmount = max(50, $originalAmount - ($creditsToUse * 100));
                }
            }

            if ($finalAmount <= 0) {
                if ($user->useReferralCredits($referralCreditsUsed, $payable)) {
                    $payable->update([
                        'payment_status' => 'paid',
                        'status' => $payable instanceof Rental ? 'confirmed' : $payable->status,
                    ]);

                    $payment = Payment::create([
                        'payable_type' => get_class($payable),
                        'payable_id' => $payable->id,
                        'rental_id' => $payable instanceof Rental ? $payable->id : null,
                        'user_id' => $user->id,
                        'amount' => $originalAmount,
                        'referral_credits_used' => $referralCreditsUsed * 100,
                        'final_amount' => 0,
                        'payment_method' => 'referral_credits',
                        'status' => 'completed',
                        'paid_at' => now(),
                    ]);

                    return response()->json([
                        'success' => true,
                        'payment_covered_by_credits' => true,
                        'payment' => $payment,
                        'message' => 'Paiement effectué entièrement avec vos crédits de parrainage.',
                    ]);
                }
            } else {
                $result = $this->stripeService->createPaymentIntent($payable, $finalAmount, [
                    'referral_credits_used' => $referralCreditsUsed,
                    'original_amount' => $originalAmount,
                ]);

                if ($result['success'] && $referralCreditsUsed > 0) {
                    $user->useReferralCredits($referralCreditsUsed, $payable);
                }

                return response()->json($result);
            }

        } catch (\Exception $e) {
            Log::error('Stripe payment intent creation failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la création du paiement. Veuillez réessayer.',
            ], 500);
        }
    }

    /**
     * Create PayPal order
     */
    public function createPayPalOrder(Request $request)
    {
        $request->validate([
            'payable_type' => 'required|string|in:rental,property_booking',
            'payable_id' => 'required|integer',
            'amount' => 'required|integer|min:50',
            'referral_credits' => 'nullable|numeric|min:0',
        ]);

        try {
            $payable = $this->resolvePayable($request->payable_type, $request->payable_id);
            $user = Auth::user();

            if (! $payable || ! $this->verifyPayableOwnership($payable, $user)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé à ce paiement.',
                ], 403);
            }

            if ($payable->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'error' => 'Ce paiement a déjà été effectué.',
                ], 400);
            }

            $originalAmount = $request->amount;
            $referralCreditsUsed = 0;
            $finalAmount = $originalAmount;

            if ($request->referral_credits && $request->referral_credits > 0) {
                $creditsToUse = min($request->referral_credits, $user->getAvailableCredits());
                $creditsToUse = min($creditsToUse, $originalAmount / 100);

                if ($creditsToUse > 0 && $user->canUseReferralCredits($creditsToUse)) {
                    $referralCreditsUsed = $creditsToUse;
                    $finalAmount = max(50, $originalAmount - ($creditsToUse * 100));
                }
            }

            if ($finalAmount <= 0) {
                if ($user->useReferralCredits($referralCreditsUsed, $payable)) {
                    $payable->update([
                        'payment_status' => 'paid',
                        'status' => $payable instanceof Rental ? 'confirmed' : $payable->status,
                    ]);

                    $payment = Payment::create([
                        'payable_type' => get_class($payable),
                        'payable_id' => $payable->id,
                        'rental_id' => $payable instanceof Rental ? $payable->id : null,
                        'user_id' => $user->id,
                        'amount' => $originalAmount,
                        'referral_credits_used' => $referralCreditsUsed * 100,
                        'final_amount' => 0,
                        'payment_method' => 'referral_credits',
                        'status' => 'completed',
                        'paid_at' => now(),
                    ]);

                    return response()->json([
                        'success' => true,
                        'payment_covered_by_credits' => true,
                        'payment' => $payment,
                        'message' => 'Paiement effectué entièrement avec vos crédits de parrainage.',
                    ]);
                }
            } else {
                $result = $this->paypalService->createOrder($payable, $finalAmount, [
                    'referral_credits_used' => $referralCreditsUsed,
                    'original_amount' => $originalAmount,
                ]);

                if ($result['success'] && $referralCreditsUsed > 0) {
                    $user->useReferralCredits($referralCreditsUsed, $payable);
                }

                return response()->json($result);
            }

        } catch (\Exception $e) {
            Log::error('PayPal order creation failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la création du paiement PayPal. Veuillez réessayer.',
            ], 500);
        }
    }

    /**
     * Handle successful payment
     */
    public function success(Request $request)
    {
        $payment = null;
        $rental = null;
        $booking = null;

        // Handle Stripe payment confirmation
        if ($request->has('payment_intent')) {
            $result = $this->stripeService->confirmPayment($request->payment_intent);

            if ($result['success']) {
                $payment = $result['payment'];
            }
        }

        // Handle PayPal payment confirmation
        if ($request->has('token') && $request->has('PayerID')) {
            $result = $this->paypalService->captureOrder($request->token);

            if ($result['success']) {
                $payment = $result['payment'];
            }
        }

        // Load the payable entity for display
        if ($payment) {
            $payment->load('payable');
            if ($payment->payable instanceof Rental) {
                $rental = $payment->payable->load('vehicle');
            } elseif ($payment->payable instanceof PropertyBooking) {
                $booking = $payment->payable->load(['property', 'guest']);

                // Send notifications for property booking payment
                try {
                    $guest = $booking->guest;
                    $owner = $booking->property->owner;

                    $guest->notify(new BookingConfirmed($booking));
                    $owner->notify(new NewBookingReceived($booking));
                } catch (\Exception $e) {
                    Log::error('Booking notification failed: '.$e->getMessage());
                }
            }
        }

        return Inertia::render('Payment/Success', [
            'payment' => $payment,
            'rental' => $rental,
            'booking' => $booking,
        ]);
    }

    /**
     * Handle payment cancellation
     */
    public function cancel(Request $request)
    {
        // Handle PayPal cancellation
        if ($request->has('token')) {
            $this->paypalService->cancelOrder($request->token);
        }

        return redirect()->route('dashboard')
            ->with('error', 'Paiement annulé.');
    }

    /**
     * Process refund for a payment
     */
    public function refund(Request $request, Payment $payment)
    {
        $user = Auth::user();

        // Check authorization based on payable type
        $payable = $payment->payable;
        if ($payable instanceof Rental) {
            if (! $user->is_admin && $payable->vehicle->owner_id !== $user->id) {
                abort(403, 'Unauthorized to refund this payment.');
            }
        } elseif ($payable instanceof PropertyBooking) {
            if (! $user->is_admin && $payable->property->owner_id !== $user->id) {
                abort(403, 'Unauthorized to refund this payment.');
            }
        } else {
            abort(403, 'Unknown payable type.');
        }

        $request->validate([
            'amount' => 'nullable|integer|min:1',
            'reason' => 'required|string|max:500',
        ]);

        try {
            if ($payment->payment_method === 'stripe') {
                $result = $this->stripeService->refund($payment, $request->amount);
            } else {
                $result = $this->paypalService->refund($payment, $request->amount);
            }

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Remboursement traité avec succès.',
                    'refund_amount' => $result['amount_refunded'],
                    'is_full_refund' => $result['is_full_refund'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 400);

        } catch (\Exception $e) {
            Log::error('Payment refund failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du remboursement. Veuillez réessayer.',
            ], 500);
        }
    }

    /**
     * Handle Stripe webhooks
     */
    public function stripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('payment.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $this->stripeService->confirmPayment($paymentIntent->id);
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    // Handle failed payment
                    $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();
                    if ($payment) {
                        $payment->update([
                            'status' => 'failed',
                            'failed_at' => now(),
                            'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Payment failed',
                        ]);
                    }
                    break;

                default:
                    Log::info('Unhandled Stripe webhook event type: '.$event->type);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Stripe webhook error: '.$e->getMessage());

            return response()->json(['error' => 'Webhook error'], 400);
        }
    }

    private function resolvePayable(string $type, int $id): ?Model
    {
        return match ($type) {
            'rental' => Rental::find($id),
            'property_booking' => PropertyBooking::find($id),
            default => null,
        };
    }

    private function verifyPayableOwnership(Model $payable, $user): bool
    {
        if ($payable instanceof Rental) {
            return $payable->renter_id === $user->id;
        }

        if ($payable instanceof PropertyBooking) {
            return $payable->guest_id === $user->id;
        }

        return false;
    }
}
