@if (auth()->user()?->isDoctor())
    <x-layouts::app.sidebar-doctor :title="$title ?? null">
        <flux:main class="bg-zinc-50 dark:bg-zinc-900">
            {{ $slot }}
        </flux:main>
    </x-layouts::app.sidebar-doctor>
@else
    <x-layouts::app.sidebar :title="$title ?? null">
        <flux:main class="bg-zinc-50 dark:bg-zinc-900">
            {{ $slot }}
        </flux:main>
    </x-layouts::app.sidebar>
@endif
