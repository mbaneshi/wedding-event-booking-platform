<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Vendor;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index()
    {
        $favorites = Favorite::where('user_id', auth()->id())
            ->with('vendor.category', 'vendor.media')
            ->get();

        return response()->json($favorites);
    }

    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|uuid|exists:vendors,id',
        ]);

        $vendor = Vendor::findOrFail($validated['vendor_id']);

        $favorite = Favorite::where('user_id', auth()->id())
            ->where('vendor_id', $vendor->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $message = 'Removed from favorites';
            $isFavorited = false;
        } else {
            Favorite::create([
                'user_id' => auth()->id(),
                'vendor_id' => $vendor->id,
            ]);
            $message = 'Added to favorites';
            $isFavorited = true;
        }

        return response()->json([
            'message' => $message,
            'is_favorited' => $isFavorited,
        ]);
    }
}
