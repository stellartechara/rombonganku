<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passenger extends Model
{
    protected $fillable = ['booking_id', 'booking_vehicle_id', 'name', 'boarded'];

    protected function casts(): array
    {
        return ['boarded' => 'boolean'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingVehicle(): BelongsTo
    {
        return $this->belongsTo(BookingVehicle::class);
    }
}