<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Message;

class EmailService
{
    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail(User $user): void
    {
        try {
            Mail::send('emails.welcome', ['user' => $user], function (Message $message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Welcome to Wedding Booking Platform');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email: ' . $e->getMessage());
        }
    }

    /**
     * Send vendor approval email
     */
    public function sendVendorApprovalEmail(Vendor $vendor): void
    {
        try {
            Mail::send('emails.vendor.approved', ['vendor' => $vendor], function (Message $message) use ($vendor) {
                $message->to($vendor->email, $vendor->business_name)
                    ->subject('Your Vendor Account Has Been Approved!');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send vendor approval email: ' . $e->getMessage());
        }
    }

    /**
     * Send vendor rejection email
     */
    public function sendVendorRejectionEmail(Vendor $vendor, string $reason): void
    {
        try {
            Mail::send('emails.vendor.rejected', [
                'vendor' => $vendor,
                'reason' => $reason
            ], function (Message $message) use ($vendor) {
                $message->to($vendor->email, $vendor->business_name)
                    ->subject('Vendor Application Update');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send vendor rejection email: ' . $e->getMessage());
        }
    }

    /**
     * Send booking reminder email
     */
    public function sendBookingReminder(Booking $booking, int $daysUntilEvent): void
    {
        try {
            Mail::send('emails.booking.reminder', [
                'booking' => $booking,
                'daysUntilEvent' => $daysUntilEvent
            ], function (Message $message) use ($booking) {
                $message->to($booking->customer->email, $booking->customer->name)
                    ->subject("Reminder: Your Event is in {$daysUntilEvent} Days");
            });
        } catch (\Exception $e) {
            Log::error('Failed to send booking reminder: ' . $e->getMessage());
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(User $user, string $token): void
    {
        try {
            $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token;

            Mail::send('emails.auth.password-reset', [
                'user' => $user,
                'resetUrl' => $resetUrl,
                'expiresIn' => 60 // minutes
            ], function (Message $message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Reset Your Password');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email: ' . $e->getMessage());
        }
    }

    /**
     * Send email verification
     */
    public function sendEmailVerification(User $user, string $verificationUrl): void
    {
        try {
            Mail::send('emails.auth.verify-email', [
                'user' => $user,
                'verificationUrl' => $verificationUrl
            ], function (Message $message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Verify Your Email Address');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send email verification: ' . $e->getMessage());
        }
    }

    /**
     * Send invoice email
     */
    public function sendInvoiceEmail(Booking $booking): void
    {
        try {
            Mail::send('emails.invoice', ['booking' => $booking], function (Message $message) use ($booking) {
                $message->to($booking->customer->email, $booking->customer->name)
                    ->subject("Invoice for Booking #{$booking->booking_number}");
            });
        } catch (\Exception $e) {
            Log::error('Failed to send invoice email: ' . $e->getMessage());
        }
    }

    /**
     * Send bulk promotional email
     */
    public function sendPromotionalEmail(array $recipients, string $subject, string $content): void
    {
        try {
            foreach ($recipients as $recipient) {
                Mail::send('emails.promotional', [
                    'content' => $content,
                    'recipient' => $recipient
                ], function (Message $message) use ($recipient, $subject) {
                    $message->to($recipient['email'], $recipient['name'])
                        ->subject($subject);
                });
            }
        } catch (\Exception $e) {
            Log::error('Failed to send promotional email: ' . $e->getMessage());
        }
    }
}
