// Service Worker para App-Tareas PWA
const CACHE_NAME = 'app-tareas-v1';
const urlsToCache = [
  '/public/index.php',
  '/public/login.php',
  '/public/calendar.php',
  '/assets/style.css',
  '/assets/icon-192x192.png',
  '/assets/icon-512x512.png'
];

// Instalación del Service Worker
self.addEventListener('install', event => {
  console.log('[SW] Instalando Service Worker...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[SW] Cache abierto');
        return cache.addAll(urlsToCache);
      })
      .catch(err => {
        console.log('[SW] Error al cachear archivos:', err);
      })
  );
  self.skipWaiting();
});

// Activación del Service Worker
self.addEventListener('activate', event => {
  console.log('[SW] Activando Service Worker...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('[SW] Eliminando cache antiguo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Interceptar peticiones (estrategia Network First con Cache Fallback)
self.addEventListener('fetch', event => {
  // Solo cachear GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Si la respuesta es válida, la guardamos en cache
        if (response && response.status === 200) {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME)
            .then(cache => {
              cache.put(event.request, responseToCache);
            });
        }
        return response;
      })
      .catch(() => {
        // Si falla la red, intentamos servir desde cache
        return caches.match(event.request)
          .then(response => {
            if (response) {
              return response;
            }
            // Si no está en cache, retornar página offline
            return caches.match('/public/offline.php');
          });
      })
  );
});

// Notificaciones Push (opcional)
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'Nueva notificación de App-Tareas',
    icon: '/assets/icon-192x192.png',
    badge: '/assets/icon-96x96.png',
    vibrate: [200, 100, 200],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'Ver tareas',
        icon: '/assets/icon-96x96.png'
      },
      {
        action: 'close',
        title: 'Cerrar',
        icon: '/assets/icon-96x96.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('App-Tareas', options)
  );
});

// Manejar clicks en notificaciones
self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/public/index.php')
    );
  }
});

// Sincronización en background
self.addEventListener('sync', event => {
  if (event.tag === 'sync-tasks') {
    event.waitUntil(syncTasks());
  }
});

function syncTasks() {
  console.log('[SW] Sincronizando tareas en background...');
  // Aquí podrías sincronizar datos con el servidor
  return Promise.resolve();
}
