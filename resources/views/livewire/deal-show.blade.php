<section class="w-full">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">{{ $deal->deal_number }}</h1>
                <x-tier-badge :package="$deal->package" size="lg" />
                <span class="badge badge-soft {{ $deal->status === \App\Enums\DealStatus::Finalized ? 'badge-success' : 'badge-ghost' }}">
                    {{ $deal->status->label() }}
                </span>
            </div>

            <div class="flex gap-3">
                <x-button :label="__('Back')" icon="o-arrow-left" :link="route('deals.index')" class="btn-ghost" />

                @if (auth()->user()->isJ4u() && $deal->status === \App\Enums\DealStatus::Draft)
                    <x-button :label="__('Edit')" icon="o-pencil" :link="route('deals.edit', $deal)" class="btn-ghost" />
                    <x-button
                        :label="__('Finalize')"
                        class="btn-primary"
                        wire:click="finalize"
                        wire:confirm="{{ __('Finalize this deal? The material checklist will be generated.') }}"
                    />
                @endif
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid gap-4 md:grid-cols-3">
            <x-card>
                <h3 class="eyebrow text-base-content/50">{{ __('Final Price') }}</h3>
                <div class="mt-2 text-2xl font-extrabold">Rp {{ number_format((float) $deal->final_price, 0, ',', '.') }}</div>
                <p class="mt-1 text-sm text-base-content/60">{{ $deal->package?->name ?? __('No base package') }}</p>
            </x-card>

            <x-card>
                <h3 class="eyebrow text-base-content/50">{{ __('Payment Progress') }}</h3>
                <div class="mt-2 text-2xl font-extrabold">
                    Rp {{ number_format((float) $totalPaid, 0, ',', '.') }}
                    <span class="text-base font-normal text-base-content/50">/ Rp {{ number_format((float) $totalTerms, 0, ',', '.') }}</span>
                </div>
                <p class="mt-1 text-sm text-base-content/60">{{ $deal->paymentTerms->where('status', \App\Enums\PaymentStatus::Paid)->count() }} / {{ $deal->paymentTerms->count() }} {{ __('terms paid') }}</p>
            </x-card>

            <x-card>
                <h3 class="eyebrow text-base-content/50">{{ __('Material Checklist') }}</h3>
                <div class="mt-2 text-2xl font-extrabold">
                    {{ $materialReceived }}
                    <span class="text-base font-normal text-base-content/50">/ {{ $materialCount }} {{ __('received') }}</span>
                </div>
                <p class="mt-1 text-sm text-base-content/60">
                    {{ $materialCount === 0 ? __('No materials required.') : __('Checklist auto-generated on finalize.') }}
                </p>
            </x-card>
        </div>

        {{-- Deal details --}}
        <x-card>
            <h2 class="text-lg font-extrabold">{{ __('Deal Details') }}</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <span class="eyebrow text-base-content/50">{{ __('Company') }}</span>
                    <div class="mt-1 text-sm font-semibold">{{ $deal->sponsor->company_name }}</div>
                </div>
                <div>
                    <span class="eyebrow text-base-content/50">{{ __('PIC') }}</span>
                    <div class="mt-1 text-sm font-semibold">{{ $deal->sponsor->pic_name }}</div>
                    <div class="text-xs text-base-content/50">{{ $deal->sponsor->pic_contact }}</div>
                </div>
                <div>
                    <span class="eyebrow text-base-content/50">{{ __('Doctor (Initiator)') }}</span>
                    <div class="mt-1 text-sm font-semibold">{{ $deal->doctor->name }}</div>
                </div>
                <div>
                    <span class="eyebrow text-base-content/50">{{ __('Created') }}</span>
                    <div class="mt-1 text-sm font-semibold">{{ $deal->created_at?->format('d M Y') }}</div>
                </div>
            </div>
        </x-card>

        {{-- Items --}}
        <x-card>
            <h2 class="text-lg font-extrabold">{{ __('Items') }}</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Item') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Custom Price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deal->items as $item)
                            <tr wire:key="item-{{ $item->id }}">
                                <td class="font-semibold">{{ $item->name }}</td>
                                <td>
                                    <span class="badge badge-soft {{ $item->pivot->is_addon ? 'badge-info' : 'badge-ghost' }}">
                                        {{ $item->pivot->is_addon ? __('Add-on') : __('Package item') }}
                                    </span>
                                </td>
                                <td>{{ $item->pivot->custom_price !== null ? 'Rp '.number_format((float) $item->pivot->custom_price, 0, ',', '.') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-base-content/50">{{ __('No items.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Assets --}}
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold">{{ __('Assets') }}</h2>
                    <p class="mt-1 text-sm text-base-content/60">{{ __('Files the sponsor will use (contracts, artwork, video). Max 50MB each.') }}</p>
                </div>
                @if ($deal->assets->isNotEmpty())
                    <x-button :label="__('Download all')" icon="o-arrow-down-tray" wire:click="downloadAll" spinner="downloadAll" class="btn-outline btn-sm" />
                @endif
            </div>

            @if (auth()->user()->isJ4u())
                <form wire:submit="uploadAssets" class="mt-4 space-y-3">
                    <div class="flex flex-wrap items-end gap-3">
                        <x-file wire:model="assets" multiple class="max-w-md grow" :hint="__('Optional. Give each file a name below.')" />
                        <x-button :label="__('Upload')" icon="o-arrow-up-tray" type="submit" class="btn-primary" spinner="uploadAssets" />
                    </div>

                    @if (! empty($assets))
                        <div class="space-y-2 rounded-box border border-base-300 p-3">
                            <p class="text-xs font-semibold text-base-content/60">{{ __('Name each file (optional)') }}</p>
                            @foreach ($assets as $i => $file)
                                <div class="flex items-center gap-3" wire:key="new-asset-{{ $i }}">
                                    <x-input wire:model="assetNames.{{ $i }}" :placeholder="$file->getClientOriginalName()" class="grow" />
                                    <span class="shrink-0 text-xs text-base-content/50">{{ $file->getClientOriginalName() }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @error('assets.*') <div class="mt-1 text-error">{{ $message }}</div> @enderror
                </form>
            @endif

            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('File') }}</th>
                            <th>{{ __('Size') }}</th>
                            <th>{{ __('Uploaded') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deal->assets as $asset)
                            <tr wire:key="asset-{{ $asset->id }}">
                                <td class="font-semibold">
                                    <button type="button" wire:click="downloadAsset({{ $asset->id }})" class="link link-primary text-start">{{ $asset->displayName() }}</button>
                                </td>
                                <td>{{ $asset->humanSize() }}</td>
                                <td class="text-sm text-base-content/60">
                                    {{ $asset->created_at?->format('d M Y') }}
                                    @if ($asset->uploadedBy)
                                        · {{ $asset->uploadedBy->name }}
                                    @endif
                                </td>
                                <td>
                                    <div class="flex gap-1">
                                        <x-button icon="o-arrow-down-tray" wire:click="downloadAsset({{ $asset->id }})" class="btn-ghost btn-sm btn-square" />
                                        @if (auth()->user()->isJ4u())
                                            <x-button
                                                icon="o-trash"
                                                wire:click="deleteAsset({{ $asset->id }})"
                                                wire:confirm="{{ __('Remove this asset?') }}"
                                                class="btn-ghost btn-sm btn-square text-error"
                                            />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-base-content/50">{{ __('No assets uploaded yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Payment terms --}}
        <x-card>
            <h2 class="text-lg font-extrabold">{{ __('Payment Terms') }}</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Due Date') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Transfer Proof') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deal->paymentTerms as $term)
                            <tr wire:key="term-{{ $term->id }}">
                                <td class="font-semibold">{{ $term->description }}</td>
                                <td>{{ $term->due_date->format('d M Y') }}</td>
                                <td>Rp {{ number_format((float) $term->amount, 0, ',', '.') }}</td>
                                <td>
                                    @if (auth()->user()->isJ4u() && $term->status === \App\Enums\PaymentStatus::Pending)
                                        <x-button
                                            :label="__('Mark Paid')"
                                            class="btn-soft btn-primary btn-xs"
                                            wire:click="markPaymentPaid({{ $term->id }})"
                                            wire:confirm="{{ __('Mark this term as paid?') }}"
                                        />
                                    @else
                                        <span class="badge badge-soft {{ $term->status === \App\Enums\PaymentStatus::Paid ? 'badge-success' : 'badge-ghost' }}">
                                            {{ $term->status->label() }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($term->hasProof())
                                        <div class="flex items-center gap-1">
                                            <button type="button" wire:click="downloadProof({{ $term->id }})" class="link link-primary inline-flex items-center gap-1 text-sm">
                                                <x-icon name="o-paper-clip" class="h-4 w-4" /> {{ $term->proofDownloadName() }}
                                            </button>
                                            <span class="text-xs text-base-content/40">{{ $term->proofHumanSize() }}</span>
                                            @if (auth()->user()->isJ4u())
                                                <x-button
                                                    icon="o-trash"
                                                    wire:click="deleteProof({{ $term->id }})"
                                                    wire:confirm="{{ __('Remove this transfer proof?') }}"
                                                    class="btn-ghost btn-xs btn-square text-error"
                                                />
                                            @endif
                                        </div>
                                    @elseif (auth()->user()->isJ4u())
                                        <form wire:submit="uploadProof({{ $term->id }})" class="flex flex-wrap items-end gap-2">
                                            <x-file wire:model="proofUploads.{{ $term->id }}" class="max-w-[11rem]" accept="image/*,application/pdf" />
                                            <x-button :label="__('Upload')" icon="o-arrow-up-tray" type="submit" class="btn-primary btn-xs" spinner="uploadProof({{ $term->id }})" />
                                            @error("proofUploads.{$term->id}") <div class="w-full text-xs text-error">{{ $message }}</div> @enderror
                                        </form>
                                    @else
                                        <span class="text-base-content/40">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-base-content/50">{{ __('No payment terms.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Material checklist --}}
        <x-card>
            <h2 class="text-lg font-extrabold">{{ __('Material Checklist') }}</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Material') }}</th>
                            <th>{{ __('Related Item') }}</th>
                            <th>{{ __('Due Date') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deal->materialDeadlines as $deadline)
                            <tr wire:key="mat-{{ $deadline->id }}">
                                <td class="font-semibold">{{ $deadline->material_name }}</td>
                                <td>{{ $deadline->item?->name ?? '—' }}</td>
                                <td>{{ $deadline->due_date?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    @if (auth()->user()->isJ4u() && $deadline->status === \App\Enums\MaterialStatus::Pending)
                                        <x-button
                                            :label="__('Mark Received')"
                                            class="btn-soft btn-primary btn-xs"
                                            wire:click="markMaterialReceived({{ $deadline->id }})"
                                            wire:confirm="{{ __('Mark this material as received?') }}"
                                        />
                                    @else
                                        <span class="badge badge-soft {{ $deadline->status === \App\Enums\MaterialStatus::Received ? 'badge-success' : 'badge-ghost' }}">
                                            {{ $deadline->status->label() }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-base-content/50">{{ __('No materials required for this deal.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Activity log --}}
        <x-card>
            <h2 class="text-lg font-extrabold">{{ __('Activity Log') }}</h2>

            <div class="mt-4 space-y-4">
                @forelse ($deal->activityLogs as $log)
                    <div class="flex gap-3">
                        <div class="mt-1.5 size-2 shrink-0 rounded-full bg-base-300"></div>
                        <div class="min-w-0">
                            <div class="text-sm">
                                <span class="font-semibold">{{ $log->user?->name ?? __('System') }}</span>
                                <span class="text-base-content/50">— {{ $log->action }}</span>
                            </div>
                            <div class="text-xs text-base-content/50">{{ $log->created_at->format('d M Y H:i') }}</div>

                            @if (is_array($log->details) && isset($log->details['status']) && is_array($log->details['status']))
                                <div class="mt-1 text-xs text-base-content/50">
                                    status: {{ $log->details['status']['old'] }} → {{ $log->details['status']['new'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-base-content/50">{{ __('No activity recorded yet.') }}</p>
                @endforelse
            </div>
        </x-card>
    </div>
</section>
