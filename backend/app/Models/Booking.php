<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'booking_number',
        'customer_id',
        'vendor_id',
        'service_id',
        'event_date',
        'event_time',
        'event_type',
        'guest_count',
        'venue_name',
        'venue_address',
        'service_price',
        'extras_price',
        'total_price',
        'currency',
        'commission_rate',
        'commission_amount',
        'deposit_amount',
        'deposit_paid',
        'deposit_paid_at',
        'balance_amount',
        'balance_paid',
        'balance_paid_at',
        'status',
        'special_requests',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'event_date' => 'date',
        'deposit_paid' => 'boolean',
        'balance_paid' => 'boolean',
        'deposit_paid_at' => 'datetime',
        'balance_paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'service_price' => 'decimal:2',
        'extras_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now())
                     ->whereIn('status', ['confirmed', 'pending']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'])
               && $this->event_date > now()->addDays(7);
    }

    public function canBeReviewed(): bool
    {
        return $this->status === 'completed'
               && !$this->review()->exists()
               && $this->event_date < now();
    }
}
