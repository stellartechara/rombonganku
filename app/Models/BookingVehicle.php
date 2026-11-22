<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingVehicle extends Model
{
    protected $fillable = ['booking_id', 'driver_id', 'share_price', 'deposit'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }

    /** Total biaya yang ditanggung sopir (BBM, tol, dll). */
    public function driverExpenses(): int
    {
        return (int) $this->expenses()->where('paid_by', Expense::PAID_BY_DRIVER)->sum('amount');
    }

    /** Pendapatan bersih = bagian harga - setoran - biaya yang ditanggung sopir. */
    public function netIncome(): int
    {
        return $this->share_price - ($this->deposit ?? 0) - $this->driverExpenses();
    }
}