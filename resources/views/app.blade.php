<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>CCS Capstone Repository</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        {{-- Apply the saved theme and sidebar state BEFORE the page draws, so nothing flashes. --}}
        <script>
            (function () {
                var d = document.documentElement, t = null, c = null;
                try { t = localStorage.getItem('ccs.theme'); c = localStorage.getItem('ccs.sidebar'); } catch (e) {}
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) d.classList.add('dark');
                if (c === 'collapsed') d.dataset.sidebar = 'collapsed';
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/css/polish.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
