<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-base-200 font-sans antialiased">
        @php($user = auth()->user())

        {{-- Mobile top navbar (deep navy chrome) --}}
        <x-nav sticky full-width class="apcn-topbar lg:hidden">
            <x-slot:brand>
                <label for="main-drawer" class="me-2 cursor-pointer p-1">
                    <x-icon name="o-bars-3" class="h-6 w-6 text-white" />
                </label>
                <a href="{{ route('dashboard') }}" wire:navigate>
                    <x-app-logo class="h-9 w-auto" />
                </a>
            </x-slot:brand>
            <x-slot:actions>
                <x-theme-toggle class="text-white" />
                <x-user-menu />
            </x-slot:actions>
        </x-nav>

        <x-main full-width>
            {{-- SIDEBAR — deep navy in both themes --}}
            <x-slot:sidebar drawer="main-drawer" collapsible class="apcn-sidebar">
                <a href="{{ route('dashboard') }}" wire:navigate
                   class="hidden-when-collapsed flex items-center justify-center px-5 py-6">
                    <x-app-logo class="h-16 w-auto" />
                </a>

                <x-menu activate-by-route active-bg-color="bg-primary">
                    <x-menu-title title="{{ __('Platform') }}" />
                    <x-menu-item title="{{ __('Dashboard') }}" icon="o-home"
                        :link="route('dashboard')" :active="request()->routeIs('dashboard')" />
                    <x-menu-item title="{{ __('Deals') }}" icon="o-briefcase"
                        :link="route('deals.index')" :active="request()->routeIs('deals.*')" />

                    <x-menu-title title="{{ __('Management') }}" />
                    <x-menu-item title="{{ __('Doctors') }}" icon="o-user-group"
                        :link="route('doctors.index')" :active="request()->routeIs('doctors.*')" />
                    <x-menu-item title="{{ __('Items') }}" icon="o-list-bullet"
                        :link="route('catalog.items.index')" :active="request()->routeIs('catalog.items.*')" />
                    <x-menu-item title="{{ __('Packages') }}" icon="o-tag"
                        :link="route('catalog.packages.index')" :active="request()->routeIs('catalog.packages.*')" />
                    <x-menu-item title="{{ __('Users') }}" icon="o-users"
                        :link="route('users.index')" :active="request()->routeIs('users.*')" />
                </x-menu>

                <div class="flex-grow"></div>

                <x-menu activate-by-route active-bg-color="bg-primary">
                    <x-menu-item title="{{ __('Settings') }}" icon="o-cog-6-tooth"
                        :link="route('profile.edit')" :active="request()->routeIs('profile.edit')" />
                </x-menu>
            </x-slot:sidebar>

            {{-- CONTENT --}}
            <x-slot:content>
                {{-- Desktop top navbar (deep navy chrome) --}}
                <div class="apcn-topbar sticky top-0 z-20 -mx-5 -mt-5 mb-6 hidden items-center gap-4 border-b px-6 py-3 lg:-mx-10 lg:flex lg:px-10">
                    <div class="flex-1"></div>
                    <span class="font-mono text-sm text-white/80"
                          x-data="{ t: '' }"
                          x-init="t = new Date().toLocaleTimeString(); setInterval(() => t = new Date().toLocaleTimeString(), 1000)"
                          x-text="t"></span>
                    <x-theme-toggle class="text-white" />
                    <x-user-menu />
                </div>

                {{ $slot }}
            </x-slot:content>
        </x-main>

        {{-- Toast area (replaces Flux toast) --}}
        <x-toast />
    </body>
</html>
