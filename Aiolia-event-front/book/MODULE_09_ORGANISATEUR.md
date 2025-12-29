# 🎪 Module 09 : Espace Organisateur

## Description

L'espace Organisateur permet aux créateurs d'événements de gérer leurs événements, suivre les ventes, créer des promotions et consulter des rapports détaillés. Ce module inclut également les fonctionnalités d'administration comme l'annulation d'événements et les remboursements automatiques.

---

## 📂 Fichiers concernés

| Type | Fichier |
|------|---------|
| Contrôleur | `src/Controller/OrganizerController.php` |
| Repository | `src/Repository/EventRepository.php` |
| Service | `src/Service/RefundService.php` |
| Listener | `src/EventListener/EventCancellationListener.php` |
| Templates | `templates/organizer/become.html.twig` |

---

## 🎯 Fonctionnalités

### 1. Gestion des événements
- **Liste des événements** : Voir tous ses événements
- **Création** : Créer un nouvel événement
- **Modification** : Mettre à jour les informations
- **Suppression** : Retirer un événement

### 2. Gestion des billets
- **Statistiques** : Billets vendus par catégorie
- **Inventaire** : Stock disponible
- **Tarification** : Prix par type de billet

### 3. Promotions
- **Création** : Codes promo pour un événement
- **Suivi** : Utilisation des codes
- **Expiration** : Gestion des dates de validité

### 4. Tableau de bord
- **Revenus** : Total des ventes
- **Graphiques** : Évolution des ventes
- **KPIs** : Taux de remplissage, panier moyen

### 5. Rapports
- **Par événement** : Détails des ventes
- **Export** : CSV et PDF

### 6. Administration (Admin uniquement)
- **Annulation d'événement** : Avec remboursement automatique
- **Gestion des utilisateurs** : Validation des organisateurs

---

## 🔄 Flux d'annulation d'événement

```
┌─────────────────┐
│     Admin       │
│ clique Annuler  │
└────────┬────────┘
         │ POST /api/admin/events/{id}/cancel
         ▼
┌─────────────────┐
│OrganizerController│
│  cancelEvent()    │
└────────┬────────┘
         │
         ├──► Vérification rôle Admin
         ├──► Vérification statut événement
         ├──► Mise à jour status = 'cancelled'
         │
         ▼
┌─────────────────┐
│  RefundService  │
│refundEventTickets│
└────────┬────────┘
         │
         ├──► Récupération commandes payées
         ├──► Calcul montants à rembourser
         ├──► Crédit sur wallet utilisateurs
         ├──► Notifications envoyées
         │
         ▼
┌─────────────────┐
│  Utilisateurs   │
│   remboursés    │
└─────────────────┘
```

---

## 📋 Scénarios d'utilisation

### Scénario 1 : Devenir organisateur

1. **Tiana** est passionnée d'événements
2. Elle accède à `/become-organizer`
3. Elle remplit le formulaire :
   - Nom de l'organisation
   - Type (Entreprise / Association / Particulier)
   - Documents justificatifs
4. Sa demande est soumise pour validation
5. L'admin approuve sa demande
6. Elle reçoit l'accès à l'espace organisateur

### Scénario 2 : Création d'un événement

1. **Voahangy** accède à son espace organisateur
2. Elle clique sur "Créer un événement"
3. Elle remplit les informations :
   - Titre : "Festival Jazz Antananarivo"
   - Description, date, lieu
   - Image de couverture
   - Types de billets et prix
4. Elle publie l'événement
5. Il apparaît sur la plateforme
6. Les ventes peuvent commencer

### Scénario 3 : Suivi des ventes

1. **Rajo** consulte son tableau de bord
2. Il voit pour son événement :
   - 150 billets vendus sur 500
   - Revenu : 15 000 000 MGA
   - Taux de remplissage : 30%
3. Il consulte les détails par type :
   - VIP : 20 vendus (100%)
   - Standard : 100 vendus (25%)
   - Enfant : 30 vendus (60%)
4. Il décide de créer une promo pour les Standard

### Scénario 4 : Annulation et remboursement (Admin)

1. Un **Admin** doit annuler un événement (force majeure)
2. Il accède à la gestion de l'événement
3. Il clique sur "Annuler l'événement"
4. Il saisit la raison : "Conditions météo défavorables"
5. Le système :
   - Passe l'événement en "Annulé"
   - Récupère toutes les commandes payées
   - Calcule les montants à rembourser
   - Crédite les wallets des utilisateurs
   - Envoie des notifications
6. Résumé affiché :
   - 75 commandes remboursées
   - 150 billets annulés
   - 7 500 000 MGA remboursés

---

## 🛠️ Points techniques

### Vérification des droits Admin

```php
public function cancelEvent(int $id, Request $request): JsonResponse
{
    $sessionUser = $session->get('user');
    
    // Vérifier l'authentification
    if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
        return new JsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
    }

    // Vérifier le rôle Admin
    $isAdmin = in_array('ROLE_ADMIN', $sessionUser['roles'] ?? [], true);
    if (!$isAdmin) {
        return new JsonResponse(['success' => false, 'message' => 'Accès réservé aux administrateurs'], 403);
    }
    
    // Continuer avec l'annulation...
}
```

### Service de remboursement

```php
public function refundEventTickets(int $eventId, string $reason): array
{
    $result = [
        'refunded_orders' => 0,
        'refunded_tickets' => 0,
        'total_amount' => 0,
        'errors' => [],
    ];
    
    // Récupérer les commandes payées pour cet événement
    $orders = $this->getOrdersForEvent($eventId);
    
    foreach ($orders as $order) {
        try {
            // Calculer le montant à rembourser
            $refundAmount = $this->calculateRefundAmount($order);
            
            // Créditer le wallet de l'utilisateur
            $this->walletService->credit(
                $order['user_id'],
                $refundAmount,
                "Remboursement - $reason"
            );
            
            // Marquer la commande comme remboursée
            $this->markOrderAsRefunded($order['id'], $reason);
            
            // Annuler les billets
            $ticketCount = $this->cancelOrderTickets($order['id']);
            
            // Notifier l'utilisateur
            $this->notificationService->sendRefundNotification(
                $order['user_id'],
                $eventId,
                $refundAmount
            );
            
            $result['refunded_orders']++;
            $result['refunded_tickets'] += $ticketCount;
            $result['total_amount'] += $refundAmount;
            
        } catch (\Exception $e) {
            $result['errors'][] = [
                'order_id' => $order['id'],
                'error' => $e->getMessage(),
            ];
        }
    }
    
    return $result;
}
```

### Mise à jour de l'événement

```php
$this->connection->executeStatement(
    <<<SQL
        UPDATE aiolia.events
        SET status = 'cancelled',
            updated_at = NOW()
        WHERE id = :event_id
    SQL,
    ['event_id' => $id]
);
```

---

## 📱 Routes

### Routes Organisateur

| Route | Méthode | Description |
|-------|---------|-------------|
| `/become-organizer` | GET | Page devenir organisateur |
| `/api/organizer/events` | GET | Liste des événements |
| `/api/organizer/events` | POST | Créer un événement |
| `/api/organizer/events/{id}` | PUT | Modifier un événement |
| `/api/organizer/events/{id}` | DELETE | Supprimer un événement |
| `/api/organizer/events/{id}/tickets` | GET | Stats billets |
| `/api/organizer/events/{id}/promotions` | GET | Liste promos |
| `/api/organizer/events/{id}/promotions` | POST | Créer promo |
| `/api/organizer/dashboard` | GET | Tableau de bord |
| `/api/organizer/reports/{eventId}` | GET | Rapport événement |
| `/api/organizer/reports/{eventId}/export` | GET | Export rapport |

### Routes Admin

| Route | Méthode | Description |
|-------|---------|-------------|
| `/api/admin/events/{id}/cancel` | POST | Annuler un événement |

---

## 🎨 Éléments d'interface

### Tableau de bord organisateur

| Élément | Description |
|---------|-------------|
| KPIs | Total événements, billets vendus, revenus |
| Graphique | Ventes par mois |
| Liste événements | Cartes avec stats rapides |
| Actions rapides | Créer événement, voir rapports |

### Page gestion événement

| Élément | Description |
|---------|-------------|
| Header | Titre, statut, date |
| Stats | Billets vendus, revenus, taux |
| Onglets | Détails, Billets, Promos, Rapports |
| Actions | Modifier, Dupliquer, Annuler |

### Modal annulation (Admin)

| Élément | Description |
|---------|-------------|
| Titre | "Annuler l'événement" |
| Avertissement | Message d'alerte rouge |
| Champ raison | Textarea obligatoire |
| Boutons | "Annuler" / "Confirmer l'annulation" |

---

## 📊 Structure de données

### Réponse annulation

```json
{
    "success": true,
    "message": "Événement annulé. Remboursements en cours...",
    "refund_summary": {
        "refunded_orders": 75,
        "refunded_tickets": 150,
        "total_amount": 7500000,
        "errors": []
    }
}
```

### Tableau de bord

```json
{
    "total_events": 5,
    "total_tickets_sold": 450,
    "total_revenue": 45000000,
    "upcoming_events": [
        {
            "id": 1,
            "title": "Concert Rock",
            "date": "2024-02-15",
            "tickets_sold": 200,
            "capacity": 500
        }
    ]
}
```

---

## 🔒 Sécurité

| Rôle | Permissions |
|------|-------------|
| `ROLE_USER` | Accès lecture seule (page devenir organisateur) |
| `ROLE_ORGANIZER` | Gestion de ses propres événements |
| `ROLE_ADMIN` | Annulation d'événements, remboursements |

---

## 🔗 Dépendances

- **EventRepository** : Accès événements
- **RefundService** : Logique de remboursement
- **WalletService** : Crédit des utilisateurs
- **NotificationService** : Notifications
- **EventCancellationListener** : Écoute des annulations

