@props(['package' => null, 'name' => null, 'size' => null])

@php $label = $name ?? $package?->name; @endphp

@if ($label)
    <span {{ $attributes->class(['badge gap-1 border-0 bg-gold font-bold text-neutral whitespace-nowrap', 'badge-lg' => $size === 'lg']) }}>
        <x-icon name="s-trophy" class="h-3.5 w-3.5" /> {{ $label }}
    </span>
@else
    <span {{ $attributes->class(['badge badge-ghost gap-1 whitespace-nowrap', 'badge-lg' => $size === 'lg']) }}>{{ __('Custom') }}</span>
@endif
