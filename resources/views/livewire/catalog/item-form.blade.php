<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $item ? __('Edit Item') : __('New Item') }}</flux:heading>
                <flux:text class="mt-1">{{ __('An item is a single sponsorship deliverable.') }}</flux:text>
            </div>
            <flux:button variant="ghost" href="{{ route('catalog.items.index') }}" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
        @endif

        <flux:card>
            <form wire:submit="save" class="space-y-4">
                <flux:fieldset>
                    <flux:field>
                        <flux:label>{{ __('Name') }}</flux:label>
                        <flux:input wire:model="name" placeholder="{{ __('Booth 3x3m') }}" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Type') }} <flux:text class="text-xs">({{ __('optional') }})</flux:text></flux:label>
                        <flux:input wire:model="type" placeholder="{{ __('booth, symposium, naming, advertising, digital...') }}" />
                        <flux:error name="type" />
                    </flux:field>

                    <flux:field>
                        <div class="flex items-center justify-between gap-4 rounded-lg border border-neutral-200 p-3 dark:border-neutral-700">
                            <div>
                                <div class="text-sm font-medium">{{ __('Requires Material') }}</div>
                                <div class="text-xs text-neutral-500">{{ __('Generates a material checklist item when a deal is finalized.') }}</div>
                            </div>
                            <flux:switch wire:model="requiresMaterial" />
                        </div>
                        <flux:error name="requiresMaterial" />
                    </flux:field>
                </flux:fieldset>

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" href="{{ route('catalog.items.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ $item ? __('Save Changes') : __('Create Item') }}
                    </flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</section>
