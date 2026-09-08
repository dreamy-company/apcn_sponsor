<section class="w-full">
    @php
        $steps = [
            1 => ['label' => __('Initiation'), 'desc' => __('Doctor & sponsor')],
            2 => ['label' => __('Package & Items'), 'desc' => __('Scope & price')],
            3 => ['label' => __('Payment Terms'), 'desc' => __('Milestones')],
            4 => ['label' => __('Summary'), 'desc' => __('Review & save')],
        ];
        $checkedItems = collect($items)->where('checked', true);
        $selectedDoctor = collect($doctorOptions)->firstWhere('id', $doctorId);
        $selectedPackage = $packages->firstWhere('id', $packageId);
        $finalPriceValue = (float) ($finalPrice ?: 0);
        $balanced = abs($termsDifference) < 0.005;
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">
                    {{ $deal ? __('Edit Deal') : __('New Deal') }}
                </h1>
                <p class="mt-1 text-base-content/60">
                    @if ($deal)
                        {{ __('Customizing deal ') }}<span class="font-semibold">{{ $deal->deal_number }}</span>
                    @else
                        {{ __('Register a sponsorship deal negotiated with a doctor.') }}
                    @endif
                </p>
            </div>
            <x-button :label="__('Back to deals')" icon="o-arrow-left" :link="route('deals.index')" class="btn-ghost" />
        </div>

        <div class="grid gap-6 lg:grid-cols-[260px_minmax(0,1fr)]">
            {{-- Left: vertical stepper --}}
            <x-card class="h-fit lg:sticky lg:top-24">
                <ul class="space-y-1">
                    @foreach ($steps as $n => $step)
                        <li>
                            <button type="button"
                                    wire:click="goToStep({{ $n }})"
                                    @disabled($n > $maxVisited)
                                    @class([
                                        'flex w-full items-center gap-3 rounded-box border-s-2 px-3 py-2.5 text-start transition',
                                        'border-primary bg-primary-soft' => $n === $currentStep,
                                        'border-transparent hover:bg-base-200' => $n !== $currentStep,
                                        'cursor-not-allowed opacity-40' => $n > $maxVisited,
                                    ])>
                                <span @class([
                                    'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                    'bg-primary text-primary-content' => $n <= $currentStep,
                                    'bg-base-300 text-base-content/60' => $n > $currentStep,
                                ])>
                                    @if ($n < $currentStep)
                                        <x-icon name="o-check" class="size-4" />
                                    @else
                                        {{ $n }}
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold {{ $n === $currentStep ? 'text-primary' : '' }}">{{ $step['label'] }}</span>
                                    <span class="block text-xs text-base-content/50">{{ $step['desc'] }}</span>
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </x-card>

            {{-- Right: current step --}}
            <x-card>
                <form wire:submit="save">
                    {{-- STEP 1 · Initiation --}}
                    <div @class(['hidden' => $currentStep !== 1])>
                        <h2 class="text-lg font-extrabold">{{ __('Initiation') }}</h2>
                        <p class="mt-1 text-sm text-base-content/60">{{ __('Who initiated the deal and which sponsor is it with.') }}</p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-choices
                                    :label="__('Doctor (Initiator)')"
                                    wire:model="doctorId"
                                    :options="$doctorOptions"
                                    search-function="searchDoctors"
                                    searchable
                                    single
                                    :placeholder="__('Type a doctor name...')"
                                    :no-result-text="__('No doctor found — add one below.')"
                                />
                                <button type="button"
                                        wire:click="$set('showDoctorModal', true)"
                                        class="mt-1 text-xs font-semibold text-primary hover:underline">
                                    + {{ __('Add a new doctor') }}
                                </button>
                            </div>
                            <x-input :label="__('Company Name (PT)')" wire:model="companyName" placeholder="PT Contoh Sejahtera"
                                     :hint="__('The legal entity the deal is signed with.')" />
                            <x-input :label="__('Brand Name')" wire:model="brandName" :placeholder="__('Contoh Brand')"
                                     :hint="__('One brand per deal.')" />
                            <x-input :label="__('PIC Name')" wire:model="picName" :placeholder="__('Budi Santoso')" />
                            <x-input :label="__('PIC Contact')" wire:model="picContact" placeholder="+62 812 3456 7890" />
                        </div>
                    </div>

                    {{-- STEP 2 · Package & Items --}}
                    <div @class(['hidden' => $currentStep !== 2])>
                        <h2 class="text-lg font-extrabold">{{ __('Package & Items') }}</h2>
                        <p class="mt-1 text-sm text-base-content/60">{{ __('Pick a base tier, adjust items, and set the agreed price.') }}</p>

                        <div class="mt-4 space-y-4">
                            <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_180px]">
                                <div>
                                    <x-select
                                        :label="__('Base Package (Tier)')"
                                        wire:model.live="packageId"
                                        :options="$packages->map(function ($p) use ($packagesTaken, $currencyEnum) {
                                            $price = $p->getAttribute($currencyEnum->priceColumn());
                                            $label = $p->name.' — '.($price !== null ? $currencyEnum->format($price) : __('n/a'));
                                            if ($p->quota !== null) {
                                                $taken = $packagesTaken[$p->id] ?? 0;
                                                $label .= ' · '.$taken.'/'.$p->quota.($taken >= $p->quota ? ' '.__('FULL') : '');
                                            }
                                            return ['id' => $p->id, 'name' => $label];
                                        })"
                                        option-value="id"
                                        option-label="name"
                                        :placeholder="'— '.__('No base package').' —'"
                                    />
                                    <button type="button"
                                            wire:click="$set('showPackageModal', true)"
                                            class="mt-1 text-xs font-semibold text-primary hover:underline">
                                        + {{ __('New package') }}
                                    </button>
                                </div>
                                <x-select
                                    :label="__('Currency')"
                                    wire:model.live="currency"
                                    :options="collect(App\Enums\Currency::cases())->map(fn ($c) => ['id' => $c->value, 'name' => $c->label()])"
                                    option-value="id"
                                    option-label="name"
                                />
                            </div>

                            {{-- Chosen items sit right under the tier, so the scope of the
                                 deal is readable without scanning the whole catalog. --}}
                            <div>
                                <label class="fieldset-label mb-2 block text-sm font-semibold">
                                    {{ __('Selected Items') }}
                                    <span class="font-normal text-base-content/50">({{ count($selectedItemKeys) }})</span>
                                </label>

                                <div class="grid gap-2">
                                    @forelse ($selectedItemKeys as $index)
                                        @include('livewire.partials.deal-item-row', ['index' => $index])
                                    @empty
                                        <p class="rounded-box border border-dashed border-base-300 p-4 text-center text-sm text-base-content/50">
                                            {{ __('No items selected yet. Pick a package above, or choose from the list below.') }}
                                        </p>
                                    @endforelse
                                </div>
                                @error('items') <div class="mt-1 text-error">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <div class="mb-2 flex flex-wrap items-end justify-between gap-2">
                                    <label class="fieldset-label text-sm font-semibold">{{ __('Other Items') }}</label>
                                    <div class="flex items-end gap-2">
                                        <x-input
                                            wire:model.live.debounce.300ms="itemSearch"
                                            icon="o-magnifying-glass"
                                            class="w-56"
                                            :placeholder="__('Search items...')"
                                        />
                                        <x-button :label="__('New item')" icon="o-plus" type="button"
                                                  wire:click="$set('showItemModal', true)" class="btn-ghost btn-sm" />
                                    </div>
                                </div>

                                <div class="grid gap-2 sm:grid-cols-2">
                                    @forelse ($availableItemKeys as $index)
                                        @include('livewire.partials.deal-item-row', ['index' => $index])
                                    @empty
                                        <p class="text-base-content/50">
                                            {{ $itemSearch !== '' ? __('No items match your search.') : __('Every catalog item is already selected.') }}
                                        </p>
                                    @endforelse
                                </div>
                            </div>

                            {{-- Subtotal (computed) vs Final Price (negotiated) --}}
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-box bg-base-200 p-4">
                                    <div class="eyebrow text-base-content/50">{{ __('Subtotal') }}</div>
                                    <div class="mt-1 text-xl font-extrabold">
                                        <x-money :amount="$subtotal" :currency="$currencyEnum" />
                                    </div>
                                    <p class="mt-1 text-xs text-base-content/50">
                                        {{ __('Package tier + selected add-ons, at catalog prices.') }}
                                    </p>
                                    <button type="button" wire:click="useSubtotalAsFinalPrice"
                                            class="mt-2 text-xs font-semibold text-primary hover:underline">
                                        {{ __('Use as final price') }}
                                    </button>
                                </div>

                                <div>
                                    <x-money-input
                                        :label="__('Final Price').' ('.$currencyEnum->value.')'"
                                        wire:model="finalPrice"
                                        :currency="$currencyEnum"
                                        :hint="__('The price actually agreed with the doctor.')"
                                    />
                                    @php $delta = $finalPriceValue - $subtotal; @endphp
                                    @if ($finalPrice !== '' && abs($delta) >= 0.005)
                                        <p class="mt-1 text-xs {{ $delta < 0 ? 'text-warning' : 'text-success' }}">
                                            {{ $delta < 0 ? __('Discount of') : __('Above subtotal by') }}
                                            <x-money :amount="abs($delta)" :currency="$currencyEnum" class="font-semibold" />
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <x-textarea
                                    :label="__('Inclusion')"
                                    wire:model.blur="inclusion"
                                    rows="4"
                                    :placeholder="__('What the sponsor gets on this deal.')"
                                    :hint="__('One note for the whole deal — it appears on the deal page and the sponsor report.')"
                                />
                            </div>

                            <div>
                                <x-file
                                    wire:model="assets"
                                    multiple
                                    :label="__('Assets (optional)')"
                                    :hint="__('Contracts, artwork, etc. Max 50MB each. You can also add these later on the sponsor page.')"
                                />
                                @error('assets.*') <div class="mt-1 text-error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- STEP 3 · Payment Terms --}}
                    <div @class(['hidden' => $currentStep !== 3])>
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-extrabold">{{ __('Payment Terms') }}</h2>
                                <p class="mt-1 text-sm text-base-content/60">{{ __('Break the deal into payment milestones.') }}</p>
                            </div>
                            <x-button :label="__('Add Term')" icon="o-plus" type="button" wire:click="addPaymentTerm" class="btn-ghost btn-sm" />
                        </div>

                        {{-- Running balance against the final price (BR-08) --}}
                        <div @class([
                            'mt-4 grid gap-4 rounded-box border p-4 sm:grid-cols-3',
                            'border-success/40 bg-success/10' => $balanced,
                            'border-warning/40 bg-warning/10' => ! $balanced,
                        ])>
                            <div>
                                <div class="eyebrow text-base-content/50">{{ __('Final Price') }}</div>
                                <div class="mt-1 text-lg font-extrabold">
                                    <x-money :amount="$finalPriceValue" :currency="$currencyEnum" />
                                </div>
                            </div>
                            <div>
                                <div class="eyebrow text-base-content/50">{{ __('Terms Total') }}</div>
                                <div class="mt-1 text-lg font-extrabold">
                                    <x-money :amount="$termsTotal" :currency="$currencyEnum" />
                                </div>
                            </div>
                            <div>
                                <div class="eyebrow text-base-content/50">
                                    {{ $termsDifference > 0 ? __('Over by') : __('Remaining') }}
                                </div>
                                <div class="mt-1 text-lg font-extrabold {{ $balanced ? 'text-success' : 'text-warning' }}">
                                    <x-money :amount="abs($termsDifference)" :currency="$currencyEnum" />
                                </div>
                                @unless ($balanced)
                                    <p class="mt-0.5 text-xs text-warning">
                                        {{ __('Terms must match the final price before this deal can be finalized.') }}
                                    </p>
                                @endunless
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach ($paymentTerms as $index => $term)
                                <div wire:key="term-{{ $index }}" class="rounded-box border border-base-300 p-3">
                                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto_auto_auto] sm:items-end">
                                        <x-input :label="__('Description')" wire:model="paymentTerms.{{ $index }}.description" :placeholder="__('Termin 1 (DP 50%)')" />
                                        <x-input :label="__('Due Date')" wire:model="paymentTerms.{{ $index }}.due_date" type="date" />
                                        <x-input
                                            :label="__('Amount').' ('.$currencyEnum->value.')'"
                                            wire:model.live.debounce.400ms="paymentTerms.{{ $index }}.amount"
                                            type="number" min="0" step="0.01" placeholder="0"
                                        />
                                        <x-button icon="o-trash" type="button" wire:click="removePaymentTerm({{ $index }})" class="btn-ghost btn-circle text-error" :aria-label="__('Remove term')" />
                                    </div>
                                    <div class="mt-3 flex items-end gap-2">
                                        <x-textarea
                                            :label="__('Notes')"
                                            wire:model="paymentTerms.{{ $index }}.notes"
                                            rows="2"
                                            class="grow"
                                            :placeholder="__('Optional context for this milestone.')"
                                        />
                                        @unless ($balanced)
                                            <x-button :label="__('Fill remaining')" type="button"
                                                      wire:click="balanceTerm({{ $index }})" class="btn-ghost btn-sm" />
                                        @endunless
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($paymentTerms === [])
                            <p class="mt-2 text-base-content/50">{{ __('No payment terms yet.') }}</p>
                        @endif
                    </div>

                    {{-- STEP 4 · Summary --}}
                    <div @class(['hidden' => $currentStep !== 4])>
                        <h2 class="text-lg font-extrabold">{{ __('Summary') }}</h2>
                        <p class="mt-1 text-sm text-base-content/60">{{ __('Review before saving. Click a step on the left to edit.') }}</p>

                        <div class="mt-4 space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                                <div><span class="eyebrow text-base-content/50">{{ __('Doctor') }}</span><div class="mt-1 text-sm font-semibold">{{ $selectedDoctor['name'] ?? '—' }}</div></div>
                                <div><span class="eyebrow text-base-content/50">{{ __('Company') }}</span><div class="mt-1 text-sm font-semibold">{{ $companyName ?: '—' }}</div></div>
                                <div><span class="eyebrow text-base-content/50">{{ __('Brand') }}</span><div class="mt-1 text-sm font-semibold">{{ $brandName ?: '—' }}</div></div>
                                <div><span class="eyebrow text-base-content/50">{{ __('PIC') }}</span><div class="mt-1 text-sm font-semibold">{{ $picName ?: '—' }}</div><div class="text-xs text-base-content/50">{{ $picContact }}</div></div>
                                <div><span class="eyebrow text-base-content/50">{{ __('Package') }}</span><div class="mt-1 text-sm font-semibold">{{ $selectedPackage?->name ?? __('Custom') }}</div></div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="rounded-box bg-base-200 p-4">
                                    <div class="eyebrow text-base-content/50">{{ __('Subtotal') }}</div>
                                    <div class="mt-1 text-xl font-extrabold"><x-money :amount="$subtotal" :currency="$currencyEnum" /></div>
                                </div>
                                <div class="rounded-box bg-base-200 p-4">
                                    <div class="eyebrow text-base-content/50">{{ __('Final Price') }}</div>
                                    <div class="mt-1 text-xl font-extrabold"><x-money :amount="$finalPriceValue" :currency="$currencyEnum" /></div>
                                </div>
                                <div class="rounded-box bg-base-200 p-4">
                                    <div class="eyebrow text-base-content/50">{{ __('Items selected') }}</div>
                                    <div class="mt-1 text-xl font-extrabold">{{ $checkedItems->count() }}</div>
                                </div>
                                <div class="rounded-box bg-base-200 p-4">
                                    <div class="eyebrow text-base-content/50">{{ __('Payment terms total') }}</div>
                                    <div class="mt-1 text-xl font-extrabold"><x-money :amount="$termsTotal" :currency="$currencyEnum" /></div>
                                    @unless ($balanced)
                                        <div class="mt-0.5 text-xs text-warning">{{ __('does not match final price') }}</div>
                                    @endunless
                                </div>
                            </div>

                            <div>
                                <span class="eyebrow text-base-content/50">{{ __('Items') }}</span>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    @forelse ($checkedItems as $item)
                                        <span class="badge badge-soft {{ $item['is_addon'] ? 'badge-info' : 'badge-ghost' }}">
                                            {{ $item['name'] }}@if ((int) $item['quantity'] > 1) ×{{ $item['quantity'] }}@endif@if ($item['is_addon'] && $item['custom_price'] !== '') · {{ $currencyEnum->format((float) $item['custom_price'] * max(1, (int) $item['quantity'])) }}@endif
                                        </span>
                                    @empty
                                        <span class="text-sm text-base-content/50">{{ __('No items selected.') }}</span>
                                    @endforelse
                                </div>
                            </div>

                            @if (trim($inclusion) !== '')
                                <div>
                                    <span class="eyebrow text-base-content/50">{{ __('Inclusion') }}</span>
                                    <p class="mt-1 text-sm whitespace-pre-line text-base-content/70">{{ $inclusion }}</p>
                                </div>
                            @endif

                            <div>
                                <span class="eyebrow text-base-content/50">{{ __('Payment Terms') }}</span>
                                <div class="mt-1 overflow-x-auto">
                                    <table class="table table-sm">
                                        <tbody>
                                            @forelse ($paymentTerms as $term)
                                                <tr>
                                                    <td class="font-semibold">
                                                        {{ $term['description'] ?: '—' }}
                                                        @if (($term['notes'] ?? '') !== '')
                                                            <div class="text-xs font-normal text-base-content/50">{{ $term['notes'] }}</div>
                                                        @endif
                                                    </td>
                                                    <td>{{ $term['due_date'] ?: '—' }}</td>
                                                    <td><x-money :amount="(float) ($term['amount'] ?: 0)" :currency="$currencyEnum" /></td>
                                                </tr>
                                            @empty
                                                <tr><td class="text-base-content/50">{{ __('No payment terms.') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer navigation --}}
                    <div class="mt-6 flex items-center justify-between border-t border-base-300 pt-4">
                        @if ($currentStep > 1)
                            <x-button :label="__('Back').': '.$steps[$currentStep - 1]['label']" icon="o-arrow-left" type="button" wire:click="previousStep" class="btn-ghost" />
                        @else
                            <x-button :label="__('Cancel')" :link="route('deals.index')" class="btn-ghost" />
                        @endif

                        @if ($currentStep < 4)
                            <x-button :label="__('Next').': '.$steps[$currentStep + 1]['label']" icon-right="o-arrow-right" type="button" wire:click="nextStep" class="btn-primary" />
                        @else
                            <x-button :label="$deal ? __('Save Changes') : __('Create Deal')" type="submit" class="btn-primary" spinner="save" />
                        @endif
                    </div>
                </form>
            </x-card>
        </div>
    </div>

    {{-- Inline creation modals (outside the wizard form — nested forms are invalid) --}}
    <x-modal wire:model="showDoctorModal" :title="__('New Doctor')" separator>
        <p class="mb-4 text-sm text-base-content/60">
            {{ __('Doctors do not log in. A contact is required so they can be reached about this deal.') }}
        </p>
        <div class="grid gap-4">
            <x-input :label="__('Name')" wire:model="newDoctorName" :placeholder="__('Dr. Budi Santoso')" />
            <x-input :label="__('Contact')" wire:model="newDoctorPhone" placeholder="+62 812 3456 7890" />
        </div>
        <x-slot:actions>
            <x-button :label="__('Cancel')" wire:click="$set('showDoctorModal', false)" class="btn-ghost" />
            <x-button :label="__('Add Doctor')" wire:click="createDoctor" class="btn-primary" spinner="createDoctor" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="showItemModal" :title="__('New Item')" separator>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input :label="__('Name')" wire:model="newItemName" class="sm:col-span-2" />
            <x-input :label="__('Type (optional)')" wire:model="newItemType" :placeholder="__('Branding, Symposium, ...')" />
            <x-input :label="__('Quota')" wire:model="newItemQuota" type="number" min="0" :hint="__('Leave blank for unlimited.')" />
            <x-money-input :label="__('Price (IDR)')" wire:model="newItemPriceIdr" currency="IDR" />
            <x-money-input :label="__('Price (USD)')" wire:model="newItemPriceUsd" currency="USD" />
            <x-textarea :label="__('Inclusion')" wire:model="newItemInclusion" rows="2" class="sm:col-span-2"
                        :placeholder="__('What the sponsor gets for this item.')" />
            <x-toggle :label="__('Requires material from the sponsor')" wire:model="newItemRequiresMaterial" class="sm:col-span-2" />
        </div>
        <x-slot:actions>
            <x-button :label="__('Cancel')" wire:click="$set('showItemModal', false)" class="btn-ghost" />
            <x-button :label="__('Add Item')" wire:click="createItem" class="btn-primary" spinner="createItem" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="showPackageModal" :title="__('New Package')" separator>
        <p class="mb-4 text-sm text-base-content/60">
            {{ __('The items currently selected become this package\'s contents.') }}
        </p>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input :label="__('Name')" wire:model="newPackageName" class="sm:col-span-2" />
            <x-money-input :label="__('Price (IDR)')" wire:model="newPackagePriceIdr" currency="IDR" />
            <x-money-input :label="__('Price (USD)')" wire:model="newPackagePriceUsd" currency="USD" />
            <x-input :label="__('Quota')" wire:model="newPackageQuota" type="number" min="0" :hint="__('Leave blank for unlimited.')" class="sm:col-span-2" />
        </div>
        <x-slot:actions>
            <x-button :label="__('Cancel')" wire:click="$set('showPackageModal', false)" class="btn-ghost" />
            <x-button :label="__('Add Package')" wire:click="createPackage" class="btn-primary" spinner="createPackage" />
        </x-slot:actions>
    </x-modal>
</section>
