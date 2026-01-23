@php
$viteAssetsAvailable = file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'));
@endphp

@if($viteAssetsAvailable)
    {{-- Use Vite in development or production with built assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    {{-- Fallback: Use Tailwind CDN + External CSS/JS Files (No npm required) --}}
    
    {{-- Preconnect to CDN for faster loading --}}
    <link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.tailwindcss.com">
    
    {{-- Load Critical CSS from external file with preload --}}
    <link rel="preload" href="{{ asset('css/critical.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('css/critical.css') }}"></noscript>
    
    {{-- Load Tailwind CDN asynchronously --}}
    <script>
        (function() {
            var script = document.createElement('script');
            script.src = 'https://cdn.tailwindcss.com';
            script.defer = true;
            script.async = true;
            script.onload = function() {
                // Load Tailwind config after CDN loads
                var configScript = document.createElement('script');
                configScript.src = '{{ asset('js/tailwind-config.js') }}';
                configScript.defer = true;
                document.head.appendChild(configScript);
            };
            document.head.appendChild(script);
        })();
    </script>
    
    {{-- Load Fallback JavaScript from external files with defer --}}
    <script src="{{ asset('js/fallback.js') }}" defer async></script>
    {{-- Documents interactions (downloads/password modal) --}}
    <script src="{{ asset('js/documents.js') }}" defer async></script>
@endif

