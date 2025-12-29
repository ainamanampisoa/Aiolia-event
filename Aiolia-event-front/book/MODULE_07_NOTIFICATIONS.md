# 🔔 Module 07 : Notifications

## Description

Le module Notifications gère l'ensemble des communications avec les utilisateurs : notifications in-app, notifications push web, emails transactionnels et rappels d'événements. Il permet aux utilisateurs de rester informés de leurs achats, des promotions et des événements à venir.

---

## 📂 Fichiers concernés

| Type | Fichier |
|------|---------|
| Contrôleur | `src/Controller/NotificationController.php` |
| Repository | `src/Repository/NotificationRepository.php` |
| Service | `src/Service/NotificationService.php` |
| Service | `src/Service/EventReminderService.php` |
| Mailer | `src/Service/Notification/UserMailer.php` |
| Listener | `src/EventListener/EventCancellationListener.php` |
| Templates | `templates/notifications/index.html.twig` |
| Templates | `templates/emails/*.html.twig` |
| JS | `public/js/push-notifications.js` |
| JS | `public/js/service-worker.js` |

---

## 🎯 Fonctionnalités

### 1. Notifications in-app
- **Page** : `/notifications`
- Liste des notifications avec statut lu/non lu
- Filtrage par type (tickets, offres, rappels, paiements)
- Suppression individuelle
- Marquer tout comme lu

### 2. Notifications Push Web
- Service Worker pour les notifications navigateur
- Permission demandée à l'utilisateur
- Notifications même quand l'onglet est fermé

### 3. Rappels d'événements
- Envoi automatique 24h avant l'événement
- Envoi automatique 2h avant l'événement
- Commande Symfony : `php bin/console app:send-event-reminders`

### 4. Emails transactionnels
- Email de bienvenue après inscription
- Confirmation d'achat avec billets
- Rappels d'événements
- Notification d'annulation et remboursement

---

## 🔄 Flux de notification

```
┌─────────────────┐
│   Événement     │
│   (achat, etc.) │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│Notification     │
│   Service       │
└────────┬────────┘
         │
         ├──► Création en BDD (notifications)
         ├──► Email (si activé)
         ├──► Push Web (si activé)
         │
         ▼
┌─────────────────┐
│  Utilisateur    │
│    notifié      │
└─────────────────┘
```

---

## 📋 Scénarios d'utilisation

### Scénario 1 : Notification d'achat

1. **Voahirana** achète 2 billets pour un concert
2. Le système crée une notification in-app :
   - Type : `ticket`
   - Titre : "Vos billets pour Concert Rock sont disponibles"
   - Description : "Téléchargez vos billets et partagez-les"
3. Un email est envoyé avec les billets en pièce jointe
4. Une notification push apparaît sur son navigateur

### Scénario 2 : Rappel 24h avant

1. La commande cron s'exécute chaque heure
2. Elle détecte que **Hery** a un événement demain à 19h
3. Une notification est créée :
   - Type : `reminder`
   - Titre : "Rappel : Festival Gasy demain à 19:00"
   - Description : "N'oubliez pas d'apporter vos billets !"
4. Un email de rappel est envoyé
5. Une notification push est déclenchée

### Scénario 3 : Gestion des notifications

1. **Soa** accède à `/notifications`
2. Elle voit 5 notifications non lues (badge rouge)
3. Elle clique sur une notification pour la marquer comme lue
4. Elle supprime les anciennes notifications
5. Elle clique sur "Tout marquer comme lu"
6. Le badge disparaît

### Scénario 4 : Activation des notifications push

1. **Tojo** visite le site pour la première fois
2. Une popup demande l'autorisation pour les notifications
3. Il clique sur "Autoriser"
4. Le Service Worker s'enregistre
5. Il recevra désormais les notifications même hors du site

---

## 🛠️ Points techniques

### Types de notifications

```php
private function determineNotificationType(string $templateCode, array $payload): string
{
    if (str_contains($templateCode, 'ticket')) return 'ticket';
    if (str_contains($templateCode, 'offer')) return 'offer';
    if (str_contains($templateCode, 'reminder')) return 'reminder';
    if (str_contains($templateCode, 'payment')) return 'payment';
    
    return $payload['type'] ?? 'info';
}
```

### Formatage du temps écoulé

```php
private function formatTimeAgo(\DateTimeImmutable $date): string
{
    $now = new \DateTimeImmutable();
    $diff = $now->diff($date);

    if ($diff->days > 7) return 'Il y a ' . $diff->days . ' jours';
    if ($diff->days > 0) return 'Il y a ' . $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
    if ($diff->h > 0) return 'Il y a ' . $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
    if ($diff->i > 0) return 'Il y a ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');

    return 'À l\'instant';
}
```

### Envoi de rappels (Commande)

```php
// SendEventRemindersCommand.php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Récupérer les événements dans les prochaines 24h
    $events = $this->eventReminderService->getUpcomingEvents(24);
    
    foreach ($events as $event) {
        // Récupérer les utilisateurs ayant des billets
        $users = $this->eventReminderService->getUsersWithTickets($event['id']);
        
        foreach ($users as $user) {
            // Envoyer la notification
            $this->notificationService->sendReminder($user, $event);
        }
    }
    
    return Command::SUCCESS;
}
```

### Service Worker (push-notifications.js)

```javascript
// Enregistrement du Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/js/service-worker.js')
        .then(registration => {
            console.log('Service Worker enregistré avec succès');
        });
}

// Demande de permission
Notification.requestPermission().then(permission => {
    if (permission === 'granted') {
        console.log('Notifications autorisées');
    }
});
```

### Service Worker (service-worker.js)

```javascript
self.addEventListener('push', function(event) {
    const data = event.data.json();
    
    const options = {
        body: data.body,
        icon: '/images/aiolia-logo-small.svg',
        badge: '/images/aiolia-logo-small.svg',
        tag: data.tag,
        data: { url: data.url }
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});
```

---

## 📱 Routes

| Route | Méthode | Description |
|-------|---------|-------------|
| `/notifications` | GET | Page des notifications |
| `/api/notifications` | GET | Liste des notifications (JSON) |
| `/api/notifications/{id}/read` | POST | Marquer comme lu |
| `/api/notifications/read-all` | POST | Tout marquer comme lu |
| `/api/notifications/{id}/delete` | DELETE | Supprimer |
| `/api/notifications/count` | GET | Nombre de non lues |
| `/api/notifications/{id}/trigger-push` | POST | Déclencher push |

---

## 🎨 Éléments d'interface

### Page notifications

| Élément | Description |
|---------|-------------|
| Header | Titre + bouton "Tout marquer comme lu" |
| Filtres | Onglets (Toutes, Non lues, Lues) |
| Liste | Cartes de notification avec icône, titre, description, temps |
| Badge | Point rouge sur les non lues |
| Actions | Bouton supprimer, marquer comme lu |
| Vide | Message "Aucune notification" |

### Carte notification

| Élément | Description |
|---------|-------------|
| Icône | 🎟️ Ticket, 🎁 Offre, ⏰ Rappel, 💳 Paiement |
| Titre | Texte principal avec mise en forme |
| Description | Texte secondaire |
| Temps | "Il y a 2 heures", "Hier" |
| État | Fond différent si non lu |

### Notification Push

| Élément | Description |
|---------|-------------|
| Icône | Logo Aiolia-event |
| Titre | Titre de la notification |
| Corps | Message court |
| Actions | Clic ouvre la page concernée |

---

## 📊 Structure de données

### Table `notifications`

```sql
CREATE TABLE aiolia.notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES aiolia.users(id),
    template_id INTEGER REFERENCES aiolia.notification_templates(id),
    channel VARCHAR(50), -- 'in_app', 'email', 'web_push'
    payload JSONB,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);
```

### Table `notification_templates`

```sql
CREATE TABLE aiolia.notification_templates (
    id SERIAL PRIMARY KEY,
    code VARCHAR(100) UNIQUE,
    subject VARCHAR(255),
    body TEXT,
    channels VARCHAR(255)[] -- ['in_app', 'email', 'web_push']
);
```

---

## ⚙️ Configuration

### Cron job pour les rappels

```bash
# Exécuter toutes les heures
0 * * * * cd /path/to/project && php bin/console app:send-event-reminders
```

### Variables d'environnement

```env
MAILER_DSN=smtp://user:password@smtp.example.com:587
NOTIFICATION_FROM_EMAIL=notifications@aiolia-event.com
NOTIFICATION_FROM_NAME="Aiolia Event"
```

---

## 🔗 Dépendances

- **NotificationRepository** : Accès BDD notifications
- **NotificationService** : Logique d'envoi
- **EventReminderService** : Gestion des rappels
- **UserMailer** : Envoi d'emails
- **Symfony Mailer** : Transport email
- **Service Worker API** : Notifications push

