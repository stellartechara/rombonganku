<?php

use App\Models\User;
use App\Support\TripEstimator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Sewa kendaraan untuk rombongan')] class extends Component {
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

@php($estimate = $this->estimate)
<div class="space-y-16">
    <section class="relative overflow-hidden rounded-[2rem] bg-[#142b45] px-6 py-10 text-white shadow-2xl shadow-slate-950/15 sm:px-10 sm:py-14 lg:px-14">
        <div class="absolute -right-20 -top-32 size-96 rounded-full border border-white/10"></div>
        <div class="absolute -right-6 -top-20 size-72 rounded-full border border-white/10"></div>
        <div class="absolute bottom-0 right-0 hidden h-full w-[42%] bg-gradient-to-l from-amber-300/10 to-transparent lg:block"></div>
        <div class="relative max-w-3xl">
            <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-amber-200/25 bg-white/10 px-3 py-1.5 text-xs font-medium text-amber-100">
                <span class="size-2 rounded-full bg-amber-300"></span>
                Rencana perjalanan rombongan jadi lebih mudah
            </div>
            <h1 class="max-w-2xl text-4xl font-semibold leading-[1.08] tracking-tight sm:text-5xl lg:text-6xl">
                Semua orang ikut. <span class="text-amber-300">Urusan trip, biar kami bantu.</span>
            </h1>
            <p class="mt-5 max-w-xl text-base leading-7 text-slate-100/80 sm:text-lg">
                Rencanakan perjalanan, cek estimasi kendaraan dan biaya, lalu ajukan langsung ke sopir. Praktis sejak awal, nyaman sampai tujuan.
            </p>
            <div class="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-sm text-slate-100/90">
                <span class="inline-flex items-center gap-2"><flux:icon name="check-circle" class="size-5 text-amber-300" /> Estimasi transparan</span>
                <span class="inline-flex items-center gap-2"><flux:icon name="check-circle" class="size-5 text-amber-300" /> Tanpa perlu buat akun</span>
            </div>
        </div>
        <div class="relative mt-9 flex items-end justify-between border-t border-white/15 pt-5 text-xs text-slate-200/75 sm:mt-12">
            <span>Mulai dari form sederhana di bawah</span>
            <span class="hidden items-center gap-2 sm:flex"><flux:icon name="shield-check" class="size-4" /> Detail perjalanan aman dan terjaga</span>
        </div>
    </section>

    <section id="ajukan-trip" class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_350px]">
        <div>
            <div class="mb-6">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-800 dark:text-blue-300">Mulai perjalananmu</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Ceritakan rencana trip-nya</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-zinc-600 dark:text-zinc-400">Isi detail di bawah. Kami hitungkan perkiraan kebutuhan kendaraan dan biaya secara langsung.</p>
            </div>
            <form wire:submit="submit" class="space-y-6 rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
                <div>
                    <div class="mb-4 flex items-center gap-3">
                        <span class="flex size-8 items-center justify-center rounded-xl bg-blue-50 text-sm font-bold text-blue-800 dark:bg-blue-950 dark:text-blue-300">1</span>
                        <div>
                            <h3 class="font-semibold">Kontak pemesan</h3>
                            <p class="text-xs text-zinc-500">Supaya sopir bisa menghubungimu untuk konfirmasi.</p>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="customer_name" label="Nama kamu atau rombongan" placeholder="Contoh: Keluarga Pak Budi" required />
                        <flux:input wire:model="customer_phone" label="Nomor WhatsApp" type="tel" placeholder="0812 3456 7890" required />
                    </div>
                </div>

                <flux:separator />

                <div>
                    <div class="mb-4 flex items-center gap-3">
                        <span class="flex size-8 items-center justify-center rounded-xl bg-blue-50 text-sm font-bold text-blue-800 dark:bg-blue-950 dark:text-blue-300">2</span>
                        <div>
                            <h3 class="font-semibold">Detail perjalanan</h3>
                            <p class="text-xs text-zinc-500">Ubah jumlah orang, durasi, dan jarak untuk memperbarui estimasi.</p>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <flux:input wire:model.live.debounce.400ms="passenger_count" label="Penumpang" type="number" min="1" max="200" required />
                        <flux:input wire:model.live.debounce.400ms="days" label="Lama (hari)" type="number" min="1" max="30" required />
                        <flux:input wire:model.live.debounce.400ms="km" label="Jarak PP (km)" type="number" min="1" max="5000" required />
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="trip_date" label="Tanggal berangkat" type="date" required />
                        <flux:textarea wire:model="notes" label="Catatan (opsional)" rows="2" placeholder="Tujuan, titik jemput, permintaan khusus..." />
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-zinc-100 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
                    <p class="max-w-sm text-xs leading-5 text-zinc-500">Dengan mengajukan, kamu setuju detail ini dibagikan kepada sopir untuk keperluan perjalanan.</p>
                    <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled" class="w-full sm:w-auto">
                        <span wire:loading.remove wire:target="submit">Ajukan perjalanan</span>
                        <span wire:loading wire:target="submit">Mengirim...</span>
                    </flux:button>
                </div>
            </form>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-28">
            <div class="overflow-hidden rounded-3xl bg-[#1d3b59] p-6 text-white shadow-xl shadow-slate-950/15">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-100/75">Estimasi perjalanan</p>
                        <div wire:loading.class="animate-pulse opacity-50" wire:target="passenger_count,days,km" class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                            Rp {{ number_format($estimate['total'], 0, ',', '.') }}
                        </div>
                    </div>
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-amber-300/15 text-amber-200"><flux:icon name="calculator" class="size-6" /></span>
                </div>
                <p class="mt-2 text-sm text-slate-100/75">Sekitar Rp {{ number_format($estimate['per_person'], 0, ',', '.') }} per orang</p>
                <div class="my-5 h-px bg-white/15"></div>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs text-slate-200/70">Kendaraan yang disarankan</p>
                        <p class="mt-1 font-semibold">{{ $estimate['count'] }} × {{ $estimate['vehicle'] }}</p>
                    </div>
                    <span class="flex size-10 items-center justify-center rounded-xl bg-white/10"><flux:icon name="truck" class="size-5" /></span>
                </div>
                <p class="mt-5 text-xs leading-5 text-slate-200/70">Belum termasuk tol dan parkir. Estimasi ini hanya patokan; harga final dibicarakan langsung dengan sopir.</p>
            </div>

            <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="font-semibold">Setelah mengajukan</h3>
                <ol class="mt-4 space-y-4">
                    <li class="flex gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-800 dark:bg-blue-950 dark:text-blue-300">1</span>
                        <span class="pt-1 text-sm text-zinc-600 dark:text-zinc-400">Simpan kode untuk melacak status pesanan.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-800 dark:bg-blue-950 dark:text-blue-300">2</span>
                        <span class="pt-1 text-sm text-zinc-600 dark:text-zinc-400">Sopir akan menghubungimu untuk membahas detail dan harga.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-800 dark:bg-blue-950 dark:text-blue-300">3</span>
                        <span class="pt-1 text-sm text-zinc-600 dark:text-zinc-400">Tambahkan daftar penumpang dari halaman pelacakan.</span>
                    </li>
                </ol>
            </div>
        </aside>
    </section>

    <section class="grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['map', 'Rencana lebih jelas', 'Catat kebutuhan rombongan dan jadwal keberangkatan sejak awal.'],
            ['chat-bubble-left-right', 'Harga dibicarakan langsung', 'Estimasi jadi pegangan, harga final tetap hasil kesepakatan dengan sopir.'],
            ['user-group', 'Satu rombongan, terkoordinasi', 'Daftar penumpang bisa dilengkapi dan dipantau melalui satu halaman.'],
        ] as [$icon, $title, $description])
            <div class="rounded-2xl border border-zinc-200/70 bg-white/75 p-5 dark:border-zinc-800 dark:bg-zinc-900/75">
                <span class="flex size-10 items-center justify-center rounded-xl bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-300"><flux:icon :name="$icon" class="size-5" /></span>
                <h3 class="mt-4 font-semibold">{{ $title }}</h3>
                <p class="mt-1 text-sm leading-6 text-zinc-600 dark:text-zinc-400">{{ $description }}</p>
            </div>
        @endforeach
    </section>
</div>