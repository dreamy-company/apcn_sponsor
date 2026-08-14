<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ __('Doctors') }}</h1>
                <p class="mt-1 text-base-content/60">{{ __('Initiators who bring in sponsors. Each has a public read-only link.') }}</p>
            </div>
            <x-button :label="__('New Doctor')" icon="o-plus" :link="route('doctors.create')" class="btn-primary" />
        </div>

        {{-- Global public access code --}}
        <x-card>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex-1">
                    <h2 class="text-lg font-extrabold">{{ __('Public access code') }}</h2>
                    <p class="mt-1 text-sm text-base-content/60">{{ __('A single code doctors enter to open any shared link.') }}</p>
                </div>
                <form wire:submit="saveAccessCode" class="flex items-end gap-2">
                    <x-input wire:model="accessCode" :label="__('Code')" icon="o-key" class="w-48" />
                    <x-button :label="__('Save')" type="submit" class="btn-primary" spinner="saveAccessCode" />
                </form>
            </div>
        </x-card>

        <x-card>
            <x-input
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                :placeholder="__('Search doctors...')"
                class="max-w-sm"
            />

            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Contact') }}</th>
                            <th>{{ __('Deals') }}</th>
                            <th>{{ __('Public link') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($doctors as $doctor)
                            <tr wire:key="{{ $doctor->id }}">
                                <td class="font-semibold">{{ $doctor->name }}</td>
                                <td>{{ $doctor->phone ?? '—' }}</td>
                                <td>{{ $doctor->deals_count }}</td>
                                <td>
                                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                                        <button type="button"
                                                class="btn btn-soft btn-primary btn-xs"
                                                @click="navigator.clipboard.writeText('{{ $doctor->publicUrl() }}'); copied = true; setTimeout(() => copied = false, 1500)">
                                            <x-icon name="o-link" class="h-3.5 w-3.5" />
                                            <span x-show="!copied">{{ __('Copy link') }}</span>
                                            <span x-show="copied" x-cloak>{{ __('Copied!') }}</span>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex gap-1">
                                        <x-button icon="o-pencil" :link="route('doctors.edit', $doctor)" class="btn-ghost btn-sm btn-square" />
                                        <x-button
                                            icon="o-trash"
                                            wire:click="delete({{ $doctor->id }})"
                                            wire:confirm="{{ __('Delete this doctor?') }}"
                                            class="btn-ghost btn-sm btn-square text-error"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-base-content/50">{{ __('No doctors yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</section>
