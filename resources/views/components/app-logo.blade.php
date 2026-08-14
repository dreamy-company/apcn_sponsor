@props(['class' => 'h-12 w-auto'])

{{-- Full white lockup (incl. wordmark) — never pair with a repeated heading. --}}
<img
    src="{{ asset('img/logowhiteapcn.png') }}"
    alt="{{ config('app.name', 'APCN 2027') }}"
    {{ $attributes->class($class) }}
/>
