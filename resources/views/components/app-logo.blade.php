@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand {{ $attributes }} class="flex-1! justify-center! h-auto!">
        <x-slot name="logo" class="h-20 w-auto in-data-flux-sidebar-collapsed-desktop:hidden!">
            <img
                src="{{ asset('img/logowhiteapcn.png') }}"
                alt="{{ config('app.name', 'Laravel') }}"
                class="h-20 w-auto"
            />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'Laravel')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif
