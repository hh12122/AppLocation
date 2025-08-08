<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Rental;
use App\Services\Payment\StripeService;
use App\Services\Payment\PayPalService;
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

        return Inertia::render('Payment/Form', [
            'rental' => $rental->load('vehicle'),
        ]);
    }

    /**
     * Create Stripe payment intent
     */
    public function createStripeIntent(Request $request)
    {
        $request->validate([
            'rental_id' => 'required|exists:rentals,id',
            'amount' => 'required|integer|min:50', // Minimum 50 cents
        ]);

        try {
            $rental = Rental::findOrFail($request->rental_id);

            // Verify user owns this rental
            if ($rental->renter_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized access to this rental.',
                ], 403);
            }

            // Check if already paid
            if ($rental->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'error' => 'Cette location a déjà été payée.',
                ], 400);
            }

            $result = $this->stripeService->createPaymentIntent($rental, $request->amount);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Stripe payment intent creation failed: ' . $e->getMessage());
            
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
            'rental_id' => 'required|exists:rentals,id',
            'amount' => 'required|integer|min:50',
        ]);

        try {
            $rental = Rental::findOrFail($request->rental_id);

            // Verify user owns this rental
            if ($rental->renter_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized access to this rental.',
                ], 403);
            }

            // Check if already paid
            if ($rental->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'error' => 'Cette location a déjà été payée.',
                ], 400);
            }

            $result = $this->paypalService->createOrder($rental, $request->amount);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('PayPal order creation failed: ' . $e->getMessage());
            
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

        // Handle Stripe payment confirmation
        if ($request->has('payment_intent')) {
            $result = $this->stripeService->confirmPayment($request->payment_intent);
            
            if ($result['success']) {
                $payment = $result['payment'];
                $rental = $payment->rental;
            }
        }

        // Handle PayPal payment confirmation
        if ($request->has('token') && $request->has('PayerID')) {
            $result = $this->paypalService->captureOrder($request->token);
            
            if ($result['success']) {
                $payment = $result['payment'];
                $rental = $payment->rental;
            }
        }

        return Inertia::render('Payment/Success', [
            'payment' => $payment,
            'rental' => $rental?->load('vehicle'),
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
        // Check if user is authorized (rental owner or admin)
        $user = Auth::user();
        if (!$user->is_admin && $payment->rental->vehicle->owner_id !== $user->id) {
            abort(403, 'Unauthorized to refund this payment.');
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
            Log::error('Payment refund failed: ' . $e->getMessage());
            
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
                    Log::info('Unhandled Stripe webhook event type: ' . $event->type);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook error'], 400);
        }
    }
}
