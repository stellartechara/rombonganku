<?php

use App\Enums\BookingStatus;
use App\Models\BookingVehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Dashboard sopir')] class extends Component {
    #[Url]
    public string $filter = 'aktif';

    #[Locked]
    public string $month = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    private function monthStart(): Carbon
    {
        return Carbon::createFromFormat('!Y-m', $this->month)->startOfMonth();
    }

    public function shiftMonth(int $delta): void
    {
        $this->month = $this->monthStart()->addMonths($delta)->format('Y-m');
        unset($this->recap);
    }

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

    #[Computed]
    public function recap(): array
    {
        $start = $this->monthStart();
        $end = $start->copy()->endOfMonth();

        $rows = BookingVehicle::query()
            ->with(['booking', 'expenses'])
            ->whereHas('driver', fn ($q) => $q->where('is_self', true))
            ->whereHas('booking', fn ($q) => $q
                ->where('user_id', Auth::id())
                ->where('status', BookingStatus::Selesai->value)
                ->whereBetween('trip_date', [$start, $end]))
            ->get()
            ->sortBy('booking.trip_date')
            ->values();

        return [
            'rows' => $rows,
            'share' => $rows->sum('share_price'),
            'deposit' => $rows->sum(fn ($r) => $r->deposit ?? 0),
            'expenses' => $rows->sum(fn ($r) => $r->driverExpenses()),
            'net' => $rows->sum(fn ($r) => $r->netIncome()),
        ];
    }
}; ?>

@php
    $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    $filters = [
        'aktif' => 'Aktif', 'permintaan' => 'Permintaan', 'nego' => 'Nego', 'deal' => 'Deal',
        'berjalan' => 'Berjalan', 'selesai' => 'Selesai', 'batal' => 'Batal', 'semua' => 'Semua',
    ];
    $recap = $this->recap;
    $monthLabel = \Illuminate\Support\Carbon::createFromFormat('!Y-m', $month)->translatedFormat('F Y');
@endphp

<div class="space-y-8">
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

    <section class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <flux:heading level="2">Rekap {{ $monthLabel }}</flux:heading>
            <div class="flex gap-1">
                <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="shiftMonth(-1)" aria-label="Bulan sebelumnya" />
                <flux:button size="sm" variant="ghost" icon="chevron-right" wire:click="shiftMonth(1)" aria-label="Bulan berikutnya" />
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:text class="text-sm">Pendapatan bersih ({{ $recap['rows']->count() }} trip selesai)</flux:text>
            <div class="text-4xl font-bold">{{ $rp($recap['net']) }}</div>

            <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-3">
                <div><dt class="text-zinc-500">Bagian harga</dt><dd class="font-semibold">{{ $rp($recap['share']) }}</dd></div>
                <div><dt class="text-zinc-500">Setoran</dt><dd class="font-semibold">{{ $rp($recap['deposit']) }}</dd></div>
                <div><dt class="text-zinc-500">Biaya ditanggung sopir</dt><dd class="font-semibold">{{ $rp($recap['expenses']) }}</dd></div>
            </dl>
        </div>

        @if ($recap['rows']->isNotEmpty())
            <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                @foreach ($recap['rows'] as $r)
                    <a href="{{ route('bookings.show', $r->booking_id) }}" wire:navigate wire:key="r-{{ $r->id }}"
                        class="flex items-center justify-between gap-3 p-4 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <span>
                            <span class="font-medium">{{ $r->booking->customer_name }}</span>
                            <span class="text-zinc-500">&middot; {{ $r->booking->trip_date->translatedFormat('d M') }}</span>
                        </span>
                        <span class="font-semibold">{{ $rp($r->netIncome()) }}</span>
                    </a>
                @endforeach
            </div>
        @else
            <flux:text class="text-sm">Belum ada trip selesai di bulan ini. Trip dihitung setelah statusnya diubah ke Selesai.</flux:text>
        @endif
    </section>

    <section class="space-y-4">
        <flux:heading level="2">Pesanan</flux:heading>

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
    </section>
</div>