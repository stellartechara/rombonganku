<?php

namespace Tests\Feature;

use App\Models\BookingVehicle;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetIncomeTest extends TestCase
{
    use RefreshDatabase;

    private function makeVehicle(int $share, ?int $deposit): BookingVehicle
    {
        $user = User::factory()->create();

        $driver = $user->drivers()->create([
            'name' => 'Pak Sopir',
            'vehicle_type' => 'Elf',
            'capacity' => 15,
            'is_self' => true,
        ]);

        $booking = $user->bookings()->create([
            'customer_name' => 'Keluarga Budi',
            'customer_phone' => '0812345678',
            'trip_date' => now()->addWeek(),
            'passenger_count' => 12,
            'deal_price' => $share,
        ]);

        return $booking->vehicles()->create([
            'driver_id' => $driver->id,
            'share_price' => $share,
            'deposit' => $deposit,
        ]);
    }

    public function test_net_income_subtracts_deposit_and_driver_expenses(): void
    {
        $bv = $this->makeVehicle(3_200_000, 1_500_000);
        $bv->expenses()->create(['category' => 'BBM', 'amount' => 400_000, 'paid_by' => Expense::PAID_BY_DRIVER]);

        $this->assertSame(1_300_000, $bv->netIncome());
    }

    public function test_expenses_paid_by_customer_are_not_deducted(): void
    {
        $bv = $this->makeVehicle(3_200_000, 1_500_000);
        $bv->expenses()->create(['category' => 'Makan', 'amount' => 100_000, 'paid_by' => Expense::PAID_BY_CUSTOMER]);

        $this->assertSame(1_700_000, $bv->netIncome());
    }

    public function test_missing_deposit_is_treated_as_zero(): void
    {
        $bv = $this->makeVehicle(2_000_000, null);

        $this->assertSame(2_000_000, $bv->netIncome());
    }

    public function test_booking_gets_unique_tracking_code(): void
    {
        $a = $this->makeVehicle(1_000_000, null)->booking;
        $b = $this->makeVehicle(1_000_000, null)->booking;

        $this->assertSame(6, strlen($a->tracking_code));
        $this->assertNotSame($a->tracking_code, $b->tracking_code);
    }
}