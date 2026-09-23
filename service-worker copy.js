const CACHE_NAME = 'educitech-cashbook-v1';

const APP_SHELL = [
    '/cashbook/',
    '/cashbook/login/',
    '/cashbook/assets/css/custom.css',
    '/cashbook/assets/style.css',
    '/cashbook/assets/js/custom.js',
    '/cashbook/assets/script.js',
    '/cashbook/icons/icon-192.png',
    '/cashbook/icons/icon-512.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(APP_SHELL))
    );

    self.skipWaiting();
});


self.addEventListener('activate', event => {

    event.waitUntil(

        caches.keys().then(cacheNames => {

            return Promise.all(

                cacheNames
                    .filter(name => name !== CACHE_NAME)
                    .map(name => caches.delete(name))

            );

        })

    );

    self.clients.claim();
});


self.addEventListener('fetch', event => {

    event.respondWith(

        fetch(event.request)
            .then(response => {

                return response;

            })
            .catch(() => {

                return caches.match(event.request);

            })

    );

});