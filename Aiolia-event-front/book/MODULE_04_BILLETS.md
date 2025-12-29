# 🎟️ Module 04 : Gestion des Billets

## Description

Le module Billets gère l'ensemble du parcours d'achat : ajout au panier, gestion du panier, processus de paiement, confirmation de commande et accès aux billets achetés. Il inclut également la génération de PDF pour les billets avec QR codes.

---

## 📂 Fichiers concernés

| Type | Fichier |
|------|---------|
| Contrôleur | `src/Controller/TicketController.php` |
| Repository | `src/Repository/TicketRepository.php` |
| Repository | `src/Repository/EventRepository.php` |
| Service | `src/Service/CartSyncService.php` |
| Service | `src/Service/PaymentService.php` |
| Service | `src/Service/ActivityService.php` |
| Templates | `templates/ticket/cart.html.twig` |
| Templates | `templates/ticket/payment.html.twig` |
| Templates | `templates/ticket/confirmation.html.twig` |
| Templates | `templates/ticket/my_tickets.html.twig` |
| Templates | `templates/ticket/my_ticket_details.html.twig` |
| Templates | `templates/ticket/pdf.html.twig` |

---

## 🎯 Fonctionnalités

### 1. Panier d'achat
- **Route** : `/cart`
- **Ajout** : POST `/add-to-cart`
- **Suppression** : POST/DELETE `/cart/remove/{cartKey}`
- **Synchronisation** : Session PHP + Base de données
- **Persistance** : Le panier survit à la déconnexion/reconnexion

### 2. Processus de paiement
- **Page récapitulatif** : `/checkout/summary`
- **Page paiement** : `/checkout/payment`
- **Traitement** : POST `/checkout/process`
- **Confirmation** : `/checkout/confirmation`

### 3. Mes billets
- **Liste** : `/my-tickets`
- **Filtres** : À venir, passés, annulés
- **Détails** : `/my-tickets/{id}`
- **PDF** : `/my-tickets/{id}/pdf`

### 4. Fonctionnalités avancées
- **Calendrier** : Ajouter au calendrier (Google, Apple, Outlook)
- **Transfert** : Transférer un billet à un autre utilisateur
- **QR Code** : Code unique pour validation à l'entrée

---

## 🔄 Flux d'ajout au panier

```
┌─────────────────┐
│  Page détails   │
│   événement     │
└────────┬────────┘
         │ POST /add-to-cart
         │ event_id, ticket_type_id, adult_qty, child_qty
         ▼
┌─────────────────┐
│ TicketController│
│   addToCart()   │
└────────┬────────┘
         │
         ├──► Validation des données
         ├──► Récupération détails événement
         ├──► Calcul des prix
         ├──► Mise à jour session
         ├──► Synchronisation BDD (si connecté)
         │
         ▼
┌─────────────────┐
│   Redirection   │
│   vers /cart    │
└─────────────────┘
```

---

## 🔄 Flux de paiement

```
┌─────────────────┐
│   Page panier   │
│     /cart       │
└────────┬────────┘
         │ Clic "Payer"
         ▼
┌─────────────────┐
│ /checkout/payment│
│ Récap + formulaire│
└────────┬────────┘
         │ POST /checkout/process
         │ payment_method, name, email, phone
         ▼
┌─────────────────┐
│ PaymentService  │
│ processPayment()│
└────────┬────────┘
         │
         ├──► Création commande en BDD
         ├──► Initiation paiement MVola
         ├──► Attente callback MVola
         ├──► Création des billets
         ├──► Vidage du panier
         │
         ▼
┌─────────────────┐
│  /checkout/     │
│  confirmation   │
└─────────────────┘
```

---

## 📋 Scénarios d'utilisation

### Scénario 1 : Achat de billets par Hery

1. **Hery** consulte un événement "Concert Rock"
2. Il sélectionne 2 billets adultes et 1 billet enfant
3. Il clique sur "Ajouter au panier"
4. Il est redirigé vers son panier
5. Il voit le récapitulatif :
   - 2 × Billet Adulte = 100 000 MGA
   - 1 × Billet Enfant = 25 000 MGA
   - **Total : 125 000 MGA**
6. Il clique sur "Procéder au paiement"
7. Il choisit MVola comme méthode de paiement
8. Il entre son numéro de téléphone
9. Il accepte les CGU et valide
10. Il reçoit une notification MVola sur son téléphone
11. Il confirme le paiement
12. La page de confirmation s'affiche avec le numéro de commande

### Scénario 2 : Téléchargement du billet

1. **Soa** accède à "Mes billets"
2. Elle voit ses billets à venir
3. Elle clique sur un billet
4. Elle voit les détails : événement, date, lieu, QR code
5. Elle clique sur "Télécharger PDF"
6. Un fichier PDF est généré avec :
   - Informations de l'événement
   - QR code unique
   - Image de l'événement
   - Instructions d'accès

### Scénario 3 : Ajout au calendrier

1. **Tiana** consulte son billet
2. Elle clique sur l'icône calendrier
3. Un menu déroulant s'affiche :
   - 📅 Google Calendar
   - 🍎 Apple Calendar
   - 📧 Outlook
4. Elle choisit Google Calendar
5. Un nouvel onglet s'ouvre avec l'événement pré-rempli
6. Elle confirme l'ajout à son calendrier

---

## 🛠️ Points techniques

### Structure du panier en session

```php
$cartItems = [
    'event_42_ticket_5' => [
        'eventId' => 42,
        'ticketTypeId' => 5,
        'adultTicketTypeId' => 5,
        'childTicketTypeId' => 6,
        'adultQuantity' => 2,
        'childQuantity' => 1,
        'adultPrice' => 50000,
        'childPrice' => 25000,
        'currency' => 'MGA',
        'added_at' => '2024-01-15 10:30:00',
    ],
];
```

### Synchronisation panier Session ↔ BDD

```php
// Récupérer le panier depuis la DB (source de vérité)
$dbCart = $this->cartSyncService->getOrCreateCart($userId, null);
if ($dbCart && !empty($dbCart['items'])) {
    $dbItems = $this->cartSyncService->convertDbItemsToSessionFormat($dbCart['items']);
    // Fusionner les deux paniers
    $cartItems = $this->cartSyncService->mergeCarts($cartItems, $dbItems);
    $session->set('cart_items', $cartItems);
}
```

### Génération du PDF avec QR Code

```php
// Générer le QR Code en local
$result = (new \Endroid\QrCode\Builder\Builder(
    writer: new \Endroid\QrCode\Writer\PngWriter(),
    data: $ticket['qr_code'] ?? (string) $ticket['id'],
    encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
    errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::High,
    size: 200,
    margin: 10
))->build();

$qrCodeBase64 = $result->getDataUri();

// Générer le PDF avec Dompdf
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
```

---

## 📱 Routes

| Route | Méthode | Description |
|-------|---------|-------------|
| `/cart` | GET | Afficher le panier |
| `/add-to-cart` | POST | Ajouter au panier |
| `/cart/remove/{cartKey}` | POST/DELETE | Retirer du panier |
| `/checkout/summary` | GET | Récapitulatif commande |
| `/checkout/payment` | GET | Page de paiement |
| `/checkout/process` | POST | Traiter le paiement |
| `/checkout/confirmation` | GET | Confirmation |
| `/my-tickets` | GET | Liste mes billets |
| `/my-tickets/{id}` | GET | Détails d'un billet |
| `/my-tickets/{id}/pdf` | GET | Télécharger PDF |

### API

| Route | Méthode | Description |
|-------|---------|-------------|
| `/api/tickets/cart` | GET | Récupérer le panier |
| `/api/tickets/cart/count` | GET | Nombre d'items |
| `/api/tickets/cart/sync` | POST | Synchroniser le panier |
| `/api/tickets/cart/load` | GET | Charger depuis BDD |
| `/api/tickets/{id}/transfer` | POST | Transférer un billet |
| `/api/tickets/{id}/validate` | POST | Valider un billet |

---

## 🎨 Éléments d'interface

### Page panier

| Élément | Description |
|---------|-------------|
| Liste items | Image, titre, quantités, prix unitaire, sous-total |
| Bouton supprimer | ❌ pour chaque item |
| Total | Montant total avec frais de service |
| Bouton payer | CTA "Procéder au paiement" |
| Panier vide | Message + lien vers événements |

### Page paiement

| Élément | Description |
|---------|-------------|
| Récapitulatif | Items, quantités, prix |
| Méthode paiement | Radio buttons (MVola, Orange Money, Airtel) |
| Formulaire | Nom, email, téléphone |
| CGU | Checkbox acceptation |
| Timer | Compte à rebours 15 minutes |
| Bouton valider | "Payer X MGA" |

### Page mes billets

| Élément | Description |
|---------|-------------|
| Filtres | Onglets (À venir, Passés, Annulés) |
| Cartes billets | Image, titre, date, lieu, statut |
| Badge catégorie | Business, Concert, Sport... avec couleur |
| Actions | Calendrier, PDF, Détails |

### PDF billet

| Élément | Description |
|---------|-------------|
| Header | Logo Aiolia + numéro billet |
| Image événement | Photo de l'événement |
| Informations | Titre, date, heure, lieu |
| QR Code | Code scannable (200x200px) |
| Instructions | Comment accéder à l'événement |
| Footer | Conditions et contacts |

---

## 🔗 Dépendances

- **TicketRepository** : Requêtes BDD billets
- **EventRepository** : Informations événements
- **CartSyncService** : Synchronisation panier
- **PaymentService** : Traitement des paiements
- **ActivityService** : Log des activités
- **Dompdf** : Génération PDF
- **Endroid/QrCode** : Génération QR codes

