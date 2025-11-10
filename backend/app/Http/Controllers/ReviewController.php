<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|uuid|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:2000',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);

        // Verify user owns this booking
        if ($booking->customer_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if booking can be reviewed
        if (!$booking->canBeReviewed()) {
            return response()->json([
                'error' => 'This booking cannot be reviewed yet or has already been reviewed'
            ], 422);
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'customer_id' => auth()->id(),
            'vendor_id' => $booking->vendor_id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'status' => 'published',
        ]);

        return response()->json([
            'review' => $review->load('customer'),
            'message' => 'Review submitted successfully',
        ], 201);
    }

    public function respond(Request $request, string $id)
    {
        $validated = $request->validate([
            'response' => 'required|string|max:2000',
        ]);

        $review = Review::findOrFail($id);

        // Verify user is the vendor
        $vendor = auth()->user()->vendor;
        if (!$vendor || $review->vendor_id !== $vendor->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review->update([
            'response' => $validated['response'],
            'response_at' => now(),
        ]);

        return response()->json([
            'review' => $review,
            'message' => 'Response posted successfully',
        ]);
    }
}
