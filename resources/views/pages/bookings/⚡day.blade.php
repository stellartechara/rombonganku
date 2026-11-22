<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mode hari H')] class extends Component {
    public Booking $booking;
    public string $names = '';

    public function mount(Booking $booking): void
    {
        abort_unless($booking->user_id === Auth::id(), 404);

        $this->booking = $booking;
    }

    private function ensureOwner(): void
    {
        abort_unless($this->booking->user_id === Auth::id(), 404);
    }

    public function toggleBoarded(int $id): void
    {
        $this->ensureOwner();

        $p = $this->booking->passengers()->findOrFail($id);
        $p->update(['boarded' => ! $p->boarded]);
    }

    public function toggleStop(int $id): void
    {
        $this->ensureOwner();

        $s = $this->booking->stops()->findOrFail($id);
        $s->update(['reached' => ! $s->reached]);
    }

    public function assign(int $passengerId, ?string $vehicleId): void
    {
        $this->ensureOwner();

        $p = $this->booking->passengers()->findOrFail($passengerId);
        $vid = filled($vehicleId) ? (int) $vehicleId : null;

        if ($vid !== null) {
            $this->booking->vehicles()->findOrFail($vid);
        }

        $p->update(['booking_vehicle_id' => $vid]);
    }

    public function boardAll(): void
    {
        $this->ensureOwner();

        $this->booking->passengers()->update(['boarded' => true]);
    }

    public function resetAll(): void
    {
        $this->ensureOwner();

        $this->booking->passengers()->update(['boarded' => false]);
        $this->booking->stops()->update(['reached' => false]);
    }

    public function start(): void
    {
        $this->ensureOwner();

        if ($this->booking->status === BookingStatus::Deal) {
            $this->booking->update(['status' => BookingStatus::Berjalan]);
        }
    }

    public function finish(): void
    {
        $this->ensureOwner();

        if ($this->booking->status === BookingStatus::Berjalan) {
            $this->booking->update(['status' => BookingStatus::Selesai]);
        }
    }

    public function addPassengers(): void
    {
        $this->ensureOwner();
        $this->validate(['names' => 'required|string|max:2000']);

        collect(preg_split('/[\n,]+/', $this->names))
            ->map(fn ($n) => Str::limit(trim($n), 100, ''))
            ->filter()
            ->unique()
            ->take(100)
            ->each(fn ($n) => $this->booking->passengers()->create(['name' => $n]));

        $this->names = '';
    }
}; ?>

@php
    $passengers = $booking->passengers()->orderBy('name')->get();
    $stops = $booking->stops()->get();
    $vehicles = $booking->vehicles()->with('driver')->get();

    $total = $passengers->count();
    $boarded = $passengers->where('boarded', true)->count();
    $percent = $total ? round($boarded / $total * 100) : 0;
    $unassigned = $passengers->whereNull('booking_vehicle_id');

    $groups = $vehicles->map(fn ($v) => [
        'title' => $v->driver->name.' - '.$v->driver->vehicle_type.($v->driver->plate ? ' ('.$v->driver->plate.')' : ''),
        'self' => $v->driver->is_self,
        'seats' => $v->driver->capacity,
        'people' => $passengers->where('booking_vehicle_id', $v->id),
    ]);
@endphp

<div class="mx-auto max-w-xl space-y-6 pb-16">
    <div>
        <flux:button href="{{ route('bookings.show', $booking) }}" wire:navigate size="sm" variant="ghost" icon="arrow-left">Detail pesanan</flux:button>
        <flux:heading size="xl" level="1" class="mt-2">{{ $booking->customer_name }}</flux:heading>
        <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500">
            <flux:badge :color="$booking->status->color()">{{ $booking->status->label() }}</flux:badge>
            <span>{{ $booking->trip_date->translatedFormat('d M Y') }}</span>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
        <div class="flex items-end justify-between">
            <div class="text-3xl font-bold">{{ $boarded }} <span class="text-lg font-normal text-zinc-500">dari {{ $total }} sudah naik</span></div>
            <div class="text-sm text-zinc-500">{{ $percent }}%</div>
        </div>
        <div class="mt-3 h-4 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
            <div class="h-full bg-green-600 transition-all" style="width: {{ $percent }}%"></div>
        </div>

        @if ($total > 0 && $boarded === $total)
            <div class="mt-3 font-semibold text-green-600">Rombongan lengkap, siap berangkat.</div>
        @elseif ($total > 0)
            <div class="mt-3 text-sm text-amber-600">Belum naik: {{ $passengers->where('boarded', false)->pluck('name')->take(6)->join(', ') }}{{ $total - $boarded > 6 ? ', ...' : '' }}</div>
        @endif

        @if ($booking->passenger_count > $total)
            <div class="mt-2 text-xs text-zinc-500">{{ $booking->passenger_count - $total }} penumpang belum ada namanya di daftar.</div>
        @endif

        <div class="mt-4 flex flex-wrap gap-2">
            <flux:button size="sm" wire:click="boardAll">Tandai semua naik</flux:button>
            <flux:button size="sm" variant="ghost" wire:click="resetAll" wire:confirm="Reset semua centang naik dan pemberhentian?">Reset</flux:button>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        @if ($booking->status === BookingStatus::Deal)
            <flux:button variant="primary" wire:click="start" wire:confirm="Mulai perjalanan sekarang?">Mulai perjalanan</flux:button>
        @elseif ($booking->status === BookingStatus::Berjalan)
            <flux:button variant="primary" wire:click="finish" wire:confirm="Tandai trip ini selesai?">Selesaikan trip</flux:button>
        @elseif (! in_array($booking->status, [BookingStatus::Selesai, BookingStatus::Batal], true))
            <flux:text class="text-sm">Ubah status ke Deal di detail pesanan untuk bisa memulai perjalanan.</flux:text>
        @endif
    </div>

    @foreach ($groups as $g)
        <div wire:key="g-{{ $loop->index }}">
            <flux:heading level="2">
                {{ $g['title'] }}
                @if ($g['self']) <flux:badge color="green" size="sm">Saya</flux:badge> @endif
            </flux:heading>
            <flux:text class="text-sm">{{ $g['people']->count() }} penumpang &middot; {{ $g['seats'] }} kursi</flux:text>

            <div class="mt-2 divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                @forelse ($g['people'] as $p)
                    @include('partials.day-passenger', ['p' => $p, 'vehicles' => $vehicles])
                @empty
                    <div class="p-4 text-sm text-zinc-500">Belum ada penumpang di kendaraan ini.</div>
                @endforelse
            </div>
        </div>
    @endforeach

    @if ($unassigned->isNotEmpty() || $vehicles->isEmpty())
        <div>
            <flux:heading level="2">{{ $vehicles->isEmpty() ? 'Penumpang' : 'Belum masuk kendaraan' }}</flux:heading>
            <div class="mt-2 divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                @forelse ($unassigned as $p)
                    @include('partials.day-passenger', ['p' => $p, 'vehicles' => $vehicles])
                @empty
                    <div class="p-4 text-sm text-zinc-500">Belum ada penumpang. Tambahkan nama di bawah.</div>
                @endforelse
            </div>
        </div>
    @endif

    <form wire:submit="addPassengers" class="space-y-2">
        <flux:textarea wire:model="names" rows="2" label="Tambah penumpang (baris baru atau koma)" />
        <flux:button type="submit" size="sm">Tambah nama</flux:button>
    </form>

    <div>
        <flux:heading level="2">Pemberhentian</flux:heading>
        <div class="mt-2 divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @forelse ($stops as $s)
                <button type="button" wire:key="s-{{ $s->id }}" wire:click="toggleStop({{ $s->id }})"
                    class="flex w-full items-center gap-3 p-4 text-left text-lg">
                    <span @class([
                        'flex size-8 shrink-0 items-center justify-center rounded-md border-2 text-white',
                        'border-green-600 bg-green-600' => $s->reached,
                        'border-zinc-400' => ! $s->reached,
                    ])>{{ $s->reached ? '✓' : '' }}</span>
                    <span @class(['text-zinc-500 line-through' => $s->reached])>
                        <span class="text-sm text-zinc-500">{{ $s->time ? substr($s->time, 0, 5) : '' }}</span>
                        {{ $s->place }}
                    </span>
                </button>
            @empty
                <div class="p-4 text-sm text-zinc-500">Belum ada pemberhentian. Tambahkan di detail pesanan.</div>
            @endforelse
        </div>
    </div>
</div>