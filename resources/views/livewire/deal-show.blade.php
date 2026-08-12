<section class="w-full">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <flux:heading size="xl">{{ $deal->deal_number }}</flux:heading>
                <flux:badge
                    :color="$deal->status === \App\Enums\DealStatus::Finalized ? 'emerald' : 'zinc'"
                    inset="top bottom"
                >
                    {{ $deal->status->label() }}
                </flux:badge>
            </div>

            <div class="flex gap-3">
                <flux:button variant="ghost" href="{{ route('deals.index') }}" wire:navigate>
                    {{ __('Back') }}
                </flux:button>

                @if (auth()->user()->isJ4u() && $deal->status === \App\Enums\DealStatus::Draft)
                    <flux:button variant="ghost" href="{{ route('deals.edit', $deal) }}" wire:navigate icon="pencil">
                        {{ __('Edit') }}
                    </flux:button>
                    <flux:button variant="primary" wire:click="finalize" wire:confirm="{{ __('Finalize this deal? The material checklist will be generated.') }}">
                        {{ __('Finalize') }}
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid gap-4 md:grid-cols-3">
            <flux:card>
                <flux:heading size="sm">{{ __('Final Price') }}</flux:heading>
                <flux:heading size="2xl" class="mt-2">Rp {{ number_format((float) $deal->final_price, 0, ',', '.') }}</flux:heading>
                <flux:text class="mt-1">{{ $deal->package?->name ?? __('No base package') }}</flux:text>
            </flux:card>

            <flux:card>
                <flux:heading size="sm">{{ __('Payment Progress') }}</flux:heading>
                <flux:heading size="2xl" class="mt-2">
                    Rp {{ number_format((float) $totalPaid, 0, ',', '.') }}
                    <span class="text-base font-normal text-neutral-500">/ Rp {{ number_format((float) $totalTerms, 0, ',', '.') }}</span>
                </flux:heading>
                <flux:text class="mt-1">{{ $deal->paymentTerms->where('status', \App\Enums\PaymentStatus::Paid)->count() }} / {{ $deal->paymentTerms->count() }} {{ __('terms paid') }}</flux:text>
            </flux:card>

            <flux:card>
                <flux:heading size="sm">{{ __('Material Checklist') }}</flux:heading>
                <flux:heading size="2xl" class="mt-2">
                    {{ $materialReceived }}
                    <span class="text-base font-normal text-neutral-500">/ {{ $materialCount }} {{ __('received') }}</span>
                </flux:heading>
                <flux:text class="mt-1">
                    {{ $materialCount === 0 ? __('No materials required.') : __('Checklist auto-generated on finalize.') }}
                </flux:text>
            </flux:card>
        </div>

        {{-- Deal details --}}
        <flux:card>
            <flux:heading size="lg">{{ __('Deal Details') }}</flux:heading>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <flux:text class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Company') }}</flux:text>
                    <div class="mt-1 text-sm font-medium">{{ $deal->sponsor->company_name }}</div>
                </div>
                <div>
                    <flux:text class="text-xs uppercase tracking-wide text-neutral-500">{{ __('PIC') }}</flux:text>
                    <div class="mt-1 text-sm font-medium">{{ $deal->sponsor->pic_name }}</div>
                    <div class="text-xs text-neutral-500">{{ $deal->sponsor->pic_contact }}</div>
                </div>
                <div>
                    <flux:text class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Doctor (Initiator)') }}</flux:text>
                    <div class="mt-1 text-sm font-medium">{{ $deal->doctor->name }}</div>
                </div>
                <div>
                    <flux:text class="text-xs uppercase tracking-wide text-neutral-500">{{ __('Created') }}</flux:text>
                    <div class="mt-1 text-sm font-medium">{{ $deal->created_at?->format('d M Y') }}</div>
                </div>
            </div>
        </flux:card>

        {{-- Items --}}
        <flux:card>
            <flux:heading size="lg">{{ __('Items') }}</flux:heading>

            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>{{ __('Item') }}</flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column>{{ __('Custom Price') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($deal->items as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell class="font-medium">{{ $item->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$item->pivot->is_addon ? 'indigo' : 'zinc'" inset="top bottom">
                                    {{ $item->pivot->is_addon ? __('Add-on') : __('Package item') }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $item->pivot->custom_price !== null ? 'Rp '.number_format((float) $item->pivot->custom_price, 0, ',', '.') : '—' }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="text-center text-neutral-500">{{ __('No items.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        {{-- Payment terms --}}
        <flux:card>
            <flux:heading size="lg">{{ __('Payment Terms') }}</flux:heading>

            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>{{ __('Description') }}</flux:table.column>
                    <flux:table.column>{{ __('Due Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Amount') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($deal->paymentTerms as $term)
                        <flux:table.row :key="$term->id">
                            <flux:table.cell class="font-medium">{{ $term->description }}</flux:table.cell>
                            <flux:table.cell>{{ $term->due_date->format('d M Y') }}</flux:table.cell>
                            <flux:table.cell>Rp {{ number_format((float) $term->amount, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>
                                @if (auth()->user()->isJ4u() && $term->status === \App\Enums\PaymentStatus::Pending)
                                    <flux:button
                                        size="xs"
                                        variant="subtle"
                                        wire:click="markPaymentPaid({{ $term->id }})"
                                        wire:confirm="{{ __('Mark this term as paid?') }}"
                                    >
                                        {{ __('Mark Paid') }}
                                    </flux:button>
                                @else
                                    <flux:badge :color="$term->status === \App\Enums\PaymentStatus::Paid ? 'emerald' : 'zinc'" inset="top bottom">
                                        {{ $term->status->label() }}
                                    </flux:badge>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-neutral-500">{{ __('No payment terms.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        {{-- Material checklist --}}
        <flux:card>
            <flux:heading size="lg">{{ __('Material Checklist') }}</flux:heading>

            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>{{ __('Material') }}</flux:table.column>
                    <flux:table.column>{{ __('Related Item') }}</flux:table.column>
                    <flux:table.column>{{ __('Due Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($deal->materialDeadlines as $deadline)
                        <flux:table.row :key="$deadline->id">
                            <flux:table.cell class="font-medium">{{ $deadline->material_name }}</flux:table.cell>
                            <flux:table.cell>{{ $deadline->item?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $deadline->due_date?->format('d M Y') ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                @if (auth()->user()->isJ4u() && $deadline->status === \App\Enums\MaterialStatus::Pending)
                                    <flux:button
                                        size="xs"
                                        variant="subtle"
                                        wire:click="markMaterialReceived({{ $deadline->id }})"
                                        wire:confirm="{{ __('Mark this material as received?') }}"
                                    >
                                        {{ __('Mark Received') }}
                                    </flux:button>
                                @else
                                    <flux:badge :color="$deadline->status === \App\Enums\MaterialStatus::Received ? 'emerald' : 'zinc'" inset="top bottom">
                                        {{ $deadline->status->label() }}
                                    </flux:badge>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-neutral-500">{{ __('No materials required for this deal.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        {{-- Activity log --}}
        <flux:card>
            <flux:heading size="lg">{{ __('Activity Log') }}</flux:heading>

            <div class="mt-4 space-y-4">
                @forelse ($deal->activityLogs as $log)
                    <div class="flex gap-3">
                        <div class="mt-1.5 size-2 shrink-0 rounded-full bg-neutral-300 dark:bg-neutral-600"></div>
                        <div class="min-w-0">
                            <div class="text-sm">
                                <span class="font-medium">{{ $log->user?->name ?? __('System') }}</span>
                                <span class="text-neutral-500">— {{ $log->action }}</span>
                            </div>
                            <div class="text-xs text-neutral-500">{{ $log->created_at->format('d M Y H:i') }}</div>

                            @if (is_array($log->details) && isset($log->details['status']) && is_array($log->details['status']))
                                <div class="mt-1 text-xs text-neutral-500">
                                    status: {{ $log->details['status']['old'] }} → {{ $log->details['status']['new'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <flux:text class="text-neutral-500">{{ __('No activity recorded yet.') }}</flux:text>
                @endforelse
            </div>
        </flux:card>
    </div>
</section>
