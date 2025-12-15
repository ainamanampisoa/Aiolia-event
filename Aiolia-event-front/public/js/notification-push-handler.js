/**
 * Gestionnaire pour déclencher les notifications push web depuis le serveur
 */

document.addEventListener('DOMContentLoaded', () => {
    // Attendre que le PushNotificationManager soit initialisé
    function waitForPushManager() {
        if (typeof window.pushNotificationManager !== 'undefined' && window.pushNotificationManager) {
            initializePushHandler();
        } else {
            // Réessayer après un court délai
            setTimeout(waitForPushManager, 500);
        }
    }
    
    // Démarrer après un court délai pour laisser le temps au PushNotificationManager de s'initialiser
    setTimeout(waitForPushManager, 1500);
});

function initializePushHandler() {
    const pushManager = window.pushNotificationManager;
    if (!pushManager) {
        console.warn('PushNotificationManager non disponible');
        return;
    }

    // Écouter les nouvelles notifications créées
    // Vérifier périodiquement les nouvelles notifications non lues
    let lastNotificationCount = 0;
    
    function checkNewNotifications() {
        fetch('/api/notifications/count', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return null;
            }
            if (!response.ok) {
                return null;
            }
            return response.json();
        })
        .then(data => {
            if (!data) {
                return;
            }
            if (data && data.status === 'success') {
                const currentCount = data.count || 0;
                
                // Si le nombre de notifications a augmenté, récupérer la dernière notification non lue
                if (currentCount > lastNotificationCount) {
                    fetch('/api/notifications?filter=unread', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                    .then(response => {
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json') || !response.ok) {
                            return null;
                        }
                        return response.json();
                    })
                    .then(notifData => {
                        if (notifData && notifData.status === 'success' && notifData.notifications && notifData.notifications.length > 0) {
                            // Prendre la notification la plus récente (première dans la liste)
                            const notification = notifData.notifications[0];
                            
                            // Déclencher la notification push pour toutes les notifications non lues récentes
                            // (reminder, ticket, offer, payment, etc.)
                            triggerPushNotification(notification.id);
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors de la récupération de la notification:', error);
                    });
                }
                
                lastNotificationCount = currentCount;
            }
        })
        .catch(error => {
            console.error('Erreur lors de la vérification des notifications:', error);
        });
    }
    
    // Vérifier toutes les 5 secondes
    setInterval(checkNewNotifications, 5000);
    
    // Initialiser le compteur au chargement
    setTimeout(() => {
        fetch('/api/notifications/count')
            .then(response => response.json())
            .then(data => {
                if (data && data.status === 'success') {
                    lastNotificationCount = data.count || 0;
                }
            });
    }, 2000);
}

/**
 * Déclenche une notification push web pour une notification spécifique
 */
async function triggerPushNotification(notificationId) {
    // Attendre que le PushNotificationManager soit disponible
    if (!window.pushNotificationManager) {
        console.warn('PushNotificationManager non disponible, réessai dans 1 seconde...');
        setTimeout(() => triggerPushNotification(notificationId), 1000);
        return;
    }

    try {
        const response = await fetch(`/api/notifications/${notificationId}/trigger-push`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            console.warn('Erreur HTTP lors du déclenchement de la notification push:', response.status);
            return;
        }

        const data = await response.json();
        
        if (data.status === 'success' && data.notification) {
            const notif = data.notification;
            
            // Vérifier que la permission est accordée
            if (Notification.permission !== 'granted') {
                console.log('Permission de notification non accordée');
                return;
            }
            
            // Afficher la notification push
            const success = await window.pushNotificationManager.showNotification(notif.title, {
                body: notif.body,
                icon: notif.icon || '/images/aiolia-logo-small.svg',
                badge: notif.badge || '/images/aiolia-logo-small.svg',
                tag: notif.tag,
                data: notif.data,
                requireInteraction: false,
                vibrate: [200, 100, 200],
            });
            
            if (success) {
                console.log('Notification push affichée:', notif.title);
            } else {
                console.warn('Échec de l\'affichage de la notification push');
            }
        } else {
            console.warn('Réponse inattendue du serveur:', data);
        }
    } catch (error) {
        console.error('Erreur lors du déclenchement de la notification push:', error);
    }
}

// Exposer la fonction globalement
window.triggerPushNotification = triggerPushNotification;

