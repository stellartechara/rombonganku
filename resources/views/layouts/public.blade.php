<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-900">
        <header class="border-b border-zinc-200 dark:border-zinc-700">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
                <a href="{{ route('home') }}" wire:navigate class="text-lg font-bold">RombonganKu</a>

                @auth
                    <flux:button href="{{ route('dashboard') }}" size="sm" variant="ghost">Dashboard</flux:button>
                @else
                    <flux:button href="{{ route('login') }}" size="sm" variant="ghost">Masuk sopir</flux:button>
                @endauth
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-10">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>