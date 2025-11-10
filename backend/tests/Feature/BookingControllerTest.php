<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Vendor;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_booking()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $vendor = Vendor::factory()->create(['status' => 'approved']);
        $service = Service::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'vendor_id' => $vendor->id,
            'service_id' => $service->id,
            'event_date' => '2024-12-25',
            'event_time' => '18:00',
            'guest_count' => 100,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'booking_number', 'status', 'total_price'],
            ]);
    }

    public function test_customer_can_view_own_bookings()
    {
        $user = User::factory()->create(['role' => 'customer']);
        Booking::factory()->count(3)->create(['customer_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_cannot_view_other_customer_bookings()
    {
        $user1 = User::factory()->create(['role' => 'customer']);
        $user2 = User::factory()->create(['role' => 'customer']);
        $booking = Booking::factory()->create(['customer_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson("/api/bookings/{$booking->id}");

        $response->assertStatus(403);
    }

    public function test_vendor_can_view_their_bookings()
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::factory()->create(['user_id' => $user->id]);
        Booking::factory()->count(2)->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($user)->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_vendor_can_confirm_booking()
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::factory()->create(['user_id' => $user->id]);
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->putJson("/api/bookings/{$booking->id}/confirm");

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'confirmed']);
    }

    public function test_customer_can_cancel_own_booking()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $booking = Booking::factory()->create([
            'customer_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->putJson("/api/bookings/{$booking->id}/cancel", [
            'reason' => 'Changed plans',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'cancelled']);
    }

    public function test_guest_cannot_create_booking()
    {
        $vendor = Vendor::factory()->create();
        $service = Service::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->postJson('/api/bookings', [
            'vendor_id' => $vendor->id,
            'service_id' => $service->id,
            'event_date' => '2024-12-25',
        ]);

        $response->assertStatus(401);
    }

    public function test_validates_required_fields()
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->postJson('/api/bookings', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vendor_id', 'service_id', 'event_date']);
    }
}
