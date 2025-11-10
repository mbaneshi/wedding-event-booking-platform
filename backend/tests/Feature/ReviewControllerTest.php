<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_review_for_completed_booking()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $vendor = Vendor::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $user->id,
            'vendor_id' => $vendor->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->postJson('/api/reviews', [
            'booking_id' => $booking->id,
            'vendor_id' => $vendor->id,
            'rating' => 5,
            'comment' => 'Excellent service!',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['rating' => 5]);
    }

    public function test_can_list_vendor_reviews()
    {
        $vendor = Vendor::factory()->create();
        Review::factory()->count(3)->create(['vendor_id' => $vendor->id]);

        $response = $this->getJson("/api/vendors/{$vendor->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_cannot_review_without_completed_booking()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $vendor = Vendor::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/reviews', [
            'vendor_id' => $vendor->id,
            'rating' => 5,
            'comment' => 'Great!',
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_review_same_booking_twice()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $vendor = Vendor::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $user->id,
            'vendor_id' => $vendor->id,
            'status' => 'completed',
        ]);

        Review::factory()->create([
            'booking_id' => $booking->id,
            'customer_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/reviews', [
            'booking_id' => $booking->id,
            'vendor_id' => $vendor->id,
            'rating' => 5,
            'comment' => 'Duplicate review',
        ]);

        $response->assertStatus(422);
    }

    public function test_review_updates_vendor_rating()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $vendor = Vendor::factory()->create([
            'rating_average' => 0,
            'rating_count' => 0,
        ]);
        $booking = Booking::factory()->create([
            'customer_id' => $user->id,
            'vendor_id' => $vendor->id,
            'status' => 'completed',
        ]);

        $this->actingAs($user)->postJson('/api/reviews', [
            'booking_id' => $booking->id,
            'vendor_id' => $vendor->id,
            'rating' => 5,
            'comment' => 'Perfect!',
        ]);

        $vendor->refresh();
        $this->assertEquals(5, $vendor->rating_average);
        $this->assertEquals(1, $vendor->rating_count);
    }

    public function test_validates_rating_range()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $vendor = Vendor::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $user->id,
            'vendor_id' => $vendor->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->postJson('/api/reviews', [
            'booking_id' => $booking->id,
            'vendor_id' => $vendor->id,
            'rating' => 6, // Invalid rating
            'comment' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }
}
