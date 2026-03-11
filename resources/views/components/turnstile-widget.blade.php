@php
    $containerId = 'turnstile-' . uniqid();
@endphp

{{-- Cloudflare Turnstile CAPTCHA Widget --}}
<div x-data="{
    widgetId: null,
    rendered: false,
    containerId: '{{ $containerId }}',
    init() {
        this.$nextTick(() => {
            if (typeof turnstile !== 'undefined' && !this.rendered) {
                this.renderWidget();
            } else {
                // Fallback: wait for Turnstile to load
                const checkTurnstile = setInterval(() => {
                    if (typeof turnstile !== 'undefined' && !this.rendered) {
                        clearInterval(checkTurnstile);
                        this.renderWidget();
                    }
                }, 100);
            }
        });
    },
    renderWidget() {
        if (this.rendered) return;

        this.widgetId = turnstile.render('#' + this.containerId, {
            sitekey: '{{ $sitekey }}',
            theme: 'auto',
            size: 'normal',
            callback: (token) => {
                $wire.set('data.cf_turnstile_response', token);
            },
            'error-callback': (error) => {
                console.error('Turnstile error:', error);
                $wire.set('data.cf_turnstile_response', null);
            },
            'expired-callback': () => {
                console.warn('Turnstile token expired');
                $wire.set('data.cf_turnstile_response', null);
            }
        });

        this.rendered = true;
    },
    reset() {
        if (this.widgetId !== null && typeof turnstile !== 'undefined') {
            turnstile.reset(this.widgetId);
            $wire.set('data.cf_turnstile_response', null);
        }
    }
}" x-init="init()" class="my-4">
    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
        Untuk melanjutkan, silakan selesaikan verifikasi keamanan di bawah ini:
    </div>

    {{-- Turnstile widget will be rendered here --}}
    <div :id="containerId" class="flex justify-center"></div>
</div>
