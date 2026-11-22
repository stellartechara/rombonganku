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
    <section class="relative overflow-hidden rounded-[1.75rem] bg-[#142b45] px-6 py-7 text-white shadow-xl shadow-slate-950/15 sm:px-8 sm:py-9">
        <div class="absolute -right-14 -top-32 size-80 rounded-full border border-white/10"></div>
        <div class="absolute -right-2 -top-24 size-64 rounded-full border border-white/10"></div>
        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-slate-200/75">{{ now()->translatedFormat('l, d F Y') }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Halo, {{ auth()->user()->name }} <span aria-hidden="true">👋</span></h1>
                <p class="mt-2 max-w-xl text-sm leading-6 text-slate-100/75 sm:text-base">Pantau pesanan dan atur perjalananmu dari satu tempat.</p>
            </div>
            <flux:button href="{{ route('drivers.index') }}" wire:navigate variant="filled" icon="truck" class="relative border-white/20 bg-white/10 text-white hover:bg-white/20">
                Rekan & kendaraan
            </flux:button>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-zinc-500">Permintaan baru</p>
                <span class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300"><flux:icon name="inbox-arrow-down" class="size-5" /></span>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <p class="text-3xl font-semibold tracking-tight">{{ $this->counts['permintaan'] ?? 0 }}</p>
                <span class="text-xs text-zinc-500">menunggu respons</span>
            </div>
        </div>
        <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-zinc-500">Siap berangkat</p>
                <span class="flex size-10 items-center justify-center rounded-xl bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-300"><flux:icon name="check-badge" class="size-5" /></span>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <p class="text-3xl font-semibold tracking-tight">{{ $this->counts['deal'] ?? 0 }}</p>
                <span class="text-xs text-zinc-500">sudah deal</span>
            </div>
        </div>
        <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-zinc-500">Total pesanan</p>
                <span class="flex size-10 items-center justify-center rounded-xl bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300"><flux:icon name="clipboard-document-list" class="size-5" /></span>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <p class="text-3xl font-semibold tracking-tight">{{ array_sum($this->counts) }}</p>
                <span class="text-xs text-zinc-500">sepanjang waktu</span>
            </div>
        </div>
    </section>

    <section class="grid items-start gap-8 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.8fr)]">
        <div class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-800 dark:text-blue-300">Aktivitas</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight">Pesanan perjalanan</h2>
                </div>
                <span class="text-xs text-zinc-500">{{ $this->bookings->count() }} pesanan ditampilkan</span>
            </div>

            <div class="flex gap-2 overflow-x-auto pb-2">
                @foreach ($filters as $key => $label)
                    <button type="button" wire:click="$set('filter', '{{ $key }}')"
                        @class([
                            'shrink-0 rounded-full px-4 py-2 text-sm font-medium transition',
                            'bg-[#1e3a5f] text-white shadow-sm' => $filter === $key,
                            'border border-zinc-200 bg-white text-zinc-600 hover:border-blue-300 hover:text-blue-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300' => $filter !== $key,
                        ])>
                        {{ $label }}
                        @if ($key === 'permintaan' && ($this->counts['permintaan'] ?? 0) > 0)
                            <span class="ml-1 rounded-full bg-white/20 px-1.5 py-0.5 text-[10px]">{{ $this->counts['permintaan'] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="space-y-3">
                @forelse ($this->bookings as $b)
                    <a href="{{ route('bookings.show', $b) }}" wire:navigate wire:key="b-{{ $b->id }}"
                        class="group block rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md sm:p-5 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-blue-900">
                        <div class="flex items-start gap-4">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                <flux:icon name="user-group" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <h3 class="font-semibold group-hover:text-blue-800 dark:group-hover:text-blue-300">{{ $b->customer_name }}</h3>
                                        <p class="mt-1 text-sm text-zinc-500">{{ $b->trip_date->translatedFormat('d M Y') }} <span class="px-1">·</span> {{ $b->days }} hari <span class="px-1">·</span> {{ $b->passenger_count }} orang</p>
                                    </div>
                                    <flux:badge :color="$b->status->color()">{{ $b->status->label() }}</flux:badge>
                                </div>
                                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-zinc-100 pt-3 text-xs text-zinc-500 dark:border-zinc-800">
                                    <span class="inline-flex items-center gap-1.5"><flux:icon name="users" class="size-4" />{{ $b->passengers_count }} nama tercatat</span>
                                    <span>Kode {{ $b->tracking_code }}</span>
                                    @if ($b->deal_price)
                                        <span class="font-medium text-zinc-700 dark:text-zinc-300">Deal {{ $rp($b->deal_price) }}</span>
                                    @endif
                                    <span class="ml-auto inline-flex items-center gap-1 font-semibold text-blue-800 dark:text-blue-300">Detail <flux:icon name="arrow-up-right" class="size-3.5 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5" /></span>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-300 bg-white/70 px-6 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900/60">
                        <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800"><flux:icon name="inbox" class="size-6" /></span>
                        <h3 class="mt-4 font-semibold">Belum ada pesanan di kategori ini</h3>
                        <p class="mt-1 text-sm text-zinc-500">Pesanan baru akan muncul di sini saat pelanggan mengajukan perjalanan.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-8">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-800 dark:text-blue-300">Keuangan</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight">Rekap {{ $monthLabel }}</h2>
                </div>
                <div class="flex gap-1">
                    <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="shiftMonth(-1)" aria-label="Bulan sebelumnya" />
                    <flux:button size="sm" variant="ghost" icon="chevron-right" wire:click="shiftMonth(1)" aria-label="Bulan berikutnya" />
                </div>
            </div>

            <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-zinc-200/80 dark:bg-zinc-900 dark:ring-zinc-800">
                <div class="bg-gradient-to-br from-[#142b45] to-[#28557b] p-6 text-white">
                    <p class="text-sm text-slate-100/75">Pendapatan bersih</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight">{{ $rp($recap['net']) }}</p>
                    <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs text-slate-100">
                        <flux:icon name="check-circle" class="size-4" />
                        {{ $recap['rows']->count() }} trip selesai bulan ini
                    </div>
                </div>
                <dl class="space-y-4 p-5">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <dt class="text-zinc-500">Bagian harga</dt>
                        <dd class="font-semibold">{{ $rp($recap['share']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <dt class="text-zinc-500">Setoran</dt>
                        <dd class="font-semibold">{{ $rp($recap['deposit']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <dt class="text-zinc-500">Biaya ditanggung sopir</dt>
                        <dd class="font-semibold">{{ $rp($recap['expenses']) }}</dd>
                    </div>
                </dl>
            </div>

            @if ($recap['rows']->isNotEmpty())
                <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="border-b border-zinc-100 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-800">Trip terbaru</div>
                    @foreach ($recap['rows']->take(4) as $r)
                        <a href="{{ route('bookings.show', $r->booking_id) }}" wire:navigate wire:key="r-{{ $r->id }}"
                            class="flex items-center justify-between gap-3 border-b border-zinc-100 px-5 py-3.5 text-sm last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/60">
                            <span class="min-w-0">
                                <span class="block truncate font-medium">{{ $r->booking->customer_name }}</span>
                                <span class="mt-0.5 block text-xs text-zinc-500">{{ $r->booking->trip_date->translatedFormat('d M') }}</span>
                            </span>
                            <span class="shrink-0 font-semibold">{{ $rp($r->netIncome()) }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-zinc-300 bg-white/60 p-5 text-sm leading-6 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/60">
                    Trip yang sudah selesai akan muncul di rekap pendapatan bulan ini.
                </div>
            @endif
        </aside>
    </section>
</div>