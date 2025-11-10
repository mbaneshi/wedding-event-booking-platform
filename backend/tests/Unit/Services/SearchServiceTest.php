<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SearchService;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private SearchService $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchService = new SearchService();
    }

    public function test_can_search_approved_vendors_only()
    {
        $category = Category::factory()->create();

        $approvedVendor = Vendor::factory()->create([
            'status' => 'approved',
            'category_id' => $category->id,
        ]);

        $pendingVendor = Vendor::factory()->create([
            'status' => 'pending',
            'category_id' => $category->id,
        ]);

        $results = $this->searchService->search([]);

        $this->assertEquals(1, $results->total());
        $this->assertEquals($approvedVendor->id, $results->first()->id);
    }

    public function test_can_filter_by_category()
    {
        $category1 = Category::factory()->create(['slug' => 'photographers']);
        $category2 = Category::factory()->create(['slug' => 'venues']);

        $vendor1 = Vendor::factory()->create([
            'status' => 'approved',
            'category_id' => $category1->id,
        ]);

        $vendor2 = Vendor::factory()->create([
            'status' => 'approved',
            'category_id' => $category2->id,
        ]);

        $results = $this->searchService->search(['category' => 'photographers']);

        $this->assertEquals(1, $results->total());
        $this->assertEquals($vendor1->id, $results->first()->id);
    }

    public function test_can_filter_by_city()
    {
        $vendor1 = Vendor::factory()->create([
            'status' => 'approved',
            'city' => 'New York',
        ]);

        $vendor2 = Vendor::factory()->create([
            'status' => 'approved',
            'city' => 'Los Angeles',
        ]);

        $results = $this->searchService->search(['city' => 'New York']);

        $this->assertEquals(1, $results->total());
        $this->assertEquals($vendor1->id, $results->first()->id);
    }

    public function test_can_filter_by_price_range()
    {
        $vendor1 = Vendor::factory()->create(['status' => 'approved']);
        $vendor2 = Vendor::factory()->create(['status' => 'approved']);

        Service::factory()->create([
            'vendor_id' => $vendor1->id,
            'price_from' => 1000,
            'price_to' => 2000,
        ]);

        Service::factory()->create([
            'vendor_id' => $vendor2->id,
            'price_from' => 5000,
            'price_to' => 10000,
        ]);

        $results = $this->searchService->search([
            'price_min' => 900,
            'price_max' => 3000,
        ]);

        $this->assertEquals(1, $results->total());
        $this->assertEquals($vendor1->id, $results->first()->id);
    }

    public function test_can_filter_by_minimum_rating()
    {
        $vendor1 = Vendor::factory()->create([
            'status' => 'approved',
            'rating_average' => 4.8,
        ]);

        $vendor2 = Vendor::factory()->create([
            'status' => 'approved',
            'rating_average' => 3.5,
        ]);

        $results = $this->searchService->search(['min_rating' => 4.0]);

        $this->assertEquals(1, $results->total());
        $this->assertEquals($vendor1->id, $results->first()->id);
    }

    public function test_sorts_by_rating_by_default()
    {
        $vendor1 = Vendor::factory()->create([
            'status' => 'approved',
            'rating_average' => 4.5,
            'rating_count' => 10,
        ]);

        $vendor2 = Vendor::factory()->create([
            'status' => 'approved',
            'rating_average' => 4.8,
            'rating_count' => 20,
        ]);

        $results = $this->searchService->search([]);

        $this->assertEquals($vendor2->id, $results->first()->id);
    }

    public function test_can_sort_by_price()
    {
        $vendor1 = Vendor::factory()->create(['status' => 'approved']);
        $vendor2 = Vendor::factory()->create(['status' => 'approved']);

        Service::factory()->create([
            'vendor_id' => $vendor1->id,
            'price_from' => 5000,
        ]);

        Service::factory()->create([
            'vendor_id' => $vendor2->id,
            'price_from' => 2000,
        ]);

        $results = $this->searchService->search(['sort_by' => 'price']);

        $this->assertEquals($vendor2->id, $results->first()->id);
    }

    public function test_can_sort_by_popularity()
    {
        $vendor1 = Vendor::factory()->create([
            'status' => 'approved',
            'view_count' => 100,
        ]);

        $vendor2 = Vendor::factory()->create([
            'status' => 'approved',
            'view_count' => 250,
        ]);

        $results = $this->searchService->search(['sort_by' => 'popularity']);

        $this->assertEquals($vendor2->id, $results->first()->id);
    }

    public function test_featured_vendors_appear_first()
    {
        $vendor1 = Vendor::factory()->create([
            'status' => 'approved',
            'featured' => false,
            'rating_average' => 5.0,
        ]);

        $vendor2 = Vendor::factory()->create([
            'status' => 'approved',
            'featured' => true,
            'rating_average' => 4.0,
        ]);

        $results = $this->searchService->search([]);

        $this->assertEquals($vendor2->id, $results->first()->id);
    }

    public function test_can_search_by_location_within_radius()
    {
        $vendor1 = Vendor::factory()->create([
            'status' => 'approved',
            'latitude' => 40.7128,  // New York
            'longitude' => -74.0060,
        ]);

        $vendor2 = Vendor::factory()->create([
            'status' => 'approved',
            'latitude' => 34.0522,  // Los Angeles
            'longitude' => -118.2437,
        ]);

        // Search near New York
        $results = $this->searchService->searchByLocation(40.7128, -74.0060, 50);

        $this->assertEquals(1, $results->total());
        $this->assertEquals($vendor1->id, $results->first()->id);
    }

    public function test_paginates_results()
    {
        Vendor::factory()->count(25)->create(['status' => 'approved']);

        $results = $this->searchService->search([]);

        $this->assertEquals(20, $results->perPage());
        $this->assertEquals(25, $results->total());
    }

    public function test_loads_relationships()
    {
        $category = Category::factory()->create();
        $vendor = Vendor::factory()->create([
            'status' => 'approved',
            'category_id' => $category->id,
        ]);
        Service::factory()->create(['vendor_id' => $vendor->id]);

        $results = $this->searchService->search([]);

        $this->assertTrue($results->first()->relationLoaded('category'));
        $this->assertTrue($results->first()->relationLoaded('services'));
        $this->assertTrue($results->first()->relationLoaded('media'));
    }
}
