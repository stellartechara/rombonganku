<?php

use App\Models\User;
use App\Support\TripEstimator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('RombonganKu - Sewa kendaraan untuk rombongan')] class extends Component {
    public string $customer_name = '';
    public string $customer_phone = '';
    public string $trip_date = '';
    public ?int $passenger_count = 12;
    public ?int $days = 1;
    public ?int $km = 200;
    public string $notes = '';

    #[Computed]
    public function estimate(): array
    {
        return TripEstimator::estimate((int) $this->passenger_count, (int) $this->days, (int) $this->km);
    }

    public function submit(): void
    {
        $data = $this->validate([
            'customer_name' => 'required|string|max:100',
            'customer_phone' => ['required', 'regex:/^[0-9+\s\-]{8,20}$/'],
            'trip_date' => 'required|date|after_or_equal:today',
            'passenger_count' => 'required|integer|min:1|max:200',
            'days' => 'required|integer|min:1|max:30',
            'km' => 'required|integer|min:1|max:5000',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Versi awal: satu sopir. Semua permintaan masuk ke akun pertama.
        $owner = User::oldest('id')->firstOrFail();

        $booking = $owner->bookings()->create([
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'trip_date' => $data['trip_date'],
            'days' => $data['days'],
            'passenger_count' => $data['passenger_count'],
            'notes' => trim(($data['notes'] ?? '')."\nPerkiraan jarak: {$data['km']} km PP"),
        ]);

        $this->redirectRoute('trip.show', ['code' => $booking->tracking_code], navigate: true);
    }
}; ?>

<div>
    <div class="mb-10 max-w-2xl">
        <flux:heading size="xl" level="1">Bawa rombongan tanpa ribet</flux:heading>
        <flux:text class="mt-2">
            Isi rencana tripnya, lihat perkiraan biaya langsung, lalu ajukan ke sopir.
            Harga akhir dibicarakan langsung dengan sopir.
        </flux:text>
    </div>

    <div class="grid gap-8 md:grid-cols-[1fr_320px]">
        <form wire:submit="submit" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="customer_name" label="Nama kamu atau rombongan" required />
                <flux:input wire:model="customer_phone" label="Nomor WhatsApp" type="tel" placeholder="0812..." required />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model.live.debounce.400ms="passenger_count" label="Jumlah penumpang" type="number" min="1" required />
                <flux:input wire:model.live.debounce.400ms="days" label="Lama (hari)" type="number" min="1" required />
                <flux:input wire:model.live.debounce.400ms="km" label="Jarak PP (km)" type="number" min="1" required />
            </div>

            <flux:input wire:model="trip_date" label="Tanggal berangkat" type="date" required />

            <flux:textarea wire:model="notes" label="Catatan (opsional)" rows="3"
                placeholder="Tujuan, titik jemput, permintaan khusus..." />

            <flux:button type="submit" variant="primary">Ajukan trip ini</flux:button>
        </form>

        <aside class="h-fit rounded-xl border border-zinc-200 p-5 md:sticky md:top-6 dark:border-zinc-700">
            <flux:text class="text-sm">Perkiraan biaya</flux:text>
            <div class="mt-1 text-3xl font-bold">Rp {{ number_format($this->estimate['total'], 0, ',', '.') }}</div>
            <flux:text class="text-sm">
                Sekitar Rp {{ number_format($this->estimate['per_person'], 0, ',', '.') }} per orang
            </flux:text>

            <flux:separator class="my-4" />

            <div class="text-sm">
                <div class="font-medium">
                    {{ $this->estimate['count'] }} x {{ $this->estimate['vehicle'] }}
                </div>
                <flux:text class="mt-2 text-xs">
                    Belum termasuk tol dan parkir. Ini hanya patokan, bukan harga final.
                </flux:text>
            </div>
        </aside>
    </div>
</div>