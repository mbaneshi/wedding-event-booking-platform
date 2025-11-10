<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VendorControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_vendors()
    {
        Vendor::factory()->count(3)->create(['status' => 'approved']);

        $response = $this->getJson('/api/vendors');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_search_vendors()
    {
        $category = Category::factory()->create(['slug' => 'photographers']);
        Vendor::factory()->create([
            'status' => 'approved',
            'category_id' => $category->id,
            'city' => 'New York',
        ]);

        $response = $this->getJson('/api/vendors/search?category=photographers&city=New York');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_get_vendor_by_id()
    {
        $vendor = Vendor::factory()->create(['status' => 'approved']);

        $response = $this->getJson("/api/vendors/{$vendor->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $vendor->id]);
    }

    public function test_authenticated_user_can_create_vendor()
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/vendors', [
            'business_name' => 'Dream Weddings',
            'category_id' => $category->id,
            'city' => 'New York',
            'country' => 'USA',
            'description' => 'Professional wedding planning',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['business_name' => 'Dream Weddings']);
    }

    public function test_vendor_owner_can_update_vendor()
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->putJson("/api/vendors/{$vendor->id}", [
            'business_name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['business_name' => 'Updated Name']);
    }

    public function test_cannot_update_other_vendors()
    {
        $user1 = User::factory()->create(['role' => 'vendor']);
        $user2 = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->putJson("/api/vendors/{$vendor->id}", [
            'business_name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_can_get_vendor_availability()
    {
        $vendor = Vendor::factory()->create();

        $response = $this->getJson("/api/vendors/{$vendor->id}/availability");

        $response->assertStatus(200);
    }

    public function test_increments_view_count_on_view()
    {
        $vendor = Vendor::factory()->create(['view_count' => 0]);

        $this->getJson("/api/vendors/{$vendor->id}");

        $this->assertEquals(1, $vendor->fresh()->view_count);
    }
}
