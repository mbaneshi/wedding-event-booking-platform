<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Exception;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createPaymentIntent(Booking $booking, float $amount, string $type = 'deposit'): array
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => strtolower($booking->currency),
                'metadata' => [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'payment_type' => $type,
                ],
                'description' => "Booking #{$booking->booking_number}",
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to create payment intent: ' . $e->getMessage());
        }
    }

    public function confirmPayment(string $paymentIntentId, Booking $booking, string $type = 'deposit'): Payment
    {
        try {
            $intent = PaymentIntent::retrieve($paymentIntentId);

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'stripe_payment_intent_id' => $intent->id,
                'amount' => $intent->amount / 100,
                'currency' => strtoupper($intent->currency),
                'payment_method' => 'card',
                'status' => $intent->status,
                'payment_type' => $type,
            ]);

            // Update booking payment status
            if ($type === 'deposit') {
                $booking->update([
                    'deposit_paid' => true,
                    'deposit_paid_at' => now(),
                ]);
            } elseif ($type === 'balance') {
                $booking->update([
                    'balance_paid' => true,
                    'balance_paid_at' => now(),
                ]);
            }

            return $payment;
        } catch (Exception $e) {
            throw new Exception('Failed to confirm payment: ' . $e->getMessage());
        }
    }

    public function processRefund(Booking $booking, ?float $amount = null, string $reason = 'requested_by_customer'): void
    {
        try {
            $payment = $booking->payments()
                ->where('status', 'succeeded')
                ->where('payment_type', 'deposit')
                ->first();

            if (!$payment) {
                throw new Exception('No successful payment found for this booking');
            }

            $refundAmount = $amount ?? $payment->amount;

            $refund = Refund::create([
                'payment_intent' => $payment->stripe_payment_intent_id,
                'amount' => $refundAmount * 100,
            ]);

            $payment->update([
                'refunded' => true,
                'refund_amount' => $refundAmount,
                'refund_reason' => $reason,
                'refunded_at' => now(),
                'status' => 'refunded',
            ]);

            $booking->update([
                'status' => 'refunded',
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to process refund: ' . $e->getMessage());
        }
    }

    public function handleWebhook(array $payload): void
    {
        $event = $payload['type'];
        $data = $payload['data']['object'];

        switch ($event) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSuccess($data);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailure($data);
                break;

            case 'charge.refunded':
                $this->handleRefund($data);
                break;
        }
    }

    private function handlePaymentSuccess(array $data): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $data['id'])->first();

        if ($payment) {
            $payment->update(['status' => 'succeeded']);
        }
    }

    private function handlePaymentFailure(array $data): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $data['id'])->first();

        if ($payment) {
            $payment->update(['status' => 'failed']);
        }
    }

    private function handleRefund(array $data): void
    {
        // Handle refund webhook
    }
}
