<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased">
        <div class="grid min-h-svh lg:grid-cols-2">
            {{-- LEFT: deep-navy hero (hidden below lg so the form is full-width on phones) --}}
            <aside class="relative hidden overflow-hidden bg-gradient-splash p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
                {{-- Decorative circles --}}
                <div class="pointer-events-none absolute -right-24 top-16 h-96 w-96 rounded-full bg-white/[0.06] blur-xl"></div>
                <div class="pointer-events-none absolute -bottom-28 -left-24 h-80 w-80 rounded-full bg-white/[0.05] blur-xl"></div>

                {{-- Top: brand lockup --}}
                <div class="relative">
                    <x-app-logo class="h-11 w-auto" />
                </div>

                {{-- Middle: hero copy --}}
                <div class="relative">
                    <span class="eyebrow inline-flex items-center rounded-full border border-white/25 px-4 py-2 text-white/80">
                        {{ __('Welcome to') }}
                    </span>

                    <h1 class="mt-6 text-6xl font-extrabold leading-none tracking-tight xl:text-7xl">APCN 2027</h1>

                    <p class="mt-6 max-w-md text-lg leading-relaxed text-white/70">
                        {{ __('J4U Sponsorship Deal Management — the single source of truth for sponsor deals.') }}
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold">
                            <x-icon name="o-calendar" class="h-4 w-4" />
                            9–11 December 2027
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold">
                            <x-icon name="o-globe-alt" class="h-4 w-4" />
                            Bali, Indonesia
                        </span>
                    </div>
                </div>

                {{-- Bottom: footer --}}
                <p class="relative text-sm text-white/50">
                    &copy; {{ date('Y') }} APCN 2027 · Asia Pacific Society of Nephrology
                </p>
            </aside>

            {{-- RIGHT: form panel --}}
            <main class="relative flex items-center justify-center bg-base-100 p-6 sm:p-10">
                <div class="absolute right-6 top-6">
                    <x-theme-toggle class="text-base-content/60" />
                </div>

                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </main>
        </div>

        <x-toast />
    </body>
</html>
