<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Permintaan = 'permintaan';
    case Nego = 'nego';
    case Deal = 'deal';
    case Berjalan = 'berjalan';
    case Selesai = 'selesai';
    case Batal = 'batal';

    public function label(): string
    {
        return match ($this) {
            self::Permintaan => 'Permintaan masuk',
            self::Nego => 'Sedang nego',
            self::Deal => 'Deal',
            self::Berjalan => 'Sedang berjalan',
            self::Selesai => 'Selesai',
            self::Batal => 'Batal',
        };
    }

        public function color(): string
    {
        return match ($this) {
            self::Permintaan => 'amber',
            self::Nego => 'blue',
            self::Deal => 'green',
            self::Berjalan => 'purple',
            self::Selesai => 'zinc',
            self::Batal => 'red',
        };
    }
}