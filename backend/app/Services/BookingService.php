<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Exception;

class BookingService
{
    public function __construct(
        private PaymentService $paymentService,
        private NotificationService $notificationService
    ) {}

    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            // 1. Check vendor availability
            $vendor = Vendor::findOrFail($data['vendor_id']);
            $service = Service::findOrFail($data['service_id']);

            if (!$this->checkAvailability($data['vendor_id'], $data['event_date'])) {
                throw new Exception('Vendor is not available on selected date');
            }

            // 2. Calculate pricing
            $total = $this->calculateTotal($service, $data);
            $commission = $total * 0.10; // 10% platform fee

            // 3. Create booking
            $booking = Booking::create([
                'booking_number' => $this->generateBookingNumber(),
                'customer_id' => auth()->id(),
                'vendor_id' => $data['vendor_id'],
                'service_id' => $data['service_id'],
                'event_date' => $data['event_date'],
                'event_time' => $data['event_time'] ?? null,
                'event_type' => $data['event_type'] ?? null,
                'guest_count' => $data['guest_count'] ?? null,
                'venue_name' => $data['venue_name'] ?? null,
                'venue_address' => $data['venue_address'] ?? null,
                'service_price' => $service->price_from,
                'extras_price' => 0.00,
                'total_price' => $total,
                'currency' => $service->currency,
                'commission_rate' => 10.00,
                'commission_amount' => $commission,
                'deposit_amount' => $total * 0.30, // 30% deposit
                'balance_amount' => $total * 0.70,
                'status' => 'pending',
                'special_requests' => $data['special_requests'] ?? null,
            ]);

            // 4. Send notifications
            $this->notificationService->sendBookingNotification($booking);

            return $booking->load(['customer', 'vendor', 'service']);
        });
    }

    public function confirmBooking(Booking $booking, string $paymentIntentId): Booking
    {
        return DB::transaction(function () use ($booking, $paymentIntentId) {
            $booking->update([
                'status' => 'confirmed',
                'deposit_paid' => true,
                'deposit_paid_at' => now(),
            ]);

            // Mark date as unavailable
            $this->markDateUnavailable($booking->vendor_id, $booking->event_date);

            $this->notificationService->sendBookingConfirmation($booking);

            return $booking;
        });
    }

    public function cancelBooking(Booking $booking, string $reason, string $userId): Booking
    {
        if (!$booking->canBeCancelled()) {
            throw new Exception('This booking cannot be cancelled');
        }

        return DB::transaction(function () use ($booking, $reason, $userId) {
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_by' => $userId,
                'cancelled_at' => now(),
            ]);

            // Release the date
            $this->releaseDateAvailability($booking->vendor_id, $booking->event_date);

            // Process refund if payment was made
            if ($booking->deposit_paid) {
                $this->paymentService->processRefund($booking);
            }

            $this->notificationService->sendCancellationNotification($booking);

            return $booking;
        });
    }

    private function checkAvailability(string $vendorId, string $date): bool
    {
        // Check if vendor marked date as unavailable
        $unavailable = DB::table('availability')
            ->where('vendor_id', $vendorId)
            ->where('date', $date)
            ->where('is_available', false)
            ->exists();

        if ($unavailable) {
            return false;
        }

        // Check if date is already booked
        $booked = Booking::where('vendor_id', $vendorId)
            ->where('event_date', $date)
            ->whereIn('status', ['confirmed', 'pending'])
            ->exists();

        return !$booked;
    }

    private function calculateTotal(Service $service, array $data): float
    {
        $total = $service->price_from;

        // Add per-person pricing if applicable
        if ($service->pricing_type === 'per_person' && isset($data['guest_count'])) {
            $total = $service->price_from * $data['guest_count'];
        }

        return $total;
    }

    private function generateBookingNumber(): string
    {
        $year = date('Y');
        $count = Booking::whereYear('created_at', $year)->count() + 1;
        return sprintf('BK-%s-%06d', $year, $count);
    }

    private function markDateUnavailable(string $vendorId, string $date): void
    {
        DB::table('availability')->updateOrInsert(
            ['vendor_id' => $vendorId, 'date' => $date],
            ['is_available' => false, 'reason' => 'Booked']
        );
    }

    private function releaseDateAvailability(string $vendorId, string $date): void
    {
        DB::table('availability')
            ->where('vendor_id', $vendorId)
            ->where('date', $date)
            ->delete();
    }
}
