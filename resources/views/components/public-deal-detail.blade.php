@props(['deal'])

@php
    $totalTerms = (float) $deal->paymentTerms->sum('amount');
    $totalPaid = (float) $deal->paymentTerms->where('status', \App\Enums\PaymentStatus::Paid)->sum('amount');
    $materialCount = $deal->materialDeadlines->count();
    $materialReceived = $deal->materialDeadlines->where('status', \App\Enums\MaterialStatus::Received)->count();
@endphp

<div class="space-y-6">
    {{-- Stat row --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-box bg-base-200 p-4">
            <div class="eyebrow text-base-content/50">{{ __('Final Price') }}</div>
            <div class="mt-1 text-xl font-extrabold">Rp {{ number_format((float) $deal->final_price, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-box bg-base-200 p-4">
            <div class="eyebrow text-base-content/50">{{ __('Payments') }}</div>
            <div class="mt-1 text-xl font-extrabold">
                Rp {{ number_format($totalPaid, 0, ',', '.') }}
                <span class="text-sm font-normal text-base-content/50">/ Rp {{ number_format($totalTerms, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="rounded-box bg-base-200 p-4">
            <div class="eyebrow text-base-content/50">{{ __('Materials') }}</div>
            <div class="mt-1 text-xl font-extrabold">{{ $materialReceived }} <span class="text-sm font-normal text-base-content/50">/ {{ $materialCount }}</span></div>
        </div>
    </div>

    {{-- Details --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
            <span class="eyebrow text-base-content/50">{{ __('Package') }}</span>
            <div class="mt-1 text-sm font-semibold">{{ $deal->package?->name ?? __('Custom') }}</div>
        </div>
    </div>

    {{-- Items --}}
    <div>
        <h3 class="mb-2 text-sm font-extrabold">{{ __('Items') }}</h3>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>{{ __('Item') }}</th><th>{{ __('Type') }}</th></tr></thead>
                <tbody>
                    @forelse ($deal->items as $item)
                        <tr>
                            <td class="font-semibold">{{ $item->name }}</td>
                            <td><span class="badge badge-soft {{ $item->pivot->is_addon ? 'badge-info' : 'badge-ghost' }}">{{ $item->pivot->is_addon ? __('Add-on') : __('Package item') }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-base-content/50">{{ __('No items.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Payment terms --}}
    <div>
        <h3 class="mb-2 text-sm font-extrabold">{{ __('Payment Terms') }}</h3>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>{{ __('Description') }}</th><th>{{ __('Due') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse ($deal->paymentTerms as $term)
                        <tr>
                            <td class="font-semibold">{{ $term->description }}</td>
                            <td>{{ $term->due_date->format('d M Y') }}</td>
                            <td>Rp {{ number_format((float) $term->amount, 0, ',', '.') }}</td>
                            <td><span class="badge badge-soft {{ $term->status === \App\Enums\PaymentStatus::Paid ? 'badge-success' : 'badge-ghost' }}">{{ $term->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-base-content/50">{{ __('No payment terms.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Materials --}}
    @if ($materialCount > 0)
        <div>
            <h3 class="mb-2 text-sm font-extrabold">{{ __('Material Checklist') }}</h3>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>{{ __('Material') }}</th><th>{{ __('Due') }}</th><th>{{ __('Status') }}</th></tr></thead>
                    <tbody>
                        @foreach ($deal->materialDeadlines as $deadline)
                            <tr>
                                <td class="font-semibold">{{ $deadline->material_name }}</td>
                                <td>{{ $deadline->due_date?->format('d M Y') ?? '—' }}</td>
                                <td><span class="badge badge-soft {{ $deadline->status === \App\Enums\MaterialStatus::Received ? 'badge-success' : 'badge-ghost' }}">{{ $deadline->status->label() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
