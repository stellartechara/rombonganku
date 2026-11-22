<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="auth-shell min-h-screen antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center px-5 py-10 sm:px-8">
            <div class="w-full max-w-[440px]">
                <a href="{{ route('home') }}" class="mb-7 flex items-center justify-center gap-3" wire:navigate>
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-[#1e3a5f] text-white shadow-lg shadow-slate-950/20">
                        <x-app-logo-icon class="size-6 fill-current" />
                    </span>
                    <span class="text-lg font-bold tracking-tight text-zinc-900 dark:text-white">Rombongan<span class="text-amber-600 dark:text-amber-400">Ku</span></span>
                </a>
                <div class="rounded-[1.75rem] border border-white/80 bg-white/90 p-6 shadow-[0_24px_80px_-32px_rgba(22,57,45,0.28)] backdrop-blur-xl sm:p-9 dark:border-zinc-800 dark:bg-zinc-900/90">
                    {{ $slot }}
                </div>
                <p class="mt-6 text-center text-xs text-zinc-500">Ruang kerja yang rapi untuk perjalanan yang lancar.</p>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
