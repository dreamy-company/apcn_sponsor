<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('Users') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Doctors and committee members with app access.') }}</flux:text>
            </div>
            <flux:button href="{{ route('users.create') }}" wire:navigate icon="plus">
                {{ __('New User') }}
            </flux:button>
        </div>

        <flux:card class="space-y-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search users...') }}"
                class="max-w-sm"
            />

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Email') }}</flux:table.column>
                    <flux:table.column>{{ __('Role') }}</flux:table.column>
                    <flux:table.column>{{ __('Deals') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($users as $user)
                        <flux:table.row :key="$user->id">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar :name="$user->name" :initials="$user->initials()" size="xs" />
                                    <span class="font-medium">{{ $user->name }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $user->email }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$user->isJ4u() ? 'violet' : 'sky'" inset="top bottom">
                                    {{ $user->role->label() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $user->deals_count }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button size="xs" variant="subtle" icon="pencil" :href="route('users.edit', $user)" wire:navigate />
                                    @if ($user->id !== auth()->id())
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            icon="trash"
                                            wire:click="delete({{ $user->id }})"
                                            wire:confirm="{{ __('Delete this user?') }}"
                                        />
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center text-neutral-500">{{ __('No users yet.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</section>
