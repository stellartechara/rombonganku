<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Rekan dan kendaraan')] class extends Component {
    public string $name = '';
    public string $phone = '';
    public string $vehicle_type = '';
    public string $plate = '';
    public ?int $capacity = null;
    public bool $is_self = false;

    #[Computed]
    public function drivers()
    {
        return Auth::user()->drivers()
            ->withCount('bookingVehicles')
            ->orderByDesc('is_self')
            ->orderBy('name')
            ->get();
    }

    public function add(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'vehicle_type' => 'required|string|max:50',
            'plate' => 'nullable|string|max:15',
            'capacity' => 'required|integer|min:1|max:100',
            'is_self' => 'boolean',
        ]);

        DB::transaction(function () use ($data) {
            if ($data['is_self']) {
                Auth::user()->drivers()->update(['is_self' => false]);
            }

            Auth::user()->drivers()->create($data);
        });

        $this->reset('name', 'phone', 'vehicle_type', 'plate', 'capacity', 'is_self');
        Flux::toast(variant: 'success', text: 'Tersimpan.');
    }

    public function remove(int $id): void
    {
        $driver = Auth::user()->drivers()->findOrFail($id);

        if ($driver->bookingVehicles()->exists()) {
            Flux::toast(variant: 'danger', text: 'Tidak bisa dihapus karena sudah pernah ikut trip.');

            return;
        }

        $driver->delete();
    }
}; ?>

<div class="mx-auto max-w-3xl space-y-8">
    <div>
        <flux:button href="{{ route('dashboard') }}" wire:navigate size="sm" variant="ghost" icon="arrow-left">Kembali</flux:button>
        <flux:heading size="xl" level="1" class="mt-2">Rekan dan kendaraan</flux:heading>
        <flux:text>Daftarkan dirimu dulu (centang "Ini saya"), lalu rekan satu armada.</flux:text>
    </div>

    <div class="space-y-3">
        @forelse ($this->drivers as $d)
            <div wire:key="d-{{ $d->id }}" class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <div class="font-semibold">
                        {{ $d->name }}
                        @if ($d->is_self) <flux:badge color="green" size="sm">Saya</flux:badge> @endif
                    </div>
                    <div class="text-sm text-zinc-500">
                        {{ $d->vehicle_type }} &middot; {{ $d->capacity }} kursi
                        @if ($d->plate) &middot; {{ $d->plate }} @endif
                        @if ($d->phone) &middot; {{ $d->phone }} @endif
                    </div>
                </div>
                <flux:button size="xs" variant="ghost" wire:click="remove({{ $d->id }})"
                    wire:confirm="Hapus {{ $d->name }}?">Hapus</flux:button>
            </div>
        @empty
            <flux:text>Belum ada data. Mulai dari dirimu sendiri.</flux:text>
        @endforelse
    </div>

    <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <flux:heading level="2" class="mb-4">Tambah</flux:heading>
        <form wire:submit="add" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" label="Nama sopir" required />
                <flux:input wire:model="phone" label="Nomor HP" type="tel" />
                <flux:input wire:model="vehicle_type" label="Jenis kendaraan" placeholder="Elf, Hiace, Bus..." required />
                <flux:input wire:model="plate" label="Plat nomor" />
                <flux:input wire:model="capacity" label="Kapasitas (kursi)" type="number" min="1" required />
            </div>
            <flux:checkbox wire:model="is_self" label="Ini saya (pendapatan bersih dihitung untuk kendaraan ini)" />
            <flux:button type="submit" variant="primary">Simpan</flux:button>
        </form>
    </div>
</div>