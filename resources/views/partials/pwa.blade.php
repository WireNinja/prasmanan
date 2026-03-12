<!-- PWA Manifest -->
<link rel="manifest" href="{{ route('pwa.manifest') }}">

<!-- PWA Theme Color -->
<meta name="theme-color" content="#4B5563">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

<!-- PWA Icons -->
<link rel="apple-touch-icon" href="{{ asset('pwa/icons/apple-touch-icon-180x180.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('pwa/icons/favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('pwa/icons/favicon-16x16.png') }}">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

<!-- PWA Service Worker Registration -->
<script x-data>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js', {
                scope: '/',
                type: 'module'
            }).then(function(registration) {
                console.log('✅ Prasmanan PWA: ServiceWorker registered successfully with scope:', registration.scope);

                registration.onupdatefound = () => {
                    const installingWorker = registration.installing;
                    if (installingWorker) {
                        installingWorker.onstatechange = () => {
                            if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                console.log('🔄 Prasmanan PWA: New content available. It will be used on next visit.');
                            }
                        };
                    }
                };
            }, function(err) {
                console.error('❌ Prasmanan PWA: ServiceWorker registration failed:', err);
            });
        });
    }
</script>
