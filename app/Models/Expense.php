<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const PAID_BY_DRIVER = 'driver';
    public const PAID_BY_CUSTOMER = 'customer';

    protected $fillable = ['booking_vehicle_id', 'category', 'amount', 'paid_by', 'note'];

    public function bookingVehicle(): BelongsTo
    {
        return $this->belongsTo(BookingVehicle::class);
    }
}