// Service Worker for PWA (Minimal - No Offline Mode)
// This enables the app to be "installable" as a PWA

const CACHE_NAME = 'attendance-v2-grade-builder';
const STATIC_ASSETS = [
    '/',
    '/manifest.json',
];

// Install event - just cache minimal static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('Service Worker: Caching static assets');
            return cache.addAll(STATIC_ASSETS);
        })
    );
    // Activate immediately
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => {
                        console.log('Service Worker: Deleting old cache', name);
                        return caches.delete(name);
                    })
            );
        })
    );
    // Take control immediately
    self.clients.claim();
});

// Fetch event - Network first strategy (no offline mode)
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip API requests - always go to network
    if (event.request.url.includes('/api/')) {
        return;
    }

    // For navigation requests, always go to network first
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => {
                // If network fails, show a simple offline message
                return new Response(
                    `<!DOCTYPE html>
          <html>
          <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Offline - Sistem Absensi</title>
            <style>
              body {
                font-family: system-ui, -apple-system, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background: #f8fafc;
                color: #334155;
              }
              .container {
                text-align: center;
                padding: 2rem;
              }
              h1 { color: #0f172a; margin-bottom: 1rem; }
              p { color: #64748b; margin-bottom: 2rem; }
              button {
                background: #0f172a;
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 16px;
              }
              button:hover { background: #1e293b; }
            </style>
          </head>
          <body>
            <div class="container">
              <h1>📡 Tidak Ada Koneksi</h1>
              <p>Anda sedang offline. Pastikan koneksi internet Anda aktif.</p>
              <button onclick="location.reload()">Coba Lagi</button>
            </div>
          </body>
          </html>`,
                    {
                        headers: { 'Content-Type': 'text/html' },
                    }
                );
            })
        );
        return;
    }

    // For other requests, try network first
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Cache successful responses for static assets
                if (response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                // If network fails, try cache
                return caches.match(event.request);
            })
    );
});

// Listen for messages from the main thread
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
