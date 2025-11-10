<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_payment_intent()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $booking = Booking::factory()->create(['customer_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/payments/intent', [
            'booking_id' => $booking->id,
            'amount' => $booking->deposit_amount,
            'type' => 'deposit',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['client_secret', 'payment_intent_id']);
    }

    public function test_customer_can_confirm_payment()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $booking = Booking::factory()->create(['customer_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/payments/confirm', [
            'booking_id' => $booking->id,
            'payment_intent_id' => 'pi_test123',
            'type' => 'deposit',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'amount', 'status']]);
    }

    public function test_customer_can_view_payment_history()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $booking = Booking::factory()->create(['customer_id' => $user->id]);
        Payment::factory()->count(2)->create(['booking_id' => $booking->id]);

        $response = $this->actingAs($user)->getJson("/api/bookings/{$booking->id}/payments");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_cannot_create_payment_for_other_customer_booking()
    {
        $user1 = User::factory()->create(['role' => 'customer']);
        $user2 = User::factory()->create(['role' => 'customer']);
        $booking = Booking::factory()->create(['customer_id' => $user2->id]);

        $response = $this->actingAs($user1)->postJson('/api/payments/intent', [
            'booking_id' => $booking->id,
            'amount' => $booking->deposit_amount,
        ]);

        $response->assertStatus(403);
    }

    public function test_webhook_handles_payment_success()
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'stripe_payment_intent_id' => 'pi_test123',
            'status' => 'processing',
        ]);

        $response = $this->postJson('/api/webhooks/stripe', [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test123',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_validates_payment_amount()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $booking = Booking::factory()->create(['customer_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/payments/intent', [
            'booking_id' => $booking->id,
            'amount' => -100, // Negative amount
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }
}
