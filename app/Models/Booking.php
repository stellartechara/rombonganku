<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $fillable = [
        'user_id', 'customer_name', 'customer_phone', 'trip_date', 'days',
        'passenger_count', 'status', 'deal_price', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'trip_date' => 'date',
            'status' => BookingStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            do {
                $code = Str::upper(Str::random(6));
            } while (static::where('tracking_code', $code)->exists());

            $booking->tracking_code = $code;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(BookingVehicle::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(Stop::class)->orderBy('position');
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }
}