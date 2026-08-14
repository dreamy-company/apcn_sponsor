<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Appearance settings') }}</h2>

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        {{-- Segmented light/dark control, synced with Mary's theme toggle
             (localStorage keys mary-theme / mary-class). --}}
        <div
            x-data="{
                theme: $persist('light').as('mary-theme'),
                set(t) {
                    this.theme = t;
                    document.documentElement.setAttribute('data-theme', t);
                    document.documentElement.setAttribute('class', t);
                    localStorage.setItem('mary-class', JSON.stringify(t));
                    this.$dispatch('theme-changed', t);
                },
            }"
            class="flex gap-2"
        >
            <button type="button" class="btn" :class="theme === 'light' ? 'btn-primary' : 'btn-outline'" @click="set('light')">
                <x-icon name="o-sun" class="h-4 w-4" /> {{ __('Light') }}
            </button>
            <button type="button" class="btn" :class="theme === 'dark' ? 'btn-primary' : 'btn-outline'" @click="set('dark')">
                <x-icon name="o-moon" class="h-4 w-4" /> {{ __('Dark') }}
            </button>
        </div>
    </x-settings.layout>
</section>
