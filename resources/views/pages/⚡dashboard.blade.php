<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Dashboard sopir')] class extends Component {
    #[Url]
    public string $filter = 'aktif';

    #[Computed]
    public function counts(): array
    {
        return Auth::user()->bookings()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    #[Computed]
    public function bookings()
    {
        $query = Auth::user()->bookings()
            ->withCount('passengers')
            ->orderBy('trip_date');

        if ($this->filter === 'aktif') {
            $query->whereNotIn('status', ['selesai', 'batal']);
        } elseif ($this->filter !== 'semua') {
            $query->where('status', $this->filter);
        }

        return $query->get();
    }
}; ?>

@php
    $filters = [
        'aktif' => 'Aktif', 'permintaan' => 'Permintaan', 'nego' => 'Nego', 'deal' => 'Deal',
        'berjalan' => 'Berjalan', 'selesai' => 'Selesai', 'batal' => 'Batal', 'semua' => 'Semua',
    ];
@endphp

<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <flux:heading size="xl" level="1">Dashboard sopir</flux:heading>
        <flux:button href="{{ route('drivers.index') }}" wire:navigate size="sm" icon="truck">Rekan dan kendaraan</flux:button>
    </div>
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-sm">Permintaan baru</flux:text>
            <div class="text-3xl font-bold">{{ $this->counts['permintaan'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-sm">Sudah deal</flux:text>
            <div class="text-3xl font-bold">{{ $this->counts['deal'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-sm">Semua pesanan</flux:text>
            <div class="text-3xl font-bold">{{ array_sum($this->counts) }}</div>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach ($filters as $key => $label)
            <flux:button size="sm" :variant="$filter === $key ? 'primary' : 'ghost'"
                wire:click="$set('filter', '{{ $key }}')">{{ $label }}</flux:button>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($this->bookings as $b)
            <a href="{{ route('bookings.show', $b) }}" wire:navigate wire:key="b-{{ $b->id }}"
                class="block rounded-xl border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-semibold">{{ $b->customer_name }}</div>
                        <div class="text-sm text-zinc-500">
                            {{ $b->trip_date->translatedFormat('d M Y') }} &middot; {{ $b->days }} hari
                            &middot; {{ $b->passenger_count }} orang
                        </div>
                    </div>
                    <flux:badge :color="$b->status->color()">{{ $b->status->label() }}</flux:badge>
                </div>
                <div class="mt-2 text-sm text-zinc-500">
                    @if ($b->deal_price)
                        Deal Rp {{ number_format($b->deal_price, 0, ',', '.') }} &middot;
                    @endif
                    {{ $b->passengers_count }} nama penumpang tercatat &middot; kode {{ $b->tracking_code }}
                </div>
            </a>
        @empty
            <flux:text>Belum ada pesanan di kategori ini.</flux:text>
        @endforelse
    </div>
</div>