<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="public-page min-h-screen antialiased text-zinc-900 dark:text-zinc-100">
        <header class="sticky top-0 z-30 border-b border-white/70 bg-white/80 backdrop-blur-xl dark:border-zinc-800/80 dark:bg-zinc-950/75">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-4 sm:px-8">
                <a href="{{ route('home') }}" wire:navigate class="group flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-2xl bg-[#1e3a5f] text-white shadow-lg shadow-slate-950/15 transition group-hover:-rotate-3">
                        <x-app-logo-icon class="size-6 fill-current" />
                    </span>
                    <span>
                        <span class="block text-base font-bold tracking-tight">Rombongan<span class="text-amber-600 dark:text-amber-400">Ku</span></span>
                        <span class="block text-[11px] font-medium tracking-wide text-zinc-500">PERJALANAN BARENG, LEBIH TENANG</span>
                    </span>
                </a>

                @auth
                    <flux:button href="{{ route('dashboard') }}" wire:navigate size="sm" variant="primary" icon="squares-2x2">Dashboard</flux:button>
                @else
                    <flux:button href="{{ route('login') }}" wire:navigate size="sm" variant="ghost" icon="arrow-right-end-on-rectangle">Masuk sopir</flux:button>
                @endauth
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-5 py-8 sm:px-8 sm:py-12">
            {{ $slot }}
        </main>

        <footer class="mx-auto flex max-w-6xl flex-col gap-2 border-t border-zinc-200/80 px-5 py-7 text-xs text-zinc-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 dark:border-zinc-800">
            <span>© {{ now()->year }} RombonganKu · Dirancang untuk perjalanan yang lebih rapi.</span>
            <span>Perjalanan nyaman dimulai dari rencana yang jelas.</span>
        </footer>

        @fluxScripts
    </body>
</html>