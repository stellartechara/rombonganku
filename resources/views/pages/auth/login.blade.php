<x-layouts::auth :title="__('Masuk')">
    <div class="flex flex-col gap-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-800 dark:text-blue-300">Portal sopir</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">Selamat datang kembali</h1>
            <p class="mt-2 text-sm leading-6 text-zinc-500">Masuk untuk mengelola pesanan, armada, dan perjalananmu.</p>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input
                name="email"
                label="Alamat email"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="nama@email.com"
            />

            <div class="relative">
                <flux:input
                    name="password"
                    label="Kata sandi"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute -top-6 text-xs end-0" :href="route('password.request')" wire:navigate>
                        Lupa kata sandi?
                    </flux:link>
                @endif
            </div>

            <flux:checkbox name="remember" label="Ingat saya di perangkat ini" :checked="old('remember')" />

            <div class="flex flex-col gap-4">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button" icon="arrow-right">
                    Masuk ke dashboard
                </flux:button>
                <p class="text-center text-xs text-zinc-500">Akses khusus untuk sopir terdaftar.</p>
            </div>
        </form>

        <div class="flex items-start gap-3 rounded-2xl bg-blue-50/80 p-4 dark:bg-blue-950/40">
            <flux:icon name="lock-closed" class="mt-0.5 size-4 shrink-0 text-blue-800 dark:text-blue-300" />
            <p class="text-xs leading-5 text-zinc-600 dark:text-zinc-400">Data pesanan dan keuangan hanya dapat diakses oleh pemilik akun.</p>
        </div>
    </div>
</x-layouts::auth>
