const CACHE_NAME = 'radio-poderosa-v18';

const CORE_ASSETS = [
  '/',
  '/sorteos',
  '/manifest.json',
  '/css/radio-landing.css?v=20261116-4',
  '/css/sorteos.css?v=20261116-12',
  '/js/sorteos.js?v=20261116-2',
  '/js/sorteos-carousel.js?v=20261116-2',
  '/css/custom.260803025619.css',
  '/webcard/static/app.min.1785311942.css',
  '/js/custom.260801165020.js',
  '/webcard/static/app.bundle.1785311956.js',
  '/images/500/27243176/Logoweb500x200px-euEMi_PZ2kiMzp730T4ZNA.png',
  '/icons/icon-192.png',
  '/icons/icon-512.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(CORE_ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  // El panel y sus assets no se cachean: deben reflejar siempre el último
  // despliegue y no quedar atrapados en la caché del sitio público.
  if (
    url.pathname.startsWith('/admin') ||
    url.pathname.startsWith('/assets/admin/') ||
    url.pathname === '/assets/admin' ||
    url.pathname === '/radio-stats.php' ||
    url.pathname === '/cover.php'
  ) {
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match('/'))
    );
    return;
  }

  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) {
        return cached;
      }
      return fetch(request).then((response) => {
        if (response && response.ok) {
          const copy = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
        }
        return response;
      }).catch(() => caches.match('/'));
    })
  );
});
