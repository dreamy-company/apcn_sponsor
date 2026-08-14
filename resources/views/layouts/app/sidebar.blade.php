<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        {{-- Dark navy sidebar (always navy, regardless of app theme) --}}
        <flux:sidebar sticky collapsible="mobile" class="border-e border-white/10! bg-navy-950!">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item
                        icon="home"
                        :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')"
                        wire:navigate
                        class="text-slate-300/80! hover:bg-white/5! hover:text-white! data-current:bg-teal-500/15! dark:data-current:bg-teal-500/15! data-current:border-teal-400/30! dark:data-current:border-teal-400/30! data-current:text-teal-300! dark:data-current:text-teal-300! hover:data-current:text-teal-300! dark:hover:data-current:text-teal-300! data-current:shadow-sm!"
                    >
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="briefcase"
                        :href="route('deals.index')"
                        :current="request()->routeIs('deals.*')"
                        wire:navigate
                        class="text-slate-300/80! hover:bg-white/5! hover:text-white! data-current:bg-teal-500/15! dark:data-current:bg-teal-500/15! data-current:border-teal-400/30! dark:data-current:border-teal-400/30! data-current:text-teal-300! dark:data-current:text-teal-300! hover:data-current:text-teal-300! dark:hover:data-current:text-teal-300! data-current:shadow-sm!"
                    >
                        {{ __('Deals') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @if (auth()->user()->isJ4u())
                    <flux:sidebar.group :heading="__('Management')" class="grid">
                        <flux:sidebar.item
                            icon="list-bullet"
                            :href="route('catalog.items.index')"
                            :current="request()->routeIs('catalog.items.*')"
                            wire:navigate
                            class="text-slate-300/80! hover:bg-white/5! hover:text-white! data-current:bg-teal-500/15! dark:data-current:bg-teal-500/15! data-current:border-teal-400/30! dark:data-current:border-teal-400/30! data-current:text-teal-300! dark:data-current:text-teal-300! hover:data-current:text-teal-300! dark:hover:data-current:text-teal-300! data-current:shadow-sm!"
                        >
                            {{ __('Items') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="tag"
                            :href="route('catalog.packages.index')"
                            :current="request()->routeIs('catalog.packages.*')"
                            wire:navigate
                            class="text-slate-300/80! hover:bg-white/5! hover:text-white! data-current:bg-teal-500/15! dark:data-current:bg-teal-500/15! data-current:border-teal-400/30! dark:data-current:border-teal-400/30! data-current:text-teal-300! dark:data-current:text-teal-300! hover:data-current:text-teal-300! dark:hover:data-current:text-teal-300! data-current:shadow-sm!"
                        >
                            {{ __('Packages') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="users"
                            :href="route('users.index')"
                            :current="request()->routeIs('users.*')"
                            wire:navigate
                            class="text-slate-300/80! hover:bg-white/5! hover:text-white! data-current:bg-teal-500/15! dark:data-current:bg-teal-500/15! data-current:border-teal-400/30! dark:data-current:border-teal-400/30! data-current:text-teal-300! dark:data-current:text-teal-300! hover:data-current:text-teal-300! dark:hover:data-current:text-teal-300! data-current:shadow-sm!"
                        >
                            {{ __('Users') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item
                    icon="cog"
                    :href="route('profile.edit')"
                    :current="request()->routeIs('profile.edit')"
                    wire:navigate
                    class="text-slate-300/80! hover:bg-white/5! hover:text-white! data-current:bg-teal-500/15! dark:data-current:bg-teal-500/15! data-current:border-teal-400/30! dark:data-current:border-teal-400/30! data-current:text-teal-300! dark:data-current:text-teal-300! hover:data-current:text-teal-300! dark:hover:data-current:text-teal-300! data-current:shadow-sm!"
                >
                    {{ __('Settings') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank" class="text-slate-300/80! hover:bg-white/5! hover:text-white!">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank" class="text-slate-300/80! hover:bg-white/5! hover:text-white!">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        @include('layouts.app.mobile-user-menu')

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
