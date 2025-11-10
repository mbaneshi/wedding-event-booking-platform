<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function createIntent(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|uuid|exists:bookings,id',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:deposit,balance,full',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);

        // Verify user owns this booking
        if ($booking->customer_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $paymentIntent = $this->paymentService->createPaymentIntent(
                $booking,
                $validated['amount'],
                $validated['type']
            );

            return response()->json($paymentIntent);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
            'booking_id' => 'required|uuid|exists:bookings,id',
            'type' => 'required|in:deposit,balance,full',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);

        try {
            $payment = $this->paymentService->confirmPayment(
                $validated['payment_intent_id'],
                $booking,
                $validated['type']
            );

            return response()->json([
                'payment' => $payment,
                'booking' => $booking->fresh(),
                'message' => 'Payment confirmed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );

            $this->paymentService->handleWebhook([
                'type' => $event->type,
                'data' => ['object' => $event->data->object],
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
