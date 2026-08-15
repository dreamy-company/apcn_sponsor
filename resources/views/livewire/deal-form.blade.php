<section class="w-full">
    @php
        $steps = [
            1 => ['label' => __('Initiation'), 'desc' => __('Doctor & sponsor')],
            2 => ['label' => __('Package & Items'), 'desc' => __('Scope & price')],
            3 => ['label' => __('Payment Terms'), 'desc' => __('Milestones')],
            4 => ['label' => __('Summary'), 'desc' => __('Review & save')],
        ];
        $checkedItems = collect($items)->where('checked', true);
        $selectedDoctor = $doctors->firstWhere('id', $doctorId);
        $selectedPackage = $packages->firstWhere('id', $packageId);
        $termsTotal = collect($paymentTerms)->sum(fn ($t) => (float) ($t['amount'] ?: 0));
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
                            <x-select
                                :label="__('Doctor (Initiator)')"
                                wire:model="doctorId"
                                :options="$doctors"
                                option-value="id"
                                option-label="name"
                                :placeholder="__('Select doctor...')"
                            />
                            <x-input :label="__('Company Name')" wire:model="companyName" placeholder="PT Contoh Sejahtera" />
                            <x-input :label="__('PIC Name')" wire:model="picName" :placeholder="__('Budi Santoso')" />
                            <x-input :label="__('PIC Contact')" wire:model="picContact" placeholder="+62 812 3456 7890" />
                        </div>
                    </div>

                    {{-- STEP 2 · Package & Items --}}
                    <div @class(['hidden' => $currentStep !== 2])>
                        <h2 class="text-lg font-extrabold">{{ __('Package & Items') }}</h2>
                        <p class="mt-1 text-sm text-base-content/60">{{ __('Pick a base tier, adjust items, and set the agreed price.') }}</p>

                        <div class="mt-4 space-y-4">
                            <x-select
                                :label="__('Base Package (Tier)')"
                                wire:model.live="packageId"
                                :options="$packages->map(function ($p) use ($packagesTaken) {
                                    $label = $p->name.' — Rp '.number_format((float) $p->default_price, 0, ',', '.');
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

                            <div>
                                <label class="fieldset-label mb-2 block text-sm font-semibold">{{ __('Items') }}</label>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @forelse ($items as $index => $item)
                                        @php
                                            $itemQuota = $item['quota'] ?? null;
                                            $taken = $itemsTaken[$item['item_id']] ?? 0;
                                            $full = $itemQuota !== null && $taken >= $itemQuota;
                                            $blocked = $full && ! $item['checked'];
                                        @endphp
                                        <label @class([
                                            'flex items-start justify-between gap-3 rounded-box border p-3 transition',
                                            'cursor-pointer' => ! $blocked,
                                            'cursor-not-allowed opacity-60' => $blocked,
                                            'border-primary bg-primary-soft' => $item['checked'],
                                            'border-base-300 hover:border-primary/40' => ! $item['checked'] && ! $blocked,
                                            'border-base-300' => $blocked,
                                        ])>
                                            <div class="flex items-start gap-3">
                                                <x-checkbox wire:model.live="items.{{ $index }}.checked" @disabled($blocked) />
                                                <div>
                                                    <div class="text-sm font-semibold">{{ $item['name'] }}</div>
                                                    <div class="flex flex-wrap items-center gap-1.5 text-xs text-base-content/50">
                                                        <span>{{ $item['is_addon'] ? __('Add-on') : __('Package item') }}</span>
                                                        @if ($itemQuota !== null)
                                                            <span class="badge badge-soft badge-xs {{ $full ? 'badge-error' : 'badge-ghost' }}">
                                                                {{ $taken }}/{{ $itemQuota }} {{ $full ? __('Full') : __('taken') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($item['is_addon'])
                                                <x-input
                                                    wire:model="items.{{ $index }}.custom_price"
                                                    class="w-28"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    :placeholder="__('Price')"
                                                    @click.stop
                                                />
                                            @endif
                                        </label>
                                    @empty
                                        <p class="text-base-content/50">{{ __('No items in the catalog yet.') }}</p>
                                    @endforelse
                                </div>
                                @error('items') <div class="mt-1 text-error">{{ $message }}</div> @enderror
                            </div>

                            <x-input :label="__('Final Price (IDR)')" wire:model="finalPrice" type="number" min="0" step="0.01" placeholder="0" prefix="Rp" />

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

                        <div class="mt-4 space-y-3">
                            @foreach ($paymentTerms as $index => $term)
                                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto_auto_auto] sm:items-end">
                                    <x-input :label="__('Description')" wire:model="paymentTerms.{{ $index }}.description" :placeholder="__('Termin 1 (DP 50%)')" />
                                    <x-input :label="__('Due Date')" wire:model="paymentTerms.{{ $index }}.due_date" type="date" />
                                    <x-input :label="__('Amount (IDR)')" wire:model="paymentTerms.{{ $index }}.amount" type="number" min="0" step="0.01" placeholder="0" />
                                    <x-button icon="o-trash" type="button" wire:click="removePaymentTerm({{ $index }})" class="btn-ghost btn-circle text-error" :aria-label="__('Remove term')" />
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
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div><span class="eyebrow text-base-content/50">{{ __('Doctor') }}</span><div class="mt-1 text-sm font-semibold">{{ $selectedDoctor?->name ?? '—' }}</div></div>
                                <div><span class="eyebrow text-base-content/50">{{ __('Company') }}</span><div class="mt-1 text-sm font-semibold">{{ $companyName ?: '—' }}</div></div>
                                <div><span class="eyebrow text-base-content/50">{{ __('PIC') }}</span><div class="mt-1 text-sm font-semibold">{{ $picName ?: '—' }}</div><div class="text-xs text-base-content/50">{{ $picContact }}</div></div>
                                <div><span class="eyebrow text-base-content/50">{{ __('Package') }}</span><div class="mt-1 text-sm font-semibold">{{ $selectedPackage?->name ?? __('Custom') }}</div></div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div class="rounded-box bg-base-200 p-4">
                                    <div class="eyebrow text-base-content/50">{{ __('Final Price') }}</div>
                                    <div class="mt-1 text-xl font-extrabold">Rp {{ number_format((float) ($finalPrice ?: 0), 0, ',', '.') }}</div>
                                </div>
                                <div class="rounded-box bg-base-200 p-4">
                                    <div class="eyebrow text-base-content/50">{{ __('Items selected') }}</div>
                                    <div class="mt-1 text-xl font-extrabold">{{ $checkedItems->count() }}</div>
                                </div>
                                <div class="rounded-box bg-base-200 p-4">
                                    <div class="eyebrow text-base-content/50">{{ __('Payment terms total') }}</div>
                                    <div class="mt-1 text-xl font-extrabold">Rp {{ number_format($termsTotal, 0, ',', '.') }}</div>
                                    @if ($termsTotal != (float) ($finalPrice ?: 0))
                                        <div class="mt-0.5 text-xs text-warning">{{ __('differs from final price') }}</div>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <span class="eyebrow text-base-content/50">{{ __('Items') }}</span>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    @forelse ($checkedItems as $item)
                                        <span class="badge badge-soft {{ $item['is_addon'] ? 'badge-info' : 'badge-ghost' }}">
                                            {{ $item['name'] }}{{ $item['is_addon'] && $item['custom_price'] !== '' ? ' · Rp '.number_format((float) $item['custom_price'], 0, ',', '.') : '' }}
                                        </span>
                                    @empty
                                        <span class="text-sm text-base-content/50">{{ __('No items selected.') }}</span>
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <span class="eyebrow text-base-content/50">{{ __('Payment Terms') }}</span>
                                <div class="mt-1 overflow-x-auto">
                                    <table class="table table-sm">
                                        <tbody>
                                            @forelse ($paymentTerms as $term)
                                                <tr>
                                                    <td class="font-semibold">{{ $term['description'] ?: '—' }}</td>
                                                    <td>{{ $term['due_date'] ?: '—' }}</td>
                                                    <td>Rp {{ number_format((float) ($term['amount'] ?: 0), 0, ',', '.') }}</td>
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
</section>
