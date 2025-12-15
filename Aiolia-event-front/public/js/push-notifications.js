/**
 * Gestion des notifications push web
 */
class PushNotificationManager {
    constructor() {
        this.serviceWorkerRegistration = null;
        this.isSupported = 'Notification' in window && 'serviceWorker' in navigator;
        this.permission = Notification.permission;
    }

    /**
     * Initialise le gestionnaire de notifications push
     */
    async init() {
        if (!this.isSupported) {
            console.log('Les notifications push ne sont pas supportées par ce navigateur');
            return false;
        }

        try {
            // Enregistrer le service worker
            const registration = await navigator.serviceWorker.register('/js/service-worker.js');
            this.serviceWorkerRegistration = registration;
            
            console.log('Service Worker enregistré avec succès');
            
            // Demander la permission si nécessaire
            if (this.permission === 'default') {
                await this.requestPermission();
            }

            return true;
        } catch (error) {
            console.error('Erreur lors de l\'enregistrement du service worker:', error);
            return false;
        }
    }

    /**
     * Demande la permission pour les notifications
     */
    async requestPermission() {
        if (!this.isSupported) {
            return false;
        }

        try {
            const permission = await Notification.requestPermission();
            this.permission = permission;
            
            if (permission === 'granted') {
                console.log('Permission accordée pour les notifications');
                return true;
            } else {
                console.log('Permission refusée pour les notifications');
                return false;
            }
        } catch (error) {
            console.error('Erreur lors de la demande de permission:', error);
            return false;
        }
    }

    /**
     * Affiche une notification
     */
    async showNotification(title, options = {}) {
        if (!this.isSupported || this.permission !== 'granted') {
            console.log('Les notifications ne sont pas autorisées');
            return false;
        }

        try {
            if (this.serviceWorkerRegistration) {
                await this.serviceWorkerRegistration.showNotification(title, {
                    icon: '/images/aiolia-logo-small.svg',
                    badge: '/images/aiolia-logo-small.svg',
                    vibrate: [200, 100, 200],
                    tag: options.tag || 'notification',
                    requireInteraction: false,
                    ...options,
                });
                return true;
            } else {
                // Fallback vers Notification API si le service worker n'est pas disponible
                new Notification(title, {
                    icon: '/images/aiolia-logo-small.svg',
                    ...options,
                });
                return true;
            }
        } catch (error) {
            console.error('Erreur lors de l\'affichage de la notification:', error);
            return false;
        }
    }

    /**
     * Écoute les messages du service worker
     */
    setupMessageListener() {
        if (!this.isSupported) {
            return;
        }

        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'NOTIFICATION_CLICK') {
                // Rediriger vers la page de notifications ou l'événement
                if (event.data.url) {
                    window.location.href = event.data.url;
                } else {
                    window.location.href = '/notifications';
                }
            }
        });
    }
}

// Initialiser le gestionnaire de notifications au chargement de la page
let pushNotificationManager = null;

document.addEventListener('DOMContentLoaded', () => {
    pushNotificationManager = new PushNotificationManager();
    pushNotificationManager.init().then((success) => {
        if (success) {
            pushNotificationManager.setupMessageListener();
            // Exposer globalement après initialisation
            window.pushNotificationManager = pushNotificationManager;
        }
    });
});

