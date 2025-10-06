// ExtremeLife MLM Dashboard Service Worker
// Provides offline functionality and caching for PWA

const CACHE_NAME = 'extremelife-mlm-v1.0.0';
const STATIC_CACHE = 'extremelife-static-v1.0.0';
const DYNAMIC_CACHE = 'extremelife-dynamic-v1.0.0';

// Files to cache for offline functionality
const STATIC_FILES = [
    '/',
    '/member_dashboard_enhanced_v2.php',
    '/css/extremelife-dashboard-enhanced.css',
    '/js/extremelife-dashboard-enhanced.js',
    '/manifest.json',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js',
    '/images/extremelife-logo.png',
    '/images/default-avatar.png',
    '/images/product-placeholder.jpg'
];

// API endpoints that should be cached
const API_ENDPOINTS = [
    '/ajax/dashboard_stats.php',
    '/ajax/check_notifications.php'
];

// Install event - cache static files
self.addEventListener('install', function(event) {
    console.log('Service Worker: Installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(function(cache) {
                console.log('Service Worker: Caching static files');
                return cache.addAll(STATIC_FILES);
            })
            .then(function() {
                console.log('Service Worker: Static files cached successfully');
                return self.skipWaiting();
            })
            .catch(function(error) {
                console.error('Service Worker: Error caching static files:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', function(event) {
    console.log('Service Worker: Activating...');
    
    event.waitUntil(
        caches.keys()
            .then(function(cacheNames) {
                return Promise.all(
                    cacheNames.map(function(cacheName) {
                        if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
                            console.log('Service Worker: Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(function() {
                console.log('Service Worker: Activated successfully');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve cached content when offline
self.addEventListener('fetch', function(event) {
    const request = event.request;
    const url = new URL(request.url);
    
    // Handle different types of requests
    if (request.method === 'GET') {
        if (isStaticFile(request.url)) {
            // Static files - cache first strategy
            event.respondWith(cacheFirst(request));
        } else if (isAPIEndpoint(request.url)) {
            // API endpoints - network first with cache fallback
            event.respondWith(networkFirstWithCache(request));
        } else if (request.destination === 'document') {
            // HTML pages - network first with cache fallback
            event.respondWith(networkFirstWithCache(request));
        } else {
            // Other resources - cache first with network fallback
            event.respondWith(cacheFirst(request));
        }
    }
});

// Cache first strategy - for static files
function cacheFirst(request) {
    return caches.match(request)
        .then(function(cachedResponse) {
            if (cachedResponse) {
                return cachedResponse;
            }
            
            return fetch(request)
                .then(function(networkResponse) {
                    if (networkResponse.ok) {
                        const responseClone = networkResponse.clone();
                        caches.open(DYNAMIC_CACHE)
                            .then(function(cache) {
                                cache.put(request, responseClone);
                            });
                    }
                    return networkResponse;
                })
                .catch(function(error) {
                    console.error('Service Worker: Network request failed:', error);
                    return getOfflineFallback(request);
                });
        });
}

// Network first with cache fallback - for dynamic content
function networkFirstWithCache(request) {
    return fetch(request)
        .then(function(networkResponse) {
            if (networkResponse.ok) {
                const responseClone = networkResponse.clone();
                caches.open(DYNAMIC_CACHE)
                    .then(function(cache) {
                        cache.put(request, responseClone);
                    });
            }
            return networkResponse;
        })
        .catch(function(error) {
            console.log('Service Worker: Network failed, trying cache:', error);
            return caches.match(request)
                .then(function(cachedResponse) {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    return getOfflineFallback(request);
                });
        });
}

// Check if URL is a static file
function isStaticFile(url) {
    return STATIC_FILES.some(function(staticFile) {
        return url.includes(staticFile);
    }) || url.includes('.css') || url.includes('.js') || url.includes('.png') || url.includes('.jpg') || url.includes('.ico');
}

// Check if URL is an API endpoint
function isAPIEndpoint(url) {
    return API_ENDPOINTS.some(function(endpoint) {
        return url.includes(endpoint);
    }) || url.includes('/ajax/');
}

// Get offline fallback response
function getOfflineFallback(request) {
    if (request.destination === 'document') {
        return caches.match('/offline.html') || new Response(
            getOfflineHTML(),
            {
                headers: { 'Content-Type': 'text/html' }
            }
        );
    }
    
    if (request.destination === 'image') {
        return caches.match('/images/offline-placeholder.png') || new Response(
            '',
            { status: 404, statusText: 'Image not available offline' }
        );
    }
    
    return new Response(
        JSON.stringify({
            success: false,
            message: 'This feature is not available offline',
            offline: true
        }),
        {
            headers: { 'Content-Type': 'application/json' },
            status: 503,
            statusText: 'Service Unavailable'
        }
    );
}

// Generate offline HTML page
function getOfflineHTML() {
    return `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ExtremeLife MLM - Offline</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #2d5a27 0%, #4a7c59 100%);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                text-align: center;
            }
            .offline-container {
                max-width: 400px;
                padding: 2rem;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 20px;
                backdrop-filter: blur(10px);
            }
            .offline-icon {
                font-size: 4rem;
                margin-bottom: 1rem;
            }
            .offline-title {
                font-size: 1.5rem;
                font-weight: 600;
                margin-bottom: 1rem;
            }
            .offline-message {
                margin-bottom: 2rem;
                opacity: 0.9;
            }
            .retry-button {
                background: rgba(255, 255, 255, 0.2);
                border: 1px solid rgba(255, 255, 255, 0.3);
                color: white;
                padding: 0.75rem 1.5rem;
                border-radius: 10px;
                cursor: pointer;
                font-size: 1rem;
                transition: all 0.3s ease;
            }
            .retry-button:hover {
                background: rgba(255, 255, 255, 0.3);
            }
        </style>
    </head>
    <body>
        <div class="offline-container">
            <div class="offline-icon">📱</div>
            <div class="offline-title">You're Offline</div>
            <div class="offline-message">
                Some features may not be available while you're offline. 
                Please check your internet connection and try again.
            </div>
            <button class="retry-button" onclick="window.location.reload()">
                Try Again
            </button>
        </div>
    </body>
    </html>
    `;
}

// Handle push notifications
self.addEventListener('push', function(event) {
    console.log('Service Worker: Push notification received');
    
    let notificationData = {
        title: 'ExtremeLife MLM',
        body: 'You have a new notification',
        icon: '/images/icons/icon-192x192.png',
        badge: '/images/icons/badge-72x72.png',
        tag: 'extremelife-notification',
        requireInteraction: false,
        actions: [
            {
                action: 'view',
                title: 'View Dashboard',
                icon: '/images/icons/action-view.png'
            },
            {
                action: 'dismiss',
                title: 'Dismiss',
                icon: '/images/icons/action-dismiss.png'
            }
        ]
    };
    
    if (event.data) {
        try {
            const data = event.data.json();
            notificationData = Object.assign(notificationData, data);
        } catch (error) {
            console.error('Service Worker: Error parsing push data:', error);
        }
    }
    
    event.waitUntil(
        self.registration.showNotification(notificationData.title, notificationData)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', function(event) {
    console.log('Service Worker: Notification clicked');
    
    event.notification.close();
    
    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow('/member_dashboard_enhanced_v2.php')
        );
    } else if (event.action === 'dismiss') {
        // Just close the notification
        return;
    } else {
        // Default action - open dashboard
        event.waitUntil(
            clients.matchAll({ type: 'window' })
                .then(function(clientList) {
                    for (let i = 0; i < clientList.length; i++) {
                        const client = clientList[i];
                        if (client.url.includes('member_dashboard') && 'focus' in client) {
                            return client.focus();
                        }
                    }
                    if (clients.openWindow) {
                        return clients.openWindow('/member_dashboard_enhanced_v2.php');
                    }
                })
        );
    }
});

// Handle background sync
self.addEventListener('sync', function(event) {
    console.log('Service Worker: Background sync triggered');
    
    if (event.tag === 'dashboard-sync') {
        event.waitUntil(syncDashboardData());
    }
});

// Sync dashboard data when back online
function syncDashboardData() {
    return fetch('/ajax/dashboard_stats.php')
        .then(function(response) {
            if (response.ok) {
                return response.json();
            }
            throw new Error('Sync failed');
        })
        .then(function(data) {
            console.log('Service Worker: Dashboard data synced successfully');
            // Notify all clients about the sync
            return self.clients.matchAll()
                .then(function(clients) {
                    clients.forEach(function(client) {
                        client.postMessage({
                            type: 'DASHBOARD_SYNCED',
                            data: data
                        });
                    });
                });
        })
        .catch(function(error) {
            console.error('Service Worker: Dashboard sync failed:', error);
        });
}

console.log('Service Worker: Loaded successfully');
