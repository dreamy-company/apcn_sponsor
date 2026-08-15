<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-base-200 font-sans antialiased">
        <header class="apcn-topbar">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-3">
                <x-app-logo class="h-9 w-auto" />
                <span class="eyebrow text-white/70">{{ __('Sponsorship Portal') }}</span>
            </div>
        </header>

        <main class="mx-auto max-w-4xl p-4 md:p-8">
            {{ $slot }}
        </main>

        <footer class="mx-auto max-w-4xl px-4 pb-10 text-center text-xs text-base-content/40">
            &copy; {{ date('Y') }} APCN 2027 — J4U Sponsorship
        </footer>
    </body>
</html>
