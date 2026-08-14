<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-gradient-splash antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-4">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2" wire:navigate>
                    <x-app-logo class="h-14 w-auto" />
                    <span class="sr-only">{{ config('app.name', 'APCN 2027') }}</span>
                </a>

                <x-card class="bg-base-100/95 shadow-soft-lg backdrop-blur">
                    {{ $slot }}
                </x-card>
            </div>
        </div>

        <x-toast />
    </body>
</html>
