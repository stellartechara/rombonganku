<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MonthlyRecapTest extends TestCase
{
    use RefreshDatabase;

    private function trip(User $user, BookingStatus $status, string $date, int $share, int $deposit, int $fuel, bool $self = true)
    {
        $driver = $user->drivers()->create([
            'name' => 'Sopir', 'vehicle_type' => 'Elf', 'capacity' => 15, 'is_self' => $self,
        ]);

        $booking = $user->bookings()->create([
            'customer_name' => 'Tes', 'customer_phone' => '0812345678',
            'trip_date' => $date, 'passenger_count' => 10, 'status' => $status,
            'deal_price' => $share,
        ]);

        $bv = $booking->vehicles()->create([
            'driver_id' => $driver->id, 'share_price' => $share, 'deposit' => $deposit,
        ]);

        $bv->expenses()->create(['category' => 'BBM', 'amount' => $fuel, 'paid_by' => Expense::PAID_BY_DRIVER]);

        return $bv;
    }

    public function test_recap_counts_only_finished_trips_of_own_vehicle_in_the_month(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->trip($user, BookingStatus::Selesai, now()->startOfMonth()->toDateString(), 3_000_000, 1_000_000, 200_000);
        $this->trip($user, BookingStatus::Selesai, now()->endOfMonth()->toDateString(), 2_000_000, 500_000, 100_000);
        $this->trip($user, BookingStatus::Deal, now()->toDateString(), 9_000_000, 0, 0);
        $this->trip($user, BookingStatus::Selesai, now()->toDateString(), 8_000_000, 0, 0, self: false);
        $this->trip($user, BookingStatus::Selesai, now()->subMonthNoOverflow()->toDateString(), 7_000_000, 0, 0);
        $this->trip($other, BookingStatus::Selesai, now()->toDateString(), 6_000_000, 0, 0);

        $this->actingAs($user);

        $recap = Livewire::test('pages::dashboard')->instance()->recap();

        $this->assertSame(2, $recap['rows']->count());
        $this->assertSame(5_000_000, $recap['share']);
        $this->assertSame(1_500_000, $recap['deposit']);
        $this->assertSame(300_000, $recap['expenses']);
        $this->assertSame(3_200_000, $recap['net']);
    }
}