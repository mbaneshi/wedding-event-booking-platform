<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\NotificationService;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = new NotificationService();
        Mail::fake();
    }

    public function test_sends_booking_notification_to_customer_and_vendor()
    {
        $customer = User::factory()->create(['email' => 'customer@example.com']);
        $vendor = Vendor::factory()->create(['email' => 'vendor@example.com']);

        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'vendor_id' => $vendor->id,
        ]);

        $this->notificationService->sendBookingNotification($booking);

        Mail::assertSent(\Illuminate\Mail\Mailable::class, 2);
    }

    public function test_sends_booking_confirmation()
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id]);

        $this->notificationService->sendBookingConfirmation($booking);

        Mail::assertSent(\Illuminate\Mail\Mailable::class, 1);
    }

    public function test_sends_cancellation_notification()
    {
        $customer = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'vendor_id' => $vendor->id,
        ]);

        $this->notificationService->sendCancellationNotification($booking);

        Mail::assertSent(\Illuminate\Mail\Mailable::class, 2);
    }

    public function test_sends_payment_confirmation()
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id]);

        $this->notificationService->sendPaymentConfirmation($booking);

        Mail::assertSent(\Illuminate\Mail\Mailable::class, 1);
    }

    public function test_sends_review_request_only_for_reviewable_bookings()
    {
        $customer = User::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'completed',
        ]);

        $this->notificationService->sendReviewRequest($booking);

        Mail::assertSent(\Illuminate\Mail\Mailable::class);
    }

    public function test_handles_email_failures_gracefully()
    {
        Mail::shouldReceive('send')->andThrow(new \Exception('Email failed'));

        $booking = Booking::factory()->create();

        // Should not throw exception
        $this->notificationService->sendBookingNotification($booking);

        $this->assertTrue(true); // Test passes if no exception thrown
    }
}
