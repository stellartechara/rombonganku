<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Expense;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail pesanan')] class extends Component {
    public Booking $booking;
    public string $status = '';
    public ?int $deal_price = null;
    public string $notes = '';
    public string $stop_time = '';
    public string $stop_place = '';

    public ?int $new_driver_id = null;
    public ?int $new_share = null;
    public array $shares = [];
    public array $deposits = [];
    public array $exp = [];

    public function mount(Booking $booking): void
    {
        abort_unless($booking->user_id === Auth::id(), 404);

        $this->booking = $booking;
        $this->status = $booking->status->value;
        $this->deal_price = $booking->deal_price;
        $this->notes = (string) $booking->notes;
        $this->loadVehicles();
    }

    private function ensureOwner(): void
    {
        abort_unless($this->booking->user_id === Auth::id(), 404);
    }

    private function loadVehicles(): void
    {
        foreach ($this->booking->vehicles()->get() as $bv) {
            $this->shares[$bv->id] = $bv->share_price;
            $this->deposits[$bv->id] = $bv->deposit;
            $this->exp[$bv->id] ??= [
                'category' => 'BBM',
                'amount' => null,
                'paid_by' => Expense::PAID_BY_DRIVER,
            ];
        }
    }

    public function save(): void
    {
        $this->ensureOwner();

        $data = $this->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'deal_price' => [
                Rule::requiredIf(in_array($this->status, ['deal', 'berjalan', 'selesai'], true)),
                'nullable', 'integer', 'min:0', 'max:1000000000',
            ],
            'notes' => 'nullable|string|max:2000',
        ]);

        $this->booking->update($data);

        Flux::toast(variant: 'success', text: 'Perubahan tersimpan.');
    }

    public function addStop(): void
    {
        $this->ensureOwner();

        $data = $this->validate([
            'stop_place' => 'required|string|max:120',
            'stop_time' => 'nullable|date_format:H:i',
        ]);

        $this->booking->stops()->create([
            'place' => $data['stop_place'],
            'time' => $data['stop_time'] ?: null,
            'position' => (int) $this->booking->stops()->max('position') + 1,
        ]);

        $this->reset('stop_place', 'stop_time');
    }

    public function removeStop(int $id): void
    {
        $this->ensureOwner();

        $this->booking->stops()->whereKey($id)->delete();
    }

    public function addVehicle(): void
    {
        $this->ensureOwner();

        $data = $this->validate([
            'new_driver_id' => 'required|integer',
            'new_share' => 'required|integer|min:0|max:1000000000',
        ], [], [
            'new_driver_id' => 'sopir',
            'new_share' => 'bagian harga',
        ]);

        $driver = Auth::user()->drivers()->findOrFail($data['new_driver_id']);

        if ($this->booking->vehicles()->where('driver_id', $driver->id)->exists()) {
            $this->addError('new_driver_id', 'Sopir ini sudah ditambahkan.');

            return;
        }

        $this->booking->vehicles()->create([
            'driver_id' => $driver->id,
            'share_price' => $data['new_share'],
        ]);

        $this->reset('new_driver_id', 'new_share');
        $this->loadVehicles();
    }

    public function saveVehicle(int $id): void
    {
        $this->ensureOwner();

        $bv = $this->booking->vehicles()->with('driver')->findOrFail($id);

        $this->validate([
            "shares.$id" => 'required|integer|min:0|max:1000000000',
            "deposits.$id" => 'nullable|integer|min:0|max:1000000000',
        ]);

        $bv->update([
            'share_price' => $this->shares[$id],
            'deposit' => $bv->driver->is_self && filled($this->deposits[$id] ?? null)
                ? (int) $this->deposits[$id]
                : null,
        ]);

        Flux::toast(variant: 'success', text: 'Tersimpan.');
    }

    public function removeVehicle(int $id): void
    {
        $this->ensureOwner();

        $this->booking->vehicles()->whereKey($id)->delete();
        unset($this->shares[$id], $this->deposits[$id], $this->exp[$id]);
    }

    public function addExpense(int $id): void
    {
        $this->ensureOwner();

        $bv = $this->booking->vehicles()
            ->whereHas('driver', fn ($q) => $q->where('is_self', true))
            ->findOrFail($id);

        $this->validate([
            "exp.$id.category" => 'required|in:BBM,Tol,Parkir,Makan,Lainnya',
            "exp.$id.amount" => 'required|integer|min:1|max:1000000000',
            "exp.$id.paid_by" => 'required|in:driver,customer',
        ]);

        $bv->expenses()->create([
            'category' => $this->exp[$id]['category'],
            'amount' => (int) $this->exp[$id]['amount'],
            'paid_by' => $this->exp[$id]['paid_by'],
        ]);

        $this->exp[$id]['amount'] = null;
    }

    public function removeExpense(int $id): void
    {
        $this->ensureOwner();

        Expense::whereKey($id)
            ->whereHas('bookingVehicle', fn ($q) => $q->where('booking_id', $this->booking->id))
            ->delete();
    }
}; ?>

@php
    $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    $stops = $booking->stops()->get();
    $passengers = $booking->passengers()->orderBy('id')->get();
    $vehicles = $booking->vehicles()->with(['driver', 'expenses'])->get();
    $drivers = Auth::user()->drivers()->orderByDesc('is_self')->orderBy('name')->get();
    $capacity = $vehicles->sum(fn ($v) => $v->driver->capacity);
    $shareTotal = $vehicles->sum('share_price');
    $myNet = $vehicles->filter(fn ($v) => $v->driver->is_self)->sum(fn ($v) => $v->netIncome());
    $hasSelf = $vehicles->contains(fn ($v) => $v->driver->is_self);

    $wa = preg_replace('/\D/', '', $booking->customer_phone);
    if (str_starts_with($wa, '0')) {
        $wa = '62'.substr($wa, 1);
    }
    $trackUrl = route('trip.show', $booking->tracking_code);
@endphp

<div class="mx-auto max-w-3xl space-y-8">
    <div>
        <flux:button href="{{ route('dashboard') }}" wire:navigate size="sm" variant="ghost" icon="arrow-left">Kembali</flux:button>
        <flux:heading size="xl" level="1" class="mt-2">{{ $booking->customer_name }}</flux:heading>
        <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-zinc-500">
            <span>{{ $booking->trip_date->translatedFormat('l, d F Y') }}</span>
            <span>{{ $booking->days }} hari</span>
            <span>{{ $booking->passenger_count }} orang</span>
            <span>Kode {{ $booking->tracking_code }}</span>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <flux:button href="{{ route('bookings.day', $booking) }}" wire:navigate size="sm" variant="primary" icon="play">
            Mode hari H
        </flux:button>
        <flux:button href="https://wa.me/{{ $wa }}" target="_blank" size="sm" icon="chat-bubble-left-right">
            Hubungi lewat WhatsApp
        </flux:button>
        <flux:button href="{{ $trackUrl }}" target="_blank" size="sm" variant="ghost">
            Lihat halaman pelanggan
        </flux:button>
    </div>

    <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <flux:heading level="2" class="mb-4">Status dan harga</flux:heading>
        <form wire:submit="save" class="space-y-4">
            <flux:select wire:model="status" label="Status">
                @foreach (BookingStatus::cases() as $s)
                    <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="deal_price" type="number" min="0" label="Harga deal (Rp)"
                description="Wajib diisi kalau status Deal, Berjalan, atau Selesai." />

            <flux:textarea wire:model="notes" rows="4" label="Catatan" />

            <flux:button type="submit" variant="primary">Simpan</flux:button>
        </form>
    </div>

    <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <flux:heading level="2" class="mb-1">Kendaraan dan keuangan</flux:heading>
        <flux:text class="mb-4 text-sm">
            Kapasitas terpasang {{ $capacity }} kursi untuk {{ $booking->passenger_count }} penumpang.
        </flux:text>

        @if ($vehicles->isNotEmpty() && $capacity < $booking->passenger_count)
            <flux:callout variant="warning" icon="exclamation-triangle" class="mb-4"
                heading="Kursi masih kurang {{ $booking->passenger_count - $capacity }} orang." />
        @endif

        @if ($booking->deal_price && $vehicles->isNotEmpty() && $shareTotal !== $booking->deal_price)
            <flux:callout variant="warning" icon="exclamation-triangle" class="mb-4"
                heading="Total bagian harga {{ $rp($shareTotal) }} belum sama dengan harga deal {{ $rp($booking->deal_price) }}." />
        @endif

        <div class="space-y-4">
            @foreach ($vehicles as $bv)
                @php $mine = $bv->driver->is_self; @endphp
                <div wire:key="bv-{{ $bv->id }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold">
                                {{ $bv->driver->name }}
                                @if ($mine) <flux:badge color="green" size="sm">Saya</flux:badge> @endif
                            </div>
                            <div class="text-sm text-zinc-500">
                                {{ $bv->driver->vehicle_type }} &middot; {{ $bv->driver->capacity }} kursi
                                @if ($bv->driver->plate) &middot; {{ $bv->driver->plate }} @endif
                            </div>
                        </div>
                        <flux:button size="xs" variant="ghost" wire:click="removeVehicle({{ $bv->id }})"
                            wire:confirm="Lepas kendaraan ini dari pesanan? Catatan biayanya ikut terhapus.">Lepas</flux:button>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <flux:input wire:model="shares.{{ $bv->id }}" type="number" min="0" label="Bagian harga (Rp)" />
                        @if ($mine)
                            <flux:input wire:model="deposits.{{ $bv->id }}" type="number" min="0" label="Setoran (Rp)" />
                        @endif
                    </div>
                    <flux:button size="sm" class="mt-3" wire:click="saveVehicle({{ $bv->id }})">Simpan angka</flux:button>

                    @if ($mine)
                        <div class="mt-5 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <div class="mb-2 text-sm font-medium">Biaya</div>

                            <ul class="mb-3 space-y-1 text-sm">
                                @forelse ($bv->expenses as $e)
                                    <li wire:key="e-{{ $e->id }}" class="flex items-center justify-between gap-3">
                                        <span>
                                            {{ $e->category }} &middot; {{ $rp($e->amount) }}
                                            @if ($e->paid_by === 'customer') <flux:badge size="sm">ditanggung pelanggan</flux:badge> @endif
                                        </span>
                                        <flux:button size="xs" variant="ghost" wire:click="removeExpense({{ $e->id }})">Hapus</flux:button>
                                    </li>
                                @empty
                                    <li class="text-zinc-500">Belum ada biaya tercatat.</li>
                                @endforelse
                            </ul>

                            <div class="grid gap-2 sm:grid-cols-[130px_1fr_150px_auto] sm:items-end">
                                <flux:select wire:model="exp.{{ $bv->id }}.category" label="Jenis">
                                    @foreach (['BBM', 'Tol', 'Parkir', 'Makan', 'Lainnya'] as $c)
                                        <flux:select.option value="{{ $c }}">{{ $c }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="exp.{{ $bv->id }}.amount" type="number" min="1" label="Jumlah (Rp)" />
                                <flux:select wire:model="exp.{{ $bv->id }}.paid_by" label="Ditanggung">
                                    <flux:select.option value="driver">Sopir</flux:select.option>
                                    <flux:select.option value="customer">Pelanggan</flux:select.option>
                                </flux:select>
                                <flux:button wire:click="addExpense({{ $bv->id }})">Tambah</flux:button>
                            </div>

                            <dl class="mt-4 space-y-1 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                                <div class="flex justify-between"><dt>Bagian harga</dt><dd>{{ $rp($bv->share_price) }}</dd></div>
                                <div class="flex justify-between"><dt>Setoran</dt><dd>- {{ $rp($bv->deposit) }}</dd></div>
                                <div class="flex justify-between"><dt>Biaya ditanggung sopir</dt><dd>- {{ $rp($bv->driverExpenses()) }}</dd></div>
                                <div class="flex justify-between border-t border-zinc-300 pt-1 font-bold dark:border-zinc-600">
                                    <dt>Pendapatan bersih</dt><dd>{{ $rp($bv->netIncome()) }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($hasSelf)
            <div class="mt-4 text-right text-sm">
                Pendapatan bersih kamu di trip ini: <span class="text-lg font-bold">{{ $rp($myNet) }}</span>
            </div>
        @endif

        <form wire:submit="addVehicle" class="mt-5 grid gap-3 border-t border-zinc-200 pt-4 sm:grid-cols-[1fr_180px_auto] sm:items-end dark:border-zinc-700">
            <flux:select wire:model="new_driver_id" label="Tambah kendaraan">
                <flux:select.option value="">Pilih sopir...</flux:select.option>
                @foreach ($drivers->whereNotIn('id', $vehicles->pluck('driver_id')) as $d)
                    <flux:select.option value="{{ $d->id }}">
                        {{ $d->name }} - {{ $d->vehicle_type }} ({{ $d->capacity }} kursi){{ $d->is_self ? ' - saya' : '' }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="new_share" type="number" min="0" label="Bagian harga (Rp)" />
            <flux:button type="submit">Tambah</flux:button>
        </form>
        @if ($drivers->isEmpty())
            <flux:text class="mt-2 text-sm">
                Belum ada sopir terdaftar.
                <a href="{{ route('drivers.index') }}" wire:navigate class="underline">Daftarkan dulu</a>.
            </flux:text>
        @endif
    </div>

    <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <flux:heading level="2" class="mb-4">Rute dan jadwal</flux:heading>

        <ul class="mb-4 space-y-2">
            @forelse ($stops as $stop)
                <li wire:key="stop-{{ $stop->id }}" class="flex items-center justify-between gap-3 text-sm">
                    <span><span class="inline-block w-14 text-zinc-500">{{ $stop->time ? substr($stop->time, 0, 5) : '-' }}</span>{{ $stop->place }}</span>
                    <flux:button size="xs" variant="ghost" wire:click="removeStop({{ $stop->id }})"
                        wire:confirm="Hapus pemberhentian ini?">Hapus</flux:button>
                </li>
            @empty
                <li class="text-sm text-zinc-500">Belum ada pemberhentian.</li>
            @endforelse
        </ul>

        <form wire:submit="addStop" class="grid gap-3 sm:grid-cols-[120px_1fr_auto] sm:items-end">
            <flux:input wire:model="stop_time" type="time" label="Jam" />
            <flux:input wire:model="stop_place" label="Tempat" placeholder="Titik jemput, rumah makan, tujuan..." />
            <flux:button type="submit">Tambah</flux:button>
        </form>
    </div>

    <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <flux:heading level="2">Penumpang</flux:heading>
        <flux:text class="text-sm">{{ $passengers->count() }} dari {{ $booking->passenger_count }} orang tercatat oleh pelanggan</flux:text>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($passengers as $p)
                <flux:badge wire:key="p-{{ $p->id }}">{{ $p->name }}</flux:badge>
            @endforeach
        </div>
    </div>
</div>