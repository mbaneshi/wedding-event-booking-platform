<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private PaymentService $paymentService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Booking::query();

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        } elseif ($user->isVendor() && $user->vendor) {
            $query->where('vendor_id', $user->vendor->id);
        }

        $bookings = $query->with(['customer', 'vendor', 'service'])
            ->latest()
            ->paginate(20);

        return response()->json($bookings);
    }

    public function show(string $id)
    {
        $booking = Booking::with([
            'customer',
            'vendor',
            'service',
            'payments',
            'review',
        ])->findOrFail($id);

        // Check authorization
        $user = auth()->user();
        if ($booking->customer_id !== $user->id &&
            $booking->vendor->user_id !== $user->id &&
            !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($booking);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|uuid|exists:vendors,id',
            'service_id' => 'required|uuid|exists:services,id',
            'event_date' => 'required|date|after:today',
            'event_time' => 'nullable|date_format:H:i',
            'event_type' => 'nullable|string|max:100',
            'guest_count' => 'nullable|integer|min:1',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'special_requests' => 'nullable|string',
        ]);

        try {
            $booking = $this->bookingService->createBooking($validated);

            // Create payment intent for deposit
            $paymentIntent = $this->paymentService->createPaymentIntent(
                $booking,
                $booking->deposit_amount,
                'deposit'
            );

            return response()->json([
                'booking' => $booking,
                'payment_intent' => $paymentIntent,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }

    public function cancel(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $booking = $this->bookingService->cancelBooking(
                $booking,
                $validated['reason'],
                auth()->id()
            );

            return response()->json([
                'booking' => $booking,
                'message' => 'Booking cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }

    public function confirm(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        // Only vendor can confirm
        if ($booking->vendor->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $booking->update(['status' => 'confirmed']);

        return response()->json([
            'booking' => $booking,
            'message' => 'Booking confirmed successfully',
        ]);
    }

    public function complete(string $id)
    {
        $booking = Booking::findOrFail($id);

        // Only vendor can mark as complete
        if ($booking->vendor->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $booking->update(['status' => 'completed']);

        return response()->json([
            'booking' => $booking,
            'message' => 'Booking marked as completed',
        ]);
    }

    public function getMyBookings(Request $request)
    {
        $user = auth()->user();
        $status = $request->input('status');

        $query = Booking::query();

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        } elseif ($user->isVendor() && $user->vendor) {
            $query->where('vendor_id', $user->vendor->id);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $bookings = $query->with(['vendor', 'service'])
            ->latest()
            ->get();

        return response()->json($bookings);
    }
}
