<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'business_name',
        'slug',
        'description',
        'logo_url',
        'cover_image_url',
        'category_id',
        'phone',
        'email',
        'website',
        'address',
        'city',
        'region',
        'country',
        'latitude',
        'longitude',
        'years_in_business',
        'license_number',
        'status',
        'verified',
        'featured',
        'rating_average',
        'rating_count',
        'view_count',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'featured' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'rating_average' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class)->orderBy('sort_order');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->where('status', 'published');
    }

    public function availability()
    {
        return $this->hasMany(Availability::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function updateRating()
    {
        $reviews = $this->reviews;
        $this->rating_count = $reviews->count();
        $this->rating_average = $reviews->avg('rating');
        $this->save();
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
}
