const CACHE_NAME = 'cashbook-static-v2';

const APP_SHELL = [
    '/cashbook/assets/css/custom.css',
    '/cashbook/assets/style.css',
    '/cashbook/assets/js/custom.js',
    '/cashbook/assets/script.js',
    '/cashbook/icons/icon-192.png',
    '/cashbook/icons/icon-512.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(async cache => {
            console.log('Cashbook: starting app shell cache...');
            for (const url of APP_SHELL) {
                try {
                    const response = await fetch(url);
                    if (!response.ok) {
                        console.error(
                            'Cashbook CACHE FAILED:',
                            url,
                            'HTTP:',
                            response.status
                        );
                        continue;
                    }

                    await cache.put(url, response.clone());

                    console.log(
                        'Cashbook CACHED:',
                        url
                    );

                } catch (error) {

                    console.error(
                        'Cashbook CACHE ERROR:',
                        url,
                        error
                    );
                }
            }
            console.log('Cashbook: app shell caching finished.');
        })
    );

    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys
                    .filter(key => key !== CACHE_NAME)
                    .map(key => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});