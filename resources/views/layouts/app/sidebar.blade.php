<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="dashboard-shell min-h-screen text-zinc-900 antialiased dark:text-zinc-100">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200/80 bg-white/90 backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-950">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group heading="Menu utama" class="grid">
                    <flux:sidebar.item icon="squares-2x2" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        Ringkasan
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('drivers.index')" :current="request()->routeIs('drivers.*')" wire:navigate>
                        Rekan & kendaraan
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="mx-3 mb-4 rounded-2xl bg-blue-50 p-4 dark:bg-blue-950/50">
                <div class="flex size-9 items-center justify-center rounded-xl bg-white text-blue-800 shadow-sm dark:bg-blue-900 dark:text-blue-200">
                    <flux:icon name="sparkles" class="size-5" />
                </div>
                <p class="mt-3 text-sm font-semibold">Siap untuk trip berikutnya?</p>
                <p class="mt-1 text-xs leading-5 text-zinc-600 dark:text-zinc-400">Pastikan data rekan dan kendaraanmu selalu terbaru.</p>
                <flux:button class="mt-3 w-full" size="sm" variant="ghost" :href="route('drivers.index')" wire:navigate>Kelola armada</flux:button>
            </div>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="arrow-top-right-on-square" :href="route('home')" wire:navigate>
                    Halaman pemesan
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="border-b border-zinc-200/80 bg-white/85 backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-950/85 lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
