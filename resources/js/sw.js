import { clientsClaim } from "workbox-core";
import { ExpirationPlugin } from "workbox-expiration";
import { cleanupOutdatedCaches, precacheAndRoute } from "workbox-precaching";
import { registerRoute } from "workbox-routing";
import {
    CacheFirst,
    NetworkOnly,
    StaleWhileRevalidate,
} from "workbox-strategies";

// 1. STANDARD SETUP
console.log("🔧 [sw]Cleaning outdated caches...");
cleanupOutdatedCaches();

console.log("🔧 [sw]Skipping waiting...");
self.skipWaiting();

console.log("🔧 [sw]Claiming clients...");
clientsClaim();

// 2. PRECACHE INJECTION
console.log("🔧 [sw]Pre-caching...");
precacheAndRoute(self.__WB_MANIFEST);

// 3. RUNTIME CACHING STRATEGIES

// A. Cache PWA Icons (StaleWhileRevalidate)
console.log("🔧 [sw]Registering routes for PWA Icons...");
registerRoute(
    ({ url }) => url.pathname.startsWith("/pwa/icons/"),
    new StaleWhileRevalidate({
        cacheName: "pwa-icons",
        plugins: [
            new ExpirationPlugin({
                maxEntries: 50,
                maxAgeSeconds: 30 * 24 * 60 * 60, // 30 days
            }),
        ],
    }),
);

// B. Cache General Images (CacheFirst)
console.log("🔧 [sw]Registering routes for General Images...");
registerRoute(
    ({ request }) => request.destination === "image",
    new CacheFirst({
        cacheName: "static-images",
        plugins: [
            new ExpirationPlugin({
                maxEntries: 100,
                maxAgeSeconds: 30 * 24 * 60 * 60, // 30 days
            }),
        ],
    }),
);

// C. Laravel App Data Strategy (Network Only) - REMOVED to prevent double fetching
// The browser will handle these requests directly, which works better with Livewire wire:navigate
// and strictly online apps.

// 4. PUSH NOTIFICATION HANDLER
console.log("🔧 [sw]Registering push notification handler...");
self.addEventListener("push", (event) => {
    console.log("🔧 [sw]Push notification received:", event);
    if (!event.data) return;

    try {
        const data = event.data.json();
        // @TODO: remove this on prod
        console.log(data);

        const title = data?.title || "Notifikasi Baru";

        const options = {
            body: data?.body || "Notifikasi Baru",
            icon: data?.icon || "/pwa/icons/pwa-192x192.png",
            badge: data?.badge || "/pwa/icons/pwa-64x64.png",
            vibrate: data?.vibrate || [100, 50, 100],
            data: {
                url: data?.data?.url || "/",
            },
            actions: data?.actions || [],
        };

        event.waitUntil(self.registration.showNotification(title, options));
    } catch (e) {
        console.error("Push notification error:", e);
    }
});

// 5. NOTIFICATION CLICK HANDLER
console.log("🔧 [sw]Registering notification click handler...");
self.addEventListener("notificationclick", (event) => {
    event.notification.close();

    event.waitUntil(
        self.clients
            .matchAll({ type: "window", includeUncontrolled: true })
            .then((clientList) => {
                const urlToOpen = event.notification.data.url;

                // Coba fokus ke window yang sudah ada
                for (const client of clientList) {
                    if (client.url === urlToOpen && "focus" in client) {
                        return client.focus();
                    }
                }

                // Kalau tidak ada, buka window baru
                if (self.clients.openWindow) {
                    return self.clients.openWindow(urlToOpen);
                }
            }),
    );
});
