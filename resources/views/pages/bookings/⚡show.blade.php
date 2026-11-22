<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
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

    public function mount(Booking $booking): void
    {
        abort_unless($booking->user_id === Auth::id(), 404);

        $this->booking = $booking;
        $this->status = $booking->status->value;
        $this->deal_price = $booking->deal_price;
        $this->notes = (string) $booking->notes;
    }

    private function ensureOwner(): void
    {
        abort_unless($this->booking->user_id === Auth::id(), 404);
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
}; ?>

@php
    $stops = $booking->stops()->get();
    $passengers = $booking->passengers()->orderBy('id')->get();

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