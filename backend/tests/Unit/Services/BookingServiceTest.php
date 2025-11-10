<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Models\Booking;
use App\Models\Vendor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookingService;
    private PaymentService $paymentService;
    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = Mockery::mock(PaymentService::class);
        $this->notificationService = Mockery::mock(NotificationService::class);

        $this->bookingService = new BookingService(
            $this->paymentService,
            $this->notificationService
        );
    }

    public function test_can_create_booking()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $vendor = Vendor::factory()->create(['status' => 'approved']);
        $service = Service::factory()->create([
            'vendor_id' => $vendor->id,
            'price_from' => 5000,
            'currency' => 'USD',
        ]);

        $this->notificationService->shouldReceive('sendBookingNotification')->once();

        $booking = $this->bookingService->createBooking([
            'vendor_id' => $vendor->id,
            'service_id' => $service->id,
            'event_date' => '2024-12-25',
            'event_time' => '18:00',
            'guest_count' => 100,
        ]);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals('pending', $booking->status);
        $this->assertEquals(5000, $booking->total_price);
        $this->assertEquals(1500, $booking->deposit_amount); // 30%
        $this->assertEquals(3500, $booking->balance_amount); // 70%
        $this->assertStringStartsWith('BK-', $booking->booking_number);
    }

    public function test_generates_unique_booking_number()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $vendor = Vendor::factory()->create(['status' => 'approved']);
        $service = Service::factory()->create(['vendor_id' => $vendor->id]);

        $this->notificationService->shouldReceive('sendBookingNotification')->twice();

        $booking1 = $this->bookingService->createBooking([
            'vendor_id' => $vendor->id,
            'service_id' => $service->id,
            'event_date' => '2024-12-25',
        ]);

        $booking2 = $this->bookingService->createBooking([
            'vendor_id' => $vendor->id,
            'service_id' => $service->id,
            'event_date' => '2024-12-26',
        ]);

        $this->assertNotEquals($booking1->booking_number, $booking2->booking_number);
    }

    public function test_calculates_platform_commission()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $vendor = Vendor::factory()->create(['status' => 'approved']);
        $service = Service::factory()->create([
            'vendor_id' => $vendor->id,
            'price_from' => 10000,
        ]);

        $this->notificationService->shouldReceive('sendBookingNotification')->once();

        $booking = $this->bookingService->createBooking([
            'vendor_id' => $vendor->id,
            'service_id' => $service->id,
            'event_date' => '2024-12-25',
        ]);

        $this->assertEquals(1000, $booking->commission_amount); // 10% of 10000
        $this->assertEquals(10, $booking->commission_rate);
    }

    public function test_can_confirm_booking()
    {
        $booking = Booking::factory()->create(['status' => 'pending']);

        $this->notificationService
            ->shouldReceive('sendBookingConfirmation')
            ->once()
            ->with($booking);

        $confirmedBooking = $this->bookingService->confirmBooking($booking, 'pi_test123');

        $this->assertEquals('confirmed', $confirmedBooking->status);
        $this->assertTrue($confirmedBooking->deposit_paid);
        $this->assertNotNull($confirmedBooking->deposit_paid_at);
    }

    public function test_can_cancel_booking()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'customer_id' => $user->id,
        ]);

        $this->paymentService
            ->shouldReceive('processRefund')
            ->once()
            ->with($booking);

        $this->notificationService
            ->shouldReceive('sendCancellationNotification')
            ->once();

        $cancelledBooking = $this->bookingService->cancelBooking(
            $booking,
            'Changed plans',
            $user->id
        );

        $this->assertEquals('cancelled', $cancelledBooking->status);
        $this->assertEquals('Changed plans', $cancelledBooking->cancellation_reason);
        $this->assertEquals($user->id, $cancelledBooking->cancelled_by);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
