const CACHE_VERSION = 'support-portal-static-v2';
const BASE_PATH = '/support';
const OFFLINE_URL = `${BASE_PATH}/offline.html`;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then((cache) => cache.add(new Request(OFFLINE_URL, { cache: 'reload' })))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith('support-portal-') && key !== CACHE_VERSION)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

function isCacheableStaticAsset(url) {
    return url.origin === self.location.origin
        && (
            url.pathname.startsWith(`${BASE_PATH}/assets/`)
            || url.pathname.startsWith(`${BASE_PATH}/build/`)
        );
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        // Authenticated HTML and request state must always come from Laravel.
        event.respondWith(
            fetch(request, { cache: 'no-store' })
                .catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    if (!isCacheableStaticAsset(url)) {
        // Never cache status, connector-token, download, or other application routes.
        return;
    }

    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }

            return fetch(request).then((networkResponse) => {
                if (!networkResponse.ok || networkResponse.type !== 'basic') {
                    return networkResponse;
                }

                const responseToCache = networkResponse.clone();
                caches.open(CACHE_VERSION).then((cache) => cache.put(request, responseToCache));

                return networkResponse;
            });
        })
    );
});
