<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendBookingNotification(Booking $booking): void
    {
        try {
            // Send email to customer
            Mail::send('emails.booking.created', ['booking' => $booking], function ($message) use ($booking) {
                $message->to($booking->customer->email)
                    ->subject('Booking Request Received - ' . $booking->booking_number);
            });

            // Send email to vendor
            Mail::send('emails.vendor.new-booking', ['booking' => $booking], function ($message) use ($booking) {
                $message->to($booking->vendor->email)
                    ->subject('New Booking Request - ' . $booking->booking_number);
            });
        } catch (\Exception $e) {
            Log::error('Failed to send booking notification: ' . $e->getMessage());
        }
    }

    public function sendBookingConfirmation(Booking $booking): void
    {
        try {
            Mail::send('emails.booking.confirmed', ['booking' => $booking], function ($message) use ($booking) {
                $message->to($booking->customer->email)
                    ->subject('Booking Confirmed - ' . $booking->booking_number);
            });
        } catch (\Exception $e) {
            Log::error('Failed to send booking confirmation: ' . $e->getMessage());
        }
    }

    public function sendCancellationNotification(Booking $booking): void
    {
        try {
            // Notify customer
            Mail::send('emails.booking.cancelled', ['booking' => $booking], function ($message) use ($booking) {
                $message->to($booking->customer->email)
                    ->subject('Booking Cancelled - ' . $booking->booking_number);
            });

            // Notify vendor
            Mail::send('emails.vendor.booking-cancelled', ['booking' => $booking], function ($message) use ($booking) {
                $message->to($booking->vendor->email)
                    ->subject('Booking Cancelled - ' . $booking->booking_number);
            });
        } catch (\Exception $e) {
            Log::error('Failed to send cancellation notification: ' . $e->getMessage());
        }
    }

    public function sendPaymentConfirmation(Booking $booking): void
    {
        try {
            Mail::send('emails.payment.confirmed', ['booking' => $booking], function ($message) use ($booking) {
                $message->to($booking->customer->email)
                    ->subject('Payment Received - ' . $booking->booking_number);
            });
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation: ' . $e->getMessage());
        }
    }

    public function sendReviewRequest(Booking $booking): void
    {
        try {
            if ($booking->canBeReviewed()) {
                Mail::send('emails.review.request', ['booking' => $booking], function ($message) use ($booking) {
                    $message->to($booking->customer->email)
                        ->subject('How was your experience? - ' . $booking->vendor->business_name);
                });
            }
        } catch (\Exception $e) {
            Log::error('Failed to send review request: ' . $e->getMessage());
        }
    }
}
