<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PaymentService;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\PaymentIntent;
use Stripe\Refund;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = new PaymentService();
    }

    public function test_can_create_payment_intent()
    {
        $booking = Booking::factory()->create([
            'total_price' => 5000,
            'currency' => 'USD',
        ]);

        $result = $this->paymentService->createPaymentIntent($booking, 1500, 'deposit');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('client_secret', $result);
        $this->assertArrayHasKey('payment_intent_id', $result);
    }

    public function test_confirm_payment_creates_payment_record()
    {
        $booking = Booking::factory()->create();

        $mockIntent = Mockery::mock();
        $mockIntent->id = 'pi_test123';
        $mockIntent->amount = 150000; // $1500 in cents
        $mockIntent->currency = 'usd';
        $mockIntent->status = 'succeeded';

        PaymentIntent::shouldReceive('retrieve')
            ->once()
            ->with('pi_test123')
            ->andReturn($mockIntent);

        $payment = $this->paymentService->confirmPayment('pi_test123', $booking, 'deposit');

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals(1500, $payment->amount);
        $this->assertEquals('USD', $payment->currency);
        $this->assertEquals('deposit', $payment->payment_type);
        $this->assertTrue($booking->fresh()->deposit_paid);
    }

    public function test_updates_booking_on_deposit_payment()
    {
        $booking = Booking::factory()->create([
            'deposit_paid' => false,
        ]);

        $mockIntent = Mockery::mock();
        $mockIntent->id = 'pi_test123';
        $mockIntent->amount = 150000;
        $mockIntent->currency = 'usd';
        $mockIntent->status = 'succeeded';

        PaymentIntent::shouldReceive('retrieve')
            ->once()
            ->andReturn($mockIntent);

        $this->paymentService->confirmPayment('pi_test123', $booking, 'deposit');

        $this->assertTrue($booking->fresh()->deposit_paid);
        $this->assertNotNull($booking->fresh()->deposit_paid_at);
    }

    public function test_updates_booking_on_balance_payment()
    {
        $booking = Booking::factory()->create([
            'balance_paid' => false,
        ]);

        $mockIntent = Mockery::mock();
        $mockIntent->id = 'pi_test123';
        $mockIntent->amount = 350000;
        $mockIntent->currency = 'usd';
        $mockIntent->status = 'succeeded';

        PaymentIntent::shouldReceive('retrieve')
            ->once()
            ->andReturn($mockIntent);

        $this->paymentService->confirmPayment('pi_test123', $booking, 'balance');

        $this->assertTrue($booking->fresh()->balance_paid);
        $this->assertNotNull($booking->fresh()->balance_paid_at);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
