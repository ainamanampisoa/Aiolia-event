/**
 * Service Worker pour les notifications push
 */

// Écouter les messages du client
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SHOW_NOTIFICATION') {
        const { title, options } = event.data;
        self.registration.showNotification(title, options);
    }
});

// Gérer les clics sur les notifications
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const notificationData = event.notification.data || {};
    const url = notificationData.url || '/notifications';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // Si une fenêtre est déjà ouverte, la focus
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            // Sinon, ouvrir une nouvelle fenêtre
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );

    // Envoyer un message au client pour gérer la navigation
    event.waitUntil(
        clients.matchAll().then((clientList) => {
            clientList.forEach((client) => {
                client.postMessage({
                    type: 'NOTIFICATION_CLICK',
                    url: url,
                });
            });
        })
    );
});

// Gérer l'installation du service worker
self.addEventListener('install', (event) => {
    console.log('Service Worker installé');
    self.skipWaiting();
});

// Gérer l'activation du service worker
self.addEventListener('activate', (event) => {
    console.log('Service Worker activé');
    event.waitUntil(self.clients.claim());
});

