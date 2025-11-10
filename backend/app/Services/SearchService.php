<?php

namespace App\Services;

use App\Models\Vendor;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    public function search(array $filters): LengthAwarePaginator
    {
        $query = Vendor::query()
            ->where('status', 'approved')
            ->with(['category', 'services', 'media' => fn($q) => $q->limit(5)]);

        // Category filter
        if (!empty($filters['category'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category']);
            });
        }

        // Location filter
        if (!empty($filters['city'])) {
            $query->where('city', 'ILIKE', '%' . $filters['city'] . '%');
        }

        // Price range filter
        if (!empty($filters['price_min']) || !empty($filters['price_max'])) {
            $query->whereHas('services', function ($q) use ($filters) {
                if (!empty($filters['price_min'])) {
                    $q->where('price_from', '>=', $filters['price_min']);
                }
                if (!empty($filters['price_max'])) {
                    $q->where('price_to', '<=', $filters['price_max']);
                }
            });
        }

        // Availability filter
        if (!empty($filters['date'])) {
            $query->whereDoesntHave('availability', function ($q) use ($filters) {
                $q->where('date', $filters['date'])
                  ->where('is_available', false);
            })->whereDoesntHave('bookings', function ($q) use ($filters) {
                $q->where('event_date', $filters['date'])
                  ->whereIn('status', ['confirmed', 'pending']);
            });
        }

        // Rating filter
        if (!empty($filters['min_rating'])) {
            $query->where('rating_average', '>=', $filters['min_rating']);
        }

        // Featured first
        $query->orderBy('featured', 'desc');

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'rating';
        switch ($sortBy) {
            case 'price':
                $query->join('services', 'vendors.id', '=', 'services.vendor_id')
                      ->orderBy('services.price_from', 'asc')
                      ->select('vendors.*')
                      ->distinct();
                break;
            case 'popularity':
                $query->orderBy('view_count', 'desc');
                break;
            case 'rating':
            default:
                $query->orderBy('rating_average', 'desc')
                      ->orderBy('rating_count', 'desc');
                break;
        }

        return $query->paginate(20);
    }

    public function searchByLocation(float $latitude, float $longitude, float $radius = 50): LengthAwarePaginator
    {
        // Search within radius (in km)
        $query = Vendor::query()
            ->where('status', 'approved')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw('*,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude)))) AS distance',
                [$latitude, $longitude, $latitude]
            )
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->with(['category', 'services', 'media']);

        return $query->paginate(20);
    }
}
