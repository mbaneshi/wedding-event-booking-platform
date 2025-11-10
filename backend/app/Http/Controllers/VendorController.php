<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VendorController extends Controller
{
    public function __construct(
        private SearchService $searchService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'category',
            'city',
            'price_min',
            'price_max',
            'date',
            'min_rating',
            'sort_by',
        ]);

        $vendors = $this->searchService->search($filters);

        return response()->json($vendors);
    }

    public function show(string $id)
    {
        $vendor = Vendor::with([
            'category',
            'services',
            'media',
            'reviews' => fn($q) => $q->latest()->limit(10),
            'reviews.customer',
        ])
        ->findOrFail($id);

        // Increment view count
        $vendor->incrementViewCount();

        return response()->json($vendor);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|uuid|exists:categories,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'city' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'years_in_business' => 'nullable|integer|min:0',
            'license_number' => 'nullable|string|max:100',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['slug'] = Str::slug($validated['business_name']) . '-' . Str::random(6);
        $validated['status'] = 'pending';

        $vendor = Vendor::create($validated);

        return response()->json($vendor, 201);
    }

    public function update(Request $request, string $id)
    {
        $vendor = Vendor::findOrFail($id);

        // Check authorization
        if ($vendor->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'business_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'city' => 'sometimes|string|max:100',
            'region' => 'nullable|string|max:100',
            'years_in_business' => 'nullable|integer|min:0',
        ]);

        $vendor->update($validated);

        return response()->json($vendor);
    }

    public function getServices(string $id)
    {
        $vendor = Vendor::findOrFail($id);
        $services = $vendor->services()->where('is_active', true)->get();

        return response()->json($services);
    }

    public function getReviews(string $id, Request $request)
    {
        $vendor = Vendor::findOrFail($id);
        $reviews = $vendor->reviews()
            ->with('customer')
            ->latest()
            ->paginate(20);

        return response()->json($reviews);
    }

    public function getAvailability(string $id, Request $request)
    {
        $vendor = Vendor::findOrFail($id);
        $month = $request->input('month', now()->format('Y-m'));

        $availability = $vendor->availability()
            ->whereRaw("to_char(date, 'YYYY-MM') = ?", [$month])
            ->get();

        $bookedDates = $vendor->bookings()
            ->whereIn('status', ['confirmed', 'pending'])
            ->whereRaw("to_char(event_date, 'YYYY-MM') = ?", [$month])
            ->pluck('event_date')
            ->toArray();

        return response()->json([
            'availability' => $availability,
            'booked_dates' => $bookedDates,
        ]);
    }
}
