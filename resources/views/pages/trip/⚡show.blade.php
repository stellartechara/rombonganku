<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Status trip')] class extends Component {
    public Booking $booking;
    public string $names = '';

    public function mount(string $code): void
    {
        $this->booking = Booking::where('tracking_code', Str::upper($code))->firstOrFail();
    }

    public function addPassengers(): void
    {
        $this->validate(['names' => 'required|string|max:2000']);

        if (in_array($this->booking->status, [BookingStatus::Selesai, BookingStatus::Batal], true)) {
            return;
        }

        $list = collect(preg_split('/[\n,]+/', $this->names))
            ->map(fn ($n) => Str::limit(trim($n), 100, ''))
            ->filter()
            ->unique()
            ->take(100);

        foreach ($list as $name) {
            $this->booking->passengers()->create(['name' => $name]);
        }

        $this->names = '';
    }
}; ?>

@php
    $steps = [BookingStatus::Permintaan, BookingStatus::Nego, BookingStatus::Deal, BookingStatus::Berjalan, BookingStatus::Selesai];
    $current = array_search($booking->status, $steps, true);
    $closed = in_array($booking->status, [BookingStatus::Selesai, BookingStatus::Batal], true);
    $passengers = $booking->passengers()->orderBy('id')->get();
@endphp

<div class="mx-auto max-w-2xl space-y-8">
    <div>
        <flux:text class="text-sm">Kode pesanan</flux:text>
        <flux:heading size="xl" level="1">{{ $booking->tracking_code }}</flux:heading>
        <flux:text class="mt-1">Simpan halaman ini. Kamu bisa membukanya lagi untuk melihat perkembangan.</flux:text>
    </div>

    @if ($booking->status === BookingStatus::Batal)
        <flux:badge color="red">Dibatalkan</flux:badge>
    @else
        <ol class="space-y-2">
            @foreach ($steps as $i => $step)
                <li class="flex items-center gap-3">
                    <span @class([
                        'flex size-7 items-center justify-center rounded-full text-xs font-bold',
                        'bg-green-600 text-white' => $i <= $current,
                        'bg-zinc-200 text-zinc-500 dark:bg-zinc-700' => $i > $current,
                    ])>{{ $i + 1 }}</span>
                    <span @class(['font-semibold' => $i === $current, 'text-zinc-500' => $i > $current])>
                        {{ $step->label() }}
                    </span>
                </li>
            @endforeach
        </ol>
    @endif

    <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-zinc-500">Atas nama</dt><dd>{{ $booking->customer_name }}</dd></div>
            <div><dt class="text-zinc-500">Tanggal berangkat</dt><dd>{{ $booking->trip_date->translatedFormat('l, d F Y') }}</dd></div>
            <div><dt class="text-zinc-500">Lama</dt><dd>{{ $booking->days }} hari</dd></div>
            <div><dt class="text-zinc-500">Jumlah penumpang</dt><dd>{{ $booking->passenger_count }} orang</dd></div>
            @if ($booking->deal_price)
                <div><dt class="text-zinc-500">Harga disepakati</dt><dd>Rp {{ number_format($booking->deal_price, 0, ',', '.') }}</dd></div>
            @endif
        </dl>
    </div>

    @if ($booking->stops->isNotEmpty())
        <div>
            <flux:heading level="2">Rute dan jadwal</flux:heading>
            <ul class="mt-3 space-y-2">
                @foreach ($booking->stops as $stop)
                    <li class="flex gap-3 text-sm">
                        <span class="w-14 text-zinc-500">{{ $stop->time ? substr($stop->time, 0, 5) : '-' }}</span>
                        <span>{{ $stop->place }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div>
        <flux:heading level="2">Daftar penumpang</flux:heading>
        <flux:text class="text-sm">{{ $passengers->count() }} dari {{ $booking->passenger_count }} orang tercatat</flux:text>

        @if ($passengers->isNotEmpty())
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($passengers as $p)
                    <flux:badge>{{ $p->name }}</flux:badge>
                @endforeach
            </div>
        @endif

        @unless ($closed)
            <form wire:submit="addPassengers" class="mt-4 space-y-3">
                <flux:textarea wire:model="names" rows="3"
                    label="Tambah nama (pisahkan dengan baris baru atau koma)" />
                <flux:button type="submit" size="sm">Tambah nama</flux:button>
            </form>
        @endunless
    </div>
</div>