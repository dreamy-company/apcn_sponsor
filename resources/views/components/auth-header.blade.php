@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="text-xl font-extrabold tracking-tight">{{ $title }}</h1>
    <p class="mt-1 text-base-content/60">{{ $description }}</p>
</div>
