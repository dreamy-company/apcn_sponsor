<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ __('Administrators') }}</h1>
                <p class="mt-1 text-base-content/60">{{ __('J4U committee members who can sign in and manage the platform.') }}</p>
            </div>
            <x-button :label="__('New Administrator')" icon="o-plus" :link="route('users.create')" class="btn-primary" />
        </div>

        <x-card>
            <x-input
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                :placeholder="__('Search administrators...')"
                class="max-w-sm"
            />

            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr wire:key="{{ $user->id }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="avatar avatar-placeholder">
                                            <span class="w-8 rounded-full bg-primary text-primary-content">
                                                <span class="text-xs font-bold">{{ $user->initials() }}</span>
                                            </span>
                                        </span>
                                        <span class="font-semibold">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <div class="flex gap-1">
                                        <x-button icon="o-pencil" :link="route('users.edit', $user)" class="btn-ghost btn-sm btn-square" />
                                        @if ($user->id !== auth()->id())
                                            <x-button
                                                icon="o-trash"
                                                wire:click="delete({{ $user->id }})"
                                                wire:confirm="{{ __('Delete this administrator?') }}"
                                                class="btn-ghost btn-sm btn-square text-error"
                                            />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-base-content/50">{{ __('No administrators yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</section>
