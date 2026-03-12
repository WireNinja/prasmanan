<!-- Prasmanan PWA Service Worker Registration -->
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
