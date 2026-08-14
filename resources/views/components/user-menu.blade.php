@php($user = auth()->user())

{{-- User dropdown for the navy chrome. The dropdown surface is a light base-100
     panel (see the unlayered chrome override in app.css), so text reads dark. --}}
<x-dropdown right>
    <x-slot:trigger>
        <button type="button" class="btn btn-ghost btn-sm gap-2 !text-white hover:!bg-white/10" data-test="user-menu-button">
            <span class="avatar avatar-placeholder">
                <span class="w-8 rounded-full bg-primary text-primary-content">
                    <span class="text-xs font-bold">{{ $user->initials() }}</span>
                </span>
            </span>
            <span class="hidden text-sm font-semibold sm:inline">{{ $user->name }}</span>
            <x-icon name="o-chevron-down" class="h-4 w-4" />
        </button>
    </x-slot:trigger>

    <li class="menu-title">
        <div class="text-sm font-bold text-base-content">{{ $user->name }}</div>
        <div class="truncate text-xs font-normal opacity-60">{{ $user->email }}</div>
    </li>
    <x-menu-separator />
    <x-menu-item title="{{ __('Settings') }}" icon="o-cog-6-tooth" :link="route('profile.edit')" />
    <li>
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="flex w-full items-center gap-2" data-test="logout-button">
                <x-icon name="o-arrow-right-start-on-rectangle" class="h-4 w-4" />
                {{ __('Log out') }}
            </button>
        </form>
    </li>
</x-dropdown>
