<?php

namespace App\Support;

class TripEstimator
{
    /** Tarif contoh (rupiah). Ganti sesuai harga asli bapak lu. */
    public const VEHICLES = [
        'mobil' => ['label' => 'Mobil keluarga', 'capacity' => 6, 'per_day' => 600_000, 'per_km' => 1_800],
        'elf' => ['label' => 'Elf', 'capacity' => 15, 'per_day' => 900_000, 'per_km' => 2_500],
        'bus_medium' => ['label' => 'Bus medium', 'capacity' => 31, 'per_day' => 1_500_000, 'per_km' => 4_000],
        'bus_besar' => ['label' => 'Bus besar', 'capacity' => 50, 'per_day' => 2_200_000, 'per_km' => 5_500],
    ];

    /**
     * @return array{vehicle: string, count: int, total: int, per_person: int}
     */
    public static function estimate(int $passengers, int $days, int $km): array
    {
        $passengers = max(1, $passengers);
        $days = max(1, $days);
        $km = max(1, $km);

        $key = collect(self::VEHICLES)
            ->filter(fn ($v) => $v['capacity'] >= $passengers)
            ->keys()
            ->first();

        $count = 1;

        if ($key === null) {
            $key = 'bus_besar';
            $count = (int) ceil($passengers / self::VEHICLES[$key]['capacity']);
        }

        $v = self::VEHICLES[$key];
        $total = ($days * $v['per_day'] + $km * $v['per_km']) * $count;

        return [
            'vehicle' => $v['label'],
            'count' => $count,
            'total' => $total,
            'per_person' => (int) ceil($total / $passengers),
        ];
    }
}