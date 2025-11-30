const CACHE_NAME = 'attendance-app-v1.1.3';
const STATIC_CACHE_NAME = 'attendance-static-v1.1.3';
const DYNAMIC_CACHE_NAME = 'attendance-dynamic-v1.1.3';
const IMAGE_CACHE_NAME = 'attendance-images-v1.1.3';

// Critical static assets for immediate caching
const STATIC_ASSETS = [
  '/',
  '/manifest.json',
  '/offline.html'
  // Note: Vite assets are dynamically generated and will be cached on-demand
];

// Routes for dynamic caching
const DYNAMIC_ROUTES = [
  '/dashboard',
  '/attendance/mobile',
  '/leave/mobile',
  '/employees',
  '/schedules'
];

// CDN resources with longer cache
const CDN_RESOURCES = [
  'https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css',
  'https://cdn.jsdelivr.net/npm/@tabler/icons@latest/iconfont/tabler-icons.min.css',
  'https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js',
  'https://code.jquery.com/jquery-3.6.0.min.js',
  'https://cdn.jsdelivr.net/npm/vue@3',
  'https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css'
];

// API patterns for smart caching
const API_CACHE_PATTERNS = [
  '/api/dashboard/stats',
  '/api/attendance/recent',
  '/api/employees/summary',
  '/api/schedule-management/today'
];

// Install event - cache critical resources
self.addEventListener('install', event => {
  console.log('Service Worker: Installing v1.1.3 with enhanced error handling');
  
  event.waitUntil(
    Promise.all([
      // Cache static assets
      caches.open(STATIC_CACHE_NAME).then(cache => {
        console.log('Service Worker: Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      }),
      // Cache CDN resources
      caches.open(CACHE_NAME).then(cache => {
        console.log('Service Worker: Caching CDN resources');
        return cache.addAll(CDN_RESOURCES);
      })
    ])
    .then(() => {
      console.log('Service Worker: Installation complete');
      return self.skipWaiting();
    })
    .catch(error => {
      console.error('Service Worker: Installation failed:', error);
    })
  );
});

// Activate event - clean up old caches and claim clients
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating v1.1.1');
  
  event.waitUntil(
    Promise.all([
      // Clean up old caches
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== CACHE_NAME && 
                cacheName !== STATIC_CACHE_NAME && 
                cacheName !== DYNAMIC_CACHE_NAME &&
                cacheName !== IMAGE_CACHE_NAME) {
              console.log('Service Worker: Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      }),
      // Claim all clients
      self.clients.claim()
    ])
    .then(() => {
      console.log('Service Worker: Activation complete');
    })
  );
});

// Fetch event - intelligent caching strategies
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }
  
  // Skip chrome-extension and data URLs
  if (url.protocol === 'chrome-extension:' || url.protocol === 'data:') {
    return;
  }
  
  // Different strategies based on request type
  if (isStaticAsset(request.url)) {
    event.respondWith(handleStaticAssets(request));
  } else if (isImage(request.url)) {
    event.respondWith(handleImages(request));
  } else if (isAPIRequest(request.url)) {
    event.respondWith(handleAPIRequests(request));
  } else if (isCDNResource(request.url)) {
    event.respondWith(handleCDNResources(request));
  } else if (isNavigation(request)) {
    event.respondWith(handleNavigation(request));
  } else {
    event.respondWith(handleDefault(request));
  }
});

// Cache strategies
async function handleStaticAssets(request) {
  try {
    const cache = await caches.open(STATIC_CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Check if we're online before attempting network request
    if (!navigator.onLine) {
      return getOfflineResponse(request);
    }
    
    // Create fetch with AbortController for timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 5000);
    
    const networkResponse = await fetch(request, {
      signal: controller.signal
    });
    
    clearTimeout(timeoutId);
    
    if (networkResponse.ok && networkResponse.status === 200) {
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.warn('Static asset fetch failed, serving offline response:', error.message);
    return getOfflineResponse(request);
  }
}

async function handleImages(request) {
  try {
    const cache = await caches.open(IMAGE_CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    const networkResponse = await fetch(request);
    if (networkResponse.status === 200) {
      // Cache images for longer
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.error('Image handling failed:', error);
    return getOfflineImageResponse();
  }
}

async function handleAPIRequests(request) {
  // Skip auth-related API requests
  if (request.url.includes('/logout') || 
      request.url.includes('/login') ||
      request.url.includes('/csrf')) {
    return fetch(request);
  }
  
  // Cache-first for specific API patterns
  if (API_CACHE_PATTERNS.some(pattern => request.url.includes(pattern))) {
    return handleCacheableAPI(request);
  }
  
  // Network-first for other API requests
  return fetch(request);
}

async function handleCacheableAPI(request) {
  try {
    const cache = await caches.open(DYNAMIC_CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    // Try network first, fallback to cache
    try {
      const networkResponse = await fetch(request);
      if (networkResponse.status === 200) {
        cache.put(request, networkResponse.clone());
      }
      return networkResponse;
    } catch (networkError) {
      console.log('Network failed, using cache:', request.url);
      return cachedResponse || getOfflineResponse(request);
    }
  } catch (error) {
    console.error('API caching failed:', error);
    return fetch(request);
  }
}

async function handleCDNResources(request) {
  try {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    const networkResponse = await fetch(request);
    if (networkResponse.status === 200) {
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.error('CDN resource handling failed:', error);
    return getOfflineResponse(request);
  }
}

async function handleNavigation(request) {
  try {
    const cache = await caches.open(DYNAMIC_CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    // Stale-while-revalidate strategy
    const networkResponsePromise = fetch(request)
      .then(response => {
        if (response.status === 200) {
          cache.put(request, response.clone());
        }
        return response;
      })
      .catch(() => null);
    
    // Return cached response immediately if available
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Otherwise wait for network
    const networkResponse = await networkResponsePromise;
    return networkResponse || getOfflineResponse(request);
  } catch (error) {
    console.error('Navigation handling failed:', error);
    return getOfflineResponse(request);
  }
}

async function handleDefault(request) {
  try {
    // Check if we're online before attempting network request
    if (!navigator.onLine) {
      return getOfflineResponse(request);
    }
    
    // Create fetch with AbortController for timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 8000);
    
    const response = await fetch(request, {
      signal: controller.signal
    });
    
    clearTimeout(timeoutId);
    
    return response;
  } catch (error) {
    console.warn('Default fetch failed, serving offline response:', error.message);
    return getOfflineResponse(request);
  }
}

// Helper functions for request classification
function isStaticAsset(url) {
  return url.includes('/css/') || 
         url.includes('/js/') || 
         url.includes('/fonts/') ||
         url.includes('/favicon.ico') ||
         url.includes('/manifest.json');
}

function isImage(url) {
  return url.match(/\.(jpg|jpeg|png|gif|webp|svg|ico)$/i);
}

function isAPIRequest(url) {
  return url.includes('/api/');
}

function isCDNResource(url) {
  return url.includes('cdn.jsdelivr.net') || 
         url.includes('cdnjs.cloudflare.com') ||
         url.includes('code.jquery.com');
}

function isNavigation(request) {
  return request.mode === 'navigate' || 
         (request.method === 'GET' && request.headers.get('accept').includes('text/html'));
}

// Offline response handlers
function getOfflineResponse(request) {
  if (isNavigation(request)) {
    return caches.match('/offline.html') || 
           new Response('<!DOCTYPE html><html><head><title>Offline</title></head><body><h1>You are offline</h1><p>Please check your internet connection and try again.</p></body></html>', {
             status: 503,
             headers: { 'Content-Type': 'text/html' }
           });
  }
  
  return new Response('Offline', {
    status: 503,
    headers: { 'Content-Type': 'text/plain' }
  });
}

function getOfflineImageResponse() {
  // Return a simple SVG placeholder
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
    <rect width="200" height="200" fill="#f0f0f0"/>
    <text x="100" y="100" text-anchor="middle" dy="0.3em" font-family="Arial, sans-serif" font-size="14" fill="#666">
      Image unavailable
    </text>
  </svg>`;
  
  return new Response(svg, {
    status: 200,
    headers: { 'Content-Type': 'image/svg+xml' }
  });
}

// Background sync for attendance data
self.addEventListener('sync', event => {
  console.log('Service Worker: Background sync triggered:', event.tag);
  
  if (event.tag === 'attendance-sync') {
    event.waitUntil(syncAttendanceData());
  } else if (event.tag === 'form-sync') {
    event.waitUntil(syncFormData());
  }
});

// Enhanced push notification handling
self.addEventListener('push', event => {
  console.log('Service Worker: Push notification received', event);
  
  let notificationData = {
    title: 'Attendance System',
    body: 'You have a new notification',
    icon: '/favicon.ico',
    badge: '/favicon.ico',
    tag: 'attendance-notification',
    requireInteraction: false,
    vibrate: [200, 100, 200],
    data: {
      url: '/dashboard',
      timestamp: Date.now()
    },
    actions: [
      {
        action: 'view',
        title: 'View',
        icon: '/favicon.ico'
      },
      {
        action: 'dismiss',
        title: 'Dismiss'
      }
    ]
  };
  
  // Parse push data if available
  if (event.data) {
    try {
      const data = event.data.json();
      notificationData = {
        ...notificationData,
        title: data.title || notificationData.title,
        body: data.body || data.message || notificationData.body,
        icon: data.icon || notificationData.icon,
        badge: data.badge || notificationData.badge,
        tag: data.tag || notificationData.tag,
        requireInteraction: data.priority === 'high' || data.requireInteraction,
        vibrate: data.priority === 'high' ? [300, 100, 300, 100, 300] : notificationData.vibrate,
        data: {
          ...notificationData.data,
          ...data.data,
          url: data.url || notificationData.data.url,
          notification_id: data.id,
          type: data.type,
          priority: data.priority
        }
      };
    } catch (error) {
      console.error('Service Worker: Failed to parse push data:', error);
    }
  }
  
  // Show notification
  event.waitUntil(
    self.registration.showNotification(notificationData.title, {
      body: notificationData.body,
      icon: notificationData.icon,
      badge: notificationData.badge,
      tag: notificationData.tag,
      requireInteraction: notificationData.requireInteraction,
      vibrate: notificationData.vibrate,
      data: notificationData.data,
      actions: notificationData.actions,
      timestamp: notificationData.data.timestamp
    })
  );
});

// Enhanced notification click handling
self.addEventListener('notificationclick', event => {
  console.log('Service Worker: Notification clicked', event);
  
  const notification = event.notification;
  const action = event.action;
  const data = notification.data || {};
  
  notification.close();
  
  if (action === 'dismiss') {
    // Send dismissal analytics
    sendNotificationAnalytics('dismissed', data);
    return;
  }
  
  // Default action or 'view' action
  event.waitUntil(
    clients.matchAll({
      type: 'window',
      includeUncontrolled: true
    }).then(clientList => {
      const url = data.url || '/dashboard';
      
      // Send click analytics
      sendNotificationAnalytics('clicked', data);
      
      // Check if app is already open
      for (let client of clientList) {
        if (client.url.includes(url.split('?')[0]) && 'focus' in client) {
          return client.focus();
        }
      }
      
      // Open new window/tab
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});

// Notification close event
self.addEventListener('notificationclose', event => {
  console.log('Service Worker: Notification closed', event);
  
  const data = event.notification.data || {};
  sendNotificationAnalytics('closed', data);
});

// Send notification analytics
function sendNotificationAnalytics(action, data) {
  try {
    fetch('/api/push-notifications/analytics', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        action,
        notification_id: data.notification_id,
        type: data.type,
        priority: data.priority,
        timestamp: Date.now()
      })
    }).catch(error => {
      console.error('Service Worker: Failed to send analytics:', error);
    });
  } catch (error) {
    console.error('Service Worker: Analytics error:', error);
  }
}

// Message handling from main thread
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

// Enhanced sync functions
async function syncAttendanceData() {
  try {
    console.log('Service Worker: Syncing attendance data');
    const pendingData = await getPendingAttendanceData();
    
    for (const data of pendingData) {
      try {
        const response = await fetch('/api/v1/attendance/sync', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': data.csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(data.payload)
        });

        if (response.ok) {
          await removePendingAttendanceData(data.id);
          console.log('Attendance data synced:', data.id);
        } else {
          console.error('Sync failed with status:', response.status);
        }
      } catch (error) {
        console.error('Failed to sync attendance data:', error);
      }
    }
  } catch (error) {
    console.error('Background sync failed:', error);
  }
}

async function syncFormData() {
  try {
    console.log('Service Worker: Syncing form data');
    const pendingForms = await getPendingFormData();
    
    for (const form of pendingForms) {
      try {
        const response = await fetch(form.url, {
          method: form.method,
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': form.csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(form.data)
        });

        if (response.ok) {
          await removePendingFormData(form.id);
          console.log('Form data synced:', form.id);
        }
      } catch (error) {
        console.error('Failed to sync form data:', error);
      }
    }
  } catch (error) {
    console.error('Form sync failed:', error);
  }
}

// IndexedDB helpers for offline data storage
function openDatabase() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('AttendanceDB', 1);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    
    request.onupgradeneeded = event => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('pendingAttendance')) {
        const store = db.createObjectStore('pendingAttendance', { keyPath: 'id', autoIncrement: true });
        store.createIndex('timestamp', 'timestamp', { unique: false });
      }
    };
  });
}

async function getPendingAttendanceData() {
  const db = await openDatabase();
  const transaction = db.transaction(['pendingAttendance'], 'readonly');
  const store = transaction.objectStore('pendingAttendance');
  
  return new Promise((resolve, reject) => {
    const request = store.getAll();
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function removePendingAttendanceData(id) {
  const db = await openDatabase();
  const transaction = db.transaction(['pendingAttendance'], 'readwrite');
  const store = transaction.objectStore('pendingAttendance');
  
  return new Promise((resolve, reject) => {
    const request = store.delete(id);
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}

// Additional IndexedDB helpers for forms
async function getPendingFormData() {
  const db = await openDatabase();
  const transaction = db.transaction(['pendingForms'], 'readonly');
  const store = transaction.objectStore('pendingForms');
  
  return new Promise((resolve, reject) => {
    const request = store.getAll();
    request.onsuccess = () => resolve(request.result || []);
    request.onerror = () => reject(request.error);
  });
}

async function removePendingFormData(id) {
  const db = await openDatabase();
  const transaction = db.transaction(['pendingForms'], 'readwrite');
  const store = transaction.objectStore('pendingForms');
  
  return new Promise((resolve, reject) => {
    const request = store.delete(id);
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}

// Enhanced cache management
async function cleanupCache() {
  console.log('Service Worker: Starting cache cleanup');
  
  try {
    // Clean up different caches with different strategies
    await Promise.all([
      cleanupCacheByName(DYNAMIC_CACHE_NAME, 24 * 60 * 60 * 1000), // 24 hours
      cleanupCacheByName(IMAGE_CACHE_NAME, 7 * 24 * 60 * 60 * 1000), // 7 days
      cleanupCacheBySize(DYNAMIC_CACHE_NAME, 50), // Max 50 entries
      cleanupCacheBySize(IMAGE_CACHE_NAME, 100) // Max 100 images
    ]);
    
    console.log('Service Worker: Cache cleanup completed');
  } catch (error) {
    console.error('Service Worker: Cache cleanup failed:', error);
  }
}

async function cleanupCacheByName(cacheName, maxAge) {
  try {
    const cache = await caches.open(cacheName);
    const requests = await cache.keys();
    const now = Date.now();

    for (const request of requests) {
      const response = await cache.match(request);
      const dateHeader = response.headers.get('date');
      
      if (dateHeader) {
        const responseDate = new Date(dateHeader).getTime();
        if (now - responseDate > maxAge) {
          await cache.delete(request);
          console.log('Deleted expired cache entry:', request.url);
        }
      }
    }
  } catch (error) {
    console.error('Cache cleanup by age failed:', error);
  }
}

async function cleanupCacheBySize(cacheName, maxEntries) {
  try {
    const cache = await caches.open(cacheName);
    const requests = await cache.keys();
    
    if (requests.length > maxEntries) {
      // Remove oldest entries
      const entriesToRemove = requests.slice(0, requests.length - maxEntries);
      await Promise.all(
        entriesToRemove.map(request => cache.delete(request))
      );
      console.log(`Removed ${entriesToRemove.length} cache entries to maintain size limit`);
    }
  } catch (error) {
    console.error('Cache cleanup by size failed:', error);
  }
}

// Performance monitoring
function logPerformanceMetric(name, value) {
  console.log(`Service Worker Performance: ${name} = ${value}ms`);
}

// Periodic maintenance
self.addEventListener('message', (event) => {
  if (event.data.action === 'cleanup') {
    cleanupCache();
  } else if (event.data.action === 'performance') {
    logPerformanceMetric('cache-hit-ratio', event.data.value);
  }
});

// Run cleanup every 6 hours
setInterval(cleanupCache, 6 * 60 * 60 * 1000);

console.log('Service Worker v1.1.0 registered successfully with enhanced caching and performance optimization');