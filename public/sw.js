const CACHE_NAME = 'lumina-salon-mobile-pwa-v2';
const STATIC_ASSETS = [
    '/manifest.webmanifest',
    '/assets/css/bootstrap.min.css',
    '/assets/css/plugins.min.css',
    '/assets/css/kaiadmin.min.css',
    '/assets/css/custom.css',
    '/assets/js/core/jquery-3.7.1.min.js',
    '/assets/js/core/popper.min.js',
    '/assets/js/core/bootstrap.min.js',
    '/assets/js/custom.js'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Cache-First untuk aset statis (/assets/), Network-Only untuk transaksi POS/Checkout/Auth
self.addEventListener('fetch', (event) => {
    const req = event.request;
    const url = new URL(req.url);

    if (
        req.method !== 'GET' ||
        url.pathname.startsWith('/pos/checkout') ||
        url.pathname.startsWith('/transactions') ||
        url.pathname.startsWith('/login') ||
        url.pathname.startsWith('/logout')
    ) {
        return;
    }

    if (url.pathname.startsWith('/assets/')) {
        event.respondWith(
            caches.match(req).then((cached) => cached || fetch(req))
        );
    }
});
