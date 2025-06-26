const CACHE_NAME = 'datarain-cache-v1';
const urlsToCache = [
  '/',
  '/style.css',
  '/manifest.json',
  '/favicon.ico',
  // اضافه کن فایل‌هایی که لازم داری
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(urlsToCache);
    })
  );
});

self.addEventListener('fetch', (e) => {
  e.respondWith(
    caches.match(e.request).then((response) => {
      return response || fetch(e.request);
    })
  );
});
