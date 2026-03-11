@if(config('prasmanan.pwa.enabled', true))
<div 
    x-data="{
        isSubscribed: false,
        checking: true,
        denied: Notification.permission === 'denied',
        vapidPublicKey: '{{ config('webpush.vapid.public_key') }}',
        
        init() {
            // Jika browser tidak support, biarkan mereka lewat
            console.log('Init Web Push');

            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                console.log('Browser tidak support Web Push');

                this.isSubscribed = true; 
                this.checking = false;

                return;
            }
            
            navigator.serviceWorker.ready.then(registration => {
                console.log('Service Worker ready');

                registration.pushManager.getSubscription().then(subscription => {
                    console.log('Subscription:', subscription);

                    if (subscription) {
                        this.saveSubscription(subscription);
                        this.isSubscribed = true;
                    }
                    this.checking = false;
                });
            });
            
            navigator.serviceWorker.getRegistration().then(registration => {
                if (!registration) {
                    alert('Perangkat anda belum terdaftar. Anda akan diarahkan ke halaman awal terlebih dahulu, dan silahkan kunjungi halaman login kembali untuk melanjutkan.');
                    window.location.href = '/';
                }
            });
        },
        
        async subscribe() {
            try {
                const permission = await Notification.requestPermission();
                
                if (permission === 'denied') {
                    this.denied = true;
                    return;
                }
                
                const registration = await navigator.serviceWorker.ready;
                const convertedVapidKey = this.urlBase64ToUint8Array(this.vapidPublicKey);
                
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedVapidKey
                });
                
                this.saveSubscription(subscription);
                
                // Buka form login setelah sukses
                this.isSubscribed = true;
                
            } catch (e) {
                console.error('Subscription failed:', e);
                alert('Gagal mengaktifkan notifikasi. Silakan coba lagi.');
            }
        },
        
        saveSubscription(subscription) {
            const key = subscription.getKey('p256dh');
            const token = subscription.getKey('auth');
            const contentEncoding = (PushManager.supportedContentEncodings || ['aes128gcm'])[0];
            
            const arrayBufferToBase64 = (buffer) => btoa(String.fromCharCode(...new Uint8Array(buffer)));
            
            $wire.set('push_endpoint', subscription.endpoint, false);
            $wire.set('push_key', key ? arrayBufferToBase64(key) : null, false);
            $wire.set('push_token', token ? arrayBufferToBase64(token) : null, false);
            $wire.set('push_encoding', contentEncoding, false);
        },
        
        urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }
    }"
    x-show="!isSubscribed"
    style="display: none;"
    class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white/95 backdrop-blur-sm p-6 text-center"
>
    <div x-show="checking">
        <svg class="animate-spin h-10 w-10 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-500 font-medium">Memeriksa kelayakan perangkat...</p>
    </div>
    
    <div x-show="!checking && !denied" x-cloak class="max-w-md">
        <div class="bg-primary-100 text-primary-600 p-4 rounded-full inline-block mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        </div>
        <h2 class="text-2xl font-bold mb-2 text-gray-800">Verifikasi Perangkat</h2>
        <p class="text-gray-600 mb-6">Karena ini adalah sistem internal, Anda diwajibkan untuk mengaktifkan Notifikasi Sistem pada browser ini agar dapat login dan menerima update penting.</p>
        <button type="button" @click="subscribe" class="w-full bg-primary-600 text-white px-6 py-3 rounded-lg font-bold shadow-lg hover:bg-primary-500 transition-all">
            Aktifkan Notifikasi & Lanjutkan Login
        </button>
    </div>
    
    <div x-show="denied" x-cloak class="max-w-md">
        <div class="bg-danger-100 text-danger-600 p-4 rounded-full inline-block mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <h2 class="text-xl font-bold mb-2 text-danger-600">Akses Diblokir</h2>
        <p class="text-gray-600 mb-4">Sistem mendeteksi Anda telah memblokir izin notifikasi untuk situs ini. Anda tidak dapat login sebelum mengizinkannya.</p>
        <p class="text-sm text-gray-500 bg-gray-100 p-4 rounded-lg text-left">
            <strong>Cara Mengatasi:</strong><br>
            1. Klik ikon "Gembok / Site Information" di sebelah kiri kolom URL browser Anda.<br>
            2. Ubah izin "Notifications / Notifikasi" menjadi <strong>Allow / Izinkan</strong>.<br>
            3. Muat ulang (Refresh) halaman ini.
        </p>
    </div>
</div>
@endif