<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<meta name="theme-color" content="#061e3a">

{{-- Settle the theme before first paint to avoid a flash of the wrong theme.
     Light (warm cream) is the brand default; Mary's theme-toggle persists to
     localStorage under mary-theme / mary-class (JSON-quoted). --}}
<script>
    (function () {
        var theme = (localStorage.getItem('mary-theme') || '"light"').replaceAll('"', '');
        var cls = (localStorage.getItem('mary-class') || '"light"').replaceAll('"', '');
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.setAttribute('class', cls);
    })();
</script>

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
