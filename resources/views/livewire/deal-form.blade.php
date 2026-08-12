<section class="w-full">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $deal ? __('Edit Deal') : __('New Deal') }}</flux:heading>
                <flux:text class="mt-1">
                    @if ($deal)
                        {{ __('Customizing deal ') }}<span class="font-medium">{{ $deal->deal_number }}</span>
                    @else
                        {{ __('Register a sponsorship deal negotiated with a doctor.') }}
                    @endif
                </flux:text>
            </div>
            <flux:button variant="ghost" href="{{ route('deals.index') }}" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
        @endif

        <form wire:submit="save" class="space-y-6">
            {{-- 1. Initiation --}}
            <flux:card>
                <flux:heading size="lg">1 · {{ __('Initiation') }}</flux:heading>

                <flux:fieldset>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Doctor (Initiator)') }}</flux:label>
                            <flux:select wire:model="doctorId" placeholder="{{ __('Select doctor...') }}">
                                @foreach ($doctors as $doctor)
                                    <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="doctorId" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Company Name') }}</flux:label>
                            <flux:input wire:model="companyName" placeholder="PT Contoh Sejahtera" />
                            <flux:error name="companyName" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('PIC Name') }}</flux:label>
                            <flux:input wire:model="picName" placeholder="{{ __('Budi Santoso') }}" />
                            <flux:error name="picName" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('PIC Contact') }}</flux:label>
                            <flux:input wire:model="picContact" placeholder="+62 812 3456 7890" />
                            <flux:error name="picContact" />
                        </flux:field>
                    </div>
                </flux:fieldset>
            </flux:card>

            {{-- 2. Package & Items --}}
            <flux:card>
                <flux:heading size="lg">2 · {{ __('Package & Items') }}</flux:heading>

                <flux:fieldset>
                    <flux:field>
                        <flux:label>{{ __('Base Package (Tier)') }}</flux:label>
                        <flux:select wire:model.live="packageId" placeholder="{{ __('No base package') }}">
                            <option value="">— {{ __('No base package') }} —</option>
                            @foreach ($packages as $package)
                                <option value="{{ $package->id }}">
                                    {{ $package->name }} — Rp {{ number_format((float) $package->default_price, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </flux:select>
                        <flux:error name="packageId" />
                    </flux:field>

                    <flux:separator class="my-4" />

                    <flux:field>
                        <flux:label>{{ __('Items') }}</flux:label>

                        <div class="space-y-2">
                            @forelse ($items as $index => $item)
                                <div class="flex items-center justify-between gap-4 rounded-lg border border-neutral-200 p-3 dark:border-neutral-700">
                                    <div class="flex items-center gap-3">
                                        <flux:checkbox wire:model="items.{{ $index }}.checked" />
                                        <div>
                                            <div class="text-sm font-medium">{{ $item['name'] }}</div>
                                            <div class="text-xs text-neutral-500">
                                                @if ($item['is_addon'])
                                                    {{ __('Add-on') }} — {{ __('optional custom price') }}
                                                @else
                                                    {{ __('Package item') }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    @if ($item['is_addon'])
                                        <flux:input
                                            wire:model="items.{{ $index }}.custom_price"
                                            class="w-40"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="{{ __('Custom price') }}"
                                        />
                                    @endif
                                </div>
                            @empty
                                <flux:text class="text-neutral-500">{{ __('No items in the catalog yet.') }}</flux:text>
                            @endforelse
                        </div>

                        <flux:error name="items" />
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label>{{ __('Final Price (IDR)') }}</flux:label>
                        <flux:input wire:model="finalPrice" type="number" min="0" step="0.01" placeholder="0" />
                        <flux:error name="finalPrice" />
                    </flux:field>
                </flux:fieldset>
            </flux:card>

            {{-- 3. Payment Terms --}}
            <flux:card>
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">3 · {{ __('Payment Terms') }}</flux:heading>
                    <flux:button type="button" variant="ghost" wire:click="addPaymentTerm" icon="plus">
                        {{ __('Add Term') }}
                    </flux:button>
                </div>

                <flux:fieldset>
                    <div class="space-y-3">
                        @foreach ($paymentTerms as $index => $term)
                            <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto_auto_auto] sm:items-end">
                                <flux:field>
                                    <flux:label>{{ __('Description') }}</flux:label>
                                    <flux:input wire:model="paymentTerms.{{ $index }}.description" placeholder="{{ __('Termin 1 (DP 50%)') }}" />
                                    <flux:error name="paymentTerms.{{ $index }}.description" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>{{ __('Due Date') }}</flux:label>
                                    <flux:input wire:model="paymentTerms.{{ $index }}.due_date" type="date" />
                                    <flux:error name="paymentTerms.{{ $index }}.due_date" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>{{ __('Amount (IDR)') }}</flux:label>
                                    <flux:input wire:model="paymentTerms.{{ $index }}.amount" type="number" min="0" step="0.01" placeholder="0" />
                                    <flux:error name="paymentTerms.{{ $index }}.amount" />
                                </flux:field>

                                <flux:button
                                    type="button"
                                    variant="subtle"
                                    icon="trash"
                                    wire:click="removePaymentTerm({{ $index }})"
                                    aria-label="{{ __('Remove term') }}"
                                />
                            </div>
                        @endforeach
                    </div>

                    @if ($paymentTerms === [])
                        <flux:text class="mt-2 text-neutral-500">{{ __('No payment terms yet.') }}</flux:text>
                    @endif
                </flux:fieldset>
            </flux:card>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" href="{{ route('deals.index') }}" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="submit">
                    {{ $deal ? __('Save Changes') : __('Create Deal') }}
                </flux:button>
            </div>
        </form>
    </div>
</section>
