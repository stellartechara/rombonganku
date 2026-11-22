<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    protected $fillable = ['user_id', 'name', 'phone', 'vehicle_type', 'plate', 'capacity', 'is_self'];

    protected function casts(): array
    {
        return ['is_self' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookingVehicles(): HasMany
    {
        return $this->hasMany(BookingVehicle::class);
    }
}