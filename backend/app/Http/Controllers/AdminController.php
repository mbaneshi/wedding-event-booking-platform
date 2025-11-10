<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_vendors' => Vendor::count(),
            'active_vendors' => Vendor::where('status', 'approved')->count(),
            'pending_vendors' => Vendor::where('status', 'pending')->count(),
            'total_bookings' => Booking::count(),
            'total_revenue' => Payment::where('status', 'succeeded')->sum('amount'),
            'total_commission' => Booking::where('status', 'completed')->sum('commission_amount'),
            'total_customers' => User::where('role', 'customer')->count(),
        ];

        return response()->json($stats);
    }

    public function pendingVendors()
    {
        $vendors = Vendor::where('status', 'pending')
            ->with(['user', 'category'])
            ->latest()
            ->paginate(20);

        return response()->json($vendors);
    }

    public function approveVendor(string $id)
    {
        $vendor = Vendor::findOrFail($id);

        $vendor->update([
            'status' => 'approved',
            'verified' => true,
        ]);

        // Send approval email to vendor
        app(\App\Services\EmailService::class)->sendVendorApprovalEmail($vendor);

        return response()->json([
            'vendor' => $vendor,
            'message' => 'Vendor approved successfully',
        ]);
    }

    public function rejectVendor(Request $request, string $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $vendor = Vendor::findOrFail($id);

        $vendor->update([
            'status' => 'rejected',
        ]);

        // Send rejection email to vendor with reason
        app(\App\Services\EmailService::class)->sendVendorRejectionEmail($vendor, $validated['reason']);

        return response()->json([
            'vendor' => $vendor,
            'message' => 'Vendor rejected',
        ]);
    }

    public function allBookings(Request $request)
    {
        $query = Booking::with(['customer', 'vendor', 'service']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $bookings = $query->latest()->paginate(50);

        return response()->json($bookings);
    }

    public function analytics()
    {
        // Revenue by month (last 12 months)
        $revenueByMonth = Payment::where('status', 'succeeded')
            ->select(
                DB::raw('DATE_TRUNC(\'month\', created_at) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Bookings by status
        $bookingsByStatus = Booking::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        // Top vendors by bookings
        $topVendors = Vendor::withCount('bookings')
            ->orderBy('bookings_count', 'desc')
            ->limit(10)
            ->get();

        // Category distribution
        $categoryStats = Vendor::select('categories.name', DB::raw('COUNT(*) as count'))
            ->join('categories', 'vendors.category_id', '=', 'categories.id')
            ->where('vendors.status', 'approved')
            ->groupBy('categories.name')
            ->get();

        return response()->json([
            'revenue_by_month' => $revenueByMonth,
            'bookings_by_status' => $bookingsByStatus,
            'top_vendors' => $topVendors,
            'category_stats' => $categoryStats,
        ]);
    }
}
