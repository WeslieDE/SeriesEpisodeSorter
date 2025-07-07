self.addEventListener('install', function(e) {
  e.waitUntil(
    caches.open('episode-sorter').then(function(cache) {
      return cache.addAll([
        '/index.php',
        '/bootstrap.min.css',
        '/bootstrap.bundle.min.js',
        '/Sortable.min.js'
      ]);
    })
  );
});

self.addEventListener('fetch', function(event) {
  event.respondWith(
    caches.match(event.request).then(function(response) {
      return response || fetch(event.request);
    })
  );
});
