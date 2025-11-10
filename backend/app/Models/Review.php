<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'booking_id',
        'customer_id',
        'vendor_id',
        'rating',
        'title',
        'comment',
        'response',
        'response_at',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
        'response_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    protected static function booted()
    {
        static::created(function ($review) {
            $review->vendor->updateRating();
        });

        static::deleted(function ($review) {
            $review->vendor->updateRating();
        });
    }
}
