<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@rombonganku.test'],
            ['name' => 'Pak Sopir (Demo)', 'password' => 'password'],
        );

        // Aman dijalankan berulang: kalau data demo sudah ada, lewati.
        if ($user->bookings()->exists()) {
            return;
        }

        $me = $user->drivers()->create([
            'name' => 'Pak Sopir', 'phone' => '081234567800', 'vehicle_type' => 'Elf',
            'plate' => 'H 1234 AB', 'capacity' => 15, 'is_self' => true,
        ]);
        $rekan = $user->drivers()->create([
            'name' => 'Pak Ceming', 'phone' => '081234567801', 'vehicle_type' => 'Elf',
            'plate' => 'H 5678 CD', 'capacity' => 15,
        ]);
        $user->drivers()->create([
            'name' => 'Pak Joko', 'phone' => '081234567802', 'vehicle_type' => 'Bus medium',
            'plate' => 'H 9012 EF', 'capacity' => 31,
        ]);

        $month = now()->startOfMonth();

        // Permintaan baru
        $user->bookings()->create([
            'customer_name' => 'Pengajian Ibu-Ibu RT 05', 'customer_phone' => '081311110001',
            'trip_date' => now()->addDays(10), 'days' => 1, 'passenger_count' => 25,
            'status' => BookingStatus::Permintaan,
            'notes' => "Ziarah ke Demak dan Kudus.\nPerkiraan jarak: 120 km PP",
        ]);

        // Sedang nego
        $user->bookings()->create([
            'customer_name' => 'Study Tour SMP Bakti', 'customer_phone' => '081311110002',
            'trip_date' => now()->addDays(14), 'days' => 2, 'passenger_count' => 45,
            'status' => BookingStatus::Nego,
            'notes' => "Tujuan Yogyakarta, minta harga bus.\nPerkiraan jarak: 450 km PP",
        ]);

        // Deal, dengan dua kendaraan dan penumpang
        $deal = $user->bookings()->create([
            'customer_name' => 'Keluarga Besar Pak Budi', 'customer_phone' => '081311110003',
            'trip_date' => now()->addDays(5), 'days' => 2, 'passenger_count' => 28,
            'status' => BookingStatus::Deal, 'deal_price' => 6_000_000,
            'notes' => 'Wisata ke Dieng, menginap satu malam.',
        ]);
        $mine = $deal->vehicles()->create(['driver_id' => $me->id, 'share_price' => 3_200_000, 'deposit' => 1_500_000]);
        $theirs = $deal->vehicles()->create(['driver_id' => $rekan->id, 'share_price' => 2_800_000]);

        $deal->stops()->createMany([
            ['time' => '05:30', 'place' => 'Jemput di rumah Pak Budi', 'position' => 1],
            ['time' => '08:00', 'place' => 'Sarapan di Temanggung', 'position' => 2],
            ['time' => '11:00', 'place' => 'Kawah Sikidang, Dieng', 'position' => 3],
            ['time' => '16:00', 'place' => 'Penginapan', 'position' => 4],
        ]);

        foreach (['Budi', 'Siti', 'Andi', 'Rina', 'Dewi', 'Agus', 'Wati', 'Hasan'] as $i => $name) {
            $deal->passengers()->create([
                'name' => $name,
                'booking_vehicle_id' => $i < 4 ? $mine->id : $theirs->id,
                'boarded' => $i % 3 === 0,
            ]);
        }

        // Dua trip selesai bulan ini, supaya rekap terisi
        $this->finished($user, $me, 'Rombongan Wisata Pantai', '081311110004',
            $month->copy()->addDays(2), 4_500_000, 1_800_000,
            [['BBM', 650_000, 'driver'], ['Tol', 150_000, 'driver'], ['Makan', 120_000, 'customer']]);

        $this->finished($user, $me, 'Reuni SMA 2005', '081311110005',
            $month->copy()->addDays(9), 3_000_000, 1_200_000,
            [['BBM', 400_000, 'driver']]);

        // Dibatalkan
        $user->bookings()->create([
            'customer_name' => 'Kantor Notaris Santoso', 'customer_phone' => '081311110006',
            'trip_date' => now()->addDays(3), 'days' => 1, 'passenger_count' => 12,
            'status' => BookingStatus::Batal, 'notes' => 'Dibatalkan pelanggan.',
        ]);
    }

    private function finished(User $user, $driver, string $name, string $phone, $date, int $price, int $deposit, array $expenses): void
    {
        $booking = $user->bookings()->create([
            'customer_name' => $name, 'customer_phone' => $phone,
            'trip_date' => $date, 'days' => 1, 'passenger_count' => 14,
            'status' => BookingStatus::Selesai, 'deal_price' => $price,
        ]);

        $bv = $booking->vehicles()->create([
            'driver_id' => $driver->id, 'share_price' => $price, 'deposit' => $deposit,
        ]);

        foreach ($expenses as [$category, $amount, $paidBy]) {
            $bv->expenses()->create(['category' => $category, 'amount' => $amount, 'paid_by' => $paidBy]);
        }
    }
}