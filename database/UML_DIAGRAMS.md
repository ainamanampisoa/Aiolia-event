# 📐 Diagrammes UML - Aiolia Event

Ce document présente les diagrammes UML (Unified Modeling Language) de la base de données et des processus métier d'Aiolia Event.

---

## 📋 Table des Matières

1. [Diagramme de Classes UML](#diagramme-de-classes-uml)
2. [Diagramme de Cas d'Utilisation](#diagramme-de-cas-dutilisation)
3. [Diagrammes de Séquence](#diagrammes-de-séquence)
4. [Diagramme d'Activité](#diagramme-dactivité)
5. [Diagramme d'États](#diagramme-détats)

---

## 🎯 Mon Avis sur l'UML

### ✅ Avantages de l'UML pour ce Projet

1. **Communication Universelle** 
   - Langage visuel compris par tous (dev, PO, clients)
   - Facilite les discussions d'architecture
   - Documentation auto-explicative

2. **Conception Avant Implémentation**
   - Identifie les problèmes en amont
   - Réduit les erreurs de conception
   - Gain de temps sur le long terme

3. **Maintenance Facilitée**
   - Vue d'ensemble du système
   - Onboarding rapide des nouveaux dev
   - Évolutions mieux anticipées

4. **Standardisation**
   - Notations standardisées ISO
   - Outils multiples disponibles
   - Intégration CI/CD possible

### ⚠️ Limitations

1. **Peut devenir complexe** sur de très gros projets
2. **Nécessite une mise à jour régulière** (risque de désynchronisation avec le code)
3. **Courbe d'apprentissage** pour les notations avancées

### 💡 Mon Recommandation

Pour **Aiolia Event**, je recommande :
- ✅ **Diagramme de classes** : Indispensable pour la BDD
- ✅ **Diagramme de cas d'utilisation** : Excellent pour le product
- ✅ **Diagrammes de séquence** : Pour les flux critiques (paiement, check-in)
- ⚠️ **Autres diagrammes** : À utiliser au cas par cas

---

## 📊 1. Diagramme de Classes UML

### Vue d'Ensemble Simplifiée

```
┌─────────────────────────────────────────────────────────────────┐
│                  DIAGRAMME DE CLASSES UML                       │
│                      Aiolia Event                               │
└─────────────────────────────────────────────────────────────────┘

┌───────────────────────┐
│       User            │
├───────────────────────┤
│ - id: BIGINT          │
│ - email: VARCHAR      │
│ - password_hash: STR  │
│ - role: ENUM          │
│ - first_name: VARCHAR │
│ - last_name: VARCHAR  │
│ - phone: VARCHAR      │
│ - photo_url: VARCHAR  │
│ - is_active: BOOLEAN  │
├───────────────────────┤
│ + login()             │
│ + logout()            │
│ + updateProfile()     │
│ + changePassword()    │
└───────┬───────────────┘
        │
        │ 1
        │
        │ has
        ▼ 1
┌───────────────────────┐
│      Wallet           │
├───────────────────────┤
│ - id: BIGINT          │
│ - user_id: BIGINT     │◄─────┐
│ - loyalty_points: INT │      │
│ - balance: DECIMAL    │      │
├───────────────────────┤      │
│ + addPoints()         │      │
│ + deductPoints()      │      │
│ + getBalance()        │      │
└───────────────────────┘      │
        │                      │
        │ 1                    │
        │                      │
        │ contains             │
        ▼ *                    │
┌───────────────────────┐      │
│ WalletTransaction     │      │
├───────────────────────┤      │
│ - id: BIGINT          │      │
│ - wallet_id: BIGINT   │      │
│ - type: ENUM          │      │
│ - amount: DECIMAL     │      │
│ - points: INT         │      │
├───────────────────────┤      │
│ + create()            │      │
└───────────────────────┘      │
                               │
┌───────────────────────┐      │
│       Event           │      │
├───────────────────────┤      │
│ - id: BIGINT          │      │
│ - organizer_id: BIGINT│──────┘
│ - category_id: INT    │◄─────────┐
│ - title: VARCHAR      │          │
│ - description: TEXT   │          │
│ - location: VARCHAR   │          │
│ - start_date: DATETIME│          │
│ - end_date: DATETIME  │          │
│ - status: ENUM        │          │
│ - is_featured: BOOL   │          │
├───────────────────────┤          │
│ + create()            │          │
│ + update()            │          │
│ + publish()           │          │
│ + cancel()            │          │
│ + getStats()          │          │
└───────┬───────────────┘          │
        │                          │
        │ 1                        │ *
        │                          │
        │ has        ┌─────────────┴─────────┐
        ▼ *         │   EventCategory       │
┌───────────────────────┐├───────────────────────┤
│  TicketCategory   │││ - id: INT             │
├───────────────────────┤│ - name: VARCHAR       │
│ - id: BIGINT          ││ - slug: VARCHAR       │
│ - event_id: BIGINT    ││ - description: TEXT   │
│ - name: VARCHAR       │├───────────────────────┤
│ - price: DECIMAL      ││ + create()            │
│ - quantity_total: INT │└───────────────────────┘
│ - quantity_sold: INT  │
│ - quantity_reserved:INT│
├───────────────────────┤
│ + checkAvailability() │
│ + reserve()           │
│ + release()           │
│ + applyDynamicPrice() │
└───────┬───────────────┘
        │
        │ 1
        │
        │ generates
        ▼ *
┌───────────────────────┐
│      Ticket           │
├───────────────────────┤
│ - id: BIGINT          │
│ - category_id: BIGINT │
│ - order_id: BIGINT    │◄────────┐
│ - user_id: BIGINT     │         │
│ - ticket_number: STR  │         │
│ - qr_code_data: STR   │         │
│ - status: ENUM        │         │
│ - current_owner: BIGINT│        │
├───────────────────────┤         │
│ + generate()          │         │
│ + checkIn()           │         │
│ + transfer()          │         │
│ + cancel()            │         │
└───────────────────────┘         │
                                  │
┌───────────────────────┐         │
│       Order           │         │
├───────────────────────┤         │
│ - id: BIGINT          │─────────┘
│ - user_id: BIGINT     │
│ - order_number: STR   │
│ - subtotal: DECIMAL   │
│ - discount: DECIMAL   │
│ - total_amount: DEC   │
│ - promo_code_id: BIG  │◄────────┐
│ - status: ENUM        │         │
│ - payment_status: ENUM│         │
├───────────────────────┤         │
│ + create()            │         │
│ + addItem()           │         │
│ + applyPromo()        │         │
│ + processPayment()    │         │
│ + complete()          │         │
│ + cancel()            │         │
└───────┬───────────────┘         │
        │                         │
        │ 1                       │
        │                         │
        │ contains                │
        ▼ *                       │
┌───────────────────────┐         │
│     OrderItem         │         │
├───────────────────────┤         │
│ - id: BIGINT          │         │
│ - order_id: BIGINT    │         │
│ - ticket_cat_id: BIG  │         │
│ - quantity: INT       │         │
│ - unit_price: DECIMAL │         │
│ - total_price: DECIMAL│         │
├───────────────────────┤         │
│ + create()            │         │
└───────────────────────┘         │
        │                         │
        │ 1                       │
        │                         │
        │ paid by                 │
        ▼ 1                       │
┌───────────────────────┐         │
│      Payment          │         │
├───────────────────────┤         │
│ - id: BIGINT          │         │
│ - order_id: BIGINT    │         │
│ - method: ENUM        │         │
│ - amount: DECIMAL     │         │
│ - transaction_id: STR │         │
│ - status: ENUM        │         │
├───────────────────────┤         │
│ + process()           │         │
│ + verify()            │         │
│ + refund()            │         │
└───────────────────────┘         │
                                  │
┌───────────────────────┐         │
│     PromoCode         │─────────┘
├───────────────────────┤
│ - id: BIGINT          │
│ - code: VARCHAR       │
│ - discount_type: ENUM │
│ - discount_value: DEC │
│ - max_uses: INT       │
│ - current_uses: INT   │
│ - valid_from: DATETIME│
│ - valid_until: DATETIME│
├───────────────────────┤
│ + validate()          │
│ + apply()             │
│ + incrementUsage()    │
└───────────────────────┘

┌───────────────────────┐
│       Cart            │
├───────────────────────┤
│ - id: BIGINT          │
│ - user_id: BIGINT     │
│ - expires_at: DATETIME│
├───────────────────────┤
│ + addItem()           │
│ + removeItem()        │
│ + clear()             │
│ + checkout()          │
└───────┬───────────────┘
        │
        │ 1
        │
        │ contains
        ▼ *
┌───────────────────────┐
│     CartItem          │
├───────────────────────┤
│ - id: BIGINT          │
│ - cart_id: BIGINT     │
│ - ticket_cat_id: BIG  │
│ - quantity: INT       │
├───────────────────────┤
│ + create()            │
│ + updateQuantity()    │
│ + remove()            │
└───────────────────────┘

┌───────────────────────┐
│    Notification       │
├───────────────────────┤
│ - id: BIGINT          │
│ - user_id: BIGINT     │
│ - type: ENUM          │
│ - title: VARCHAR      │
│ - message: TEXT       │
│ - channel: ENUM       │
│ - status: ENUM        │
├───────────────────────┤
│ + send()              │
│ + markAsRead()        │
│ + markAsSent()        │
└───────────────────────┘

┌───────────────────────┐
│       Review          │
├───────────────────────┤
│ - id: BIGINT          │
│ - event_id: BIGINT    │
│ - user_id: BIGINT     │
│ - rating: INT         │
│ - comment: TEXT       │
│ - is_published: BOOL  │
├───────────────────────┤
│ + create()            │
│ + update()            │
│ + delete()            │
│ + publish()           │
└───────────────────────┘
```

### Relations UML

```
User ──────┬─── "1" organizes "0..*" ──────► Event
           │
           ├─── "1" owns "1" ──────────────► Wallet
           │
           ├─── "1" has "0..*" ────────────► Order
           │
           ├─── "1" has "0..*" ────────────► Ticket
           │
           ├─── "1" has "0..1" ────────────► Cart
           │
           └─── "1" writes "0..*" ─────────► Review

Event ─────┬─── "1" belongs to "1" ────────► EventCategory
           │
           ├─── "1" has "1..*" ────────────► TicketCategory
           │
           └─── "1" has "0..*" ────────────► Review

TicketCategory ─── "1" generates "0..*" ───► Ticket

Order ─────┬─── "1" contains "1..*" ────────► OrderItem
           │
           ├─── "1" paid by "0..1" ────────► Payment
           │
           └─── "1" uses "0..1" ───────────► PromoCode

Cart ──────── "1" contains "0..*" ─────────► CartItem

Wallet ────── "1" has "0..*" ──────────────► WalletTransaction
```

---

## 👤 2. Diagramme de Cas d'Utilisation

```
┌────────────────────────────────────────────────────────────────┐
│          DIAGRAMME DE CAS D'UTILISATION - Aiolia Event         │
└────────────────────────────────────────────────────────────────┘

┌─────────────┐
│             │
│ Utilisateur │
│   (User)    │
│             │
└──────┬──────┘
       │
       ├──────────────► (Rechercher événements)
       │                        │
       │                        │ <<include>>
       │                        ▼
       │                 (Filtrer résultats)
       │
       ├──────────────► (Voir détails événement)
       │
       ├──────────────► (Ajouter au panier)
       │                        │
       │                        │ <<extend>>
       │                        ▼
       │                 (Sauvegarder pour plus tard)
       │
       ├──────────────► (Passer commande)
       │                        │
       │                        │ <<include>>
       │                        ├────► (Valider disponibilité)
       │                        │
       │                        │ <<include>>
       │                        ├────► (Appliquer code promo)
       │                        │
       │                        │ <<include>>
       │                        ▼
       │                 (Effectuer paiement)
       │                        │
       │                        │ <<extend>>
       │                        ▼
       │                 (Choisir Mobile Money)
       │
       ├──────────────► (Consulter mes billets)
       │
       ├──────────────► (Télécharger billet PDF)
       │
       ├──────────────► (Transférer billet)
       │
       ├──────────────► (Consulter historique)
       │
       ├──────────────► (Gérer profil)
       │                        │
       │                        │ <<include>>
       │                        ▼
       │                 (Voir statistiques perso)
       │
       ├──────────────► (Consulter portefeuille)
       │                        │
       │                        │ <<extend>>
       │                        ▼
       │                 (Utiliser points fidélité)
       │
       ├──────────────► (Jouer mini-jeu)
       │
       ├──────────────► (Parrainer ami)
       │
       ├──────────────► (Laisser avis)
       │
       └──────────────► (Ajouter aux favoris)


┌─────────────┐
│             │
│Organisateur │
│ (Organizer) │
│             │
└──────┬──────┘
       │
       ├──────────────► (Créer événement)
       │                        │
       │                        │ <<include>>
       │                        ├────► (Ajouter médias)
       │                        │
       │                        │ <<include>>
       │                        ▼
       │                 (Configurer billets)
       │
       ├──────────────► (Modifier événement)
       │
       ├──────────────► (Publier événement)
       │
       ├──────────────► (Gérer équipe)
       │                        │
       │                        │ <<include>>
       │                        ▼
       │                 (Définir permissions)
       │
       ├──────────────► (Configurer tarification dynamique)
       │
       ├──────────────► (Créer codes promo)
       │
       ├──────────────► (Scanner billets)
       │                        │
       │                        │ <<include>>
       │                        ▼
       │                 (Valider QR code)
       │
       ├──────────────► (Consulter dashboard)
       │                        │
       │                        │ <<include>>
       │                        ├────► (Voir ventes)
       │                        │
       │                        │ <<include>>
       │                        ▼
       │                 (Analyser statistiques)
       │
       ├──────────────► (Générer rapports)
       │                        │
       │                        │ <<extend>>
       │                        ├────► (Export CSV)
       │                        │
       │                        │ <<extend>>
       │                        ▼
       │                 (Export PDF)
       │
       ├──────────────► (Gérer liste d'attente)
       │
       └──────────────► (Envoyer notifications)


┌─────────────┐
│             │
│   Système   │
│   (System)  │
│             │
└──────┬──────┘
       │
       ├──────────────► (Calculer statistiques)
       │
       ├──────────────► (Appliquer tarification dynamique)
       │
       ├──────────────► (Envoyer rappels événement)
       │
       ├──────────────► (Nettoyer paniers expirés)
       │
       ├──────────────► (Attribuer points fidélité)
       │
       ├──────────────► (Archiver logs)
       │
       └──────────────► (Notifier liste d'attente)


┌─────────────┐
│             │
│ Fournisseur │
│  Paiement   │
│  (External) │
│             │
└──────┬──────┘
       │
       ├──────────────► (Valider transaction)
       │
       ├──────────────► (Envoyer callback)
       │
       └──────────────► (Rembourser)
```

---

## 🔄 3. Diagrammes de Séquence

### 3.1 Achat de Billet

```
┌─────────────────────────────────────────────────────────────────┐
│       DIAGRAMME DE SÉQUENCE - Achat de Billet                   │
└─────────────────────────────────────────────────────────────────┘

User    Frontend    API      Database    Payment     Email
 │         │         │           │        Gateway    Service
 │         │         │           │           │          │
 │ Browse  │         │           │           │          │
 │ Events  │         │           │           │          │
 ├────────►│         │           │           │          │
 │         │ GET /events        │           │          │
 │         ├────────►│           │           │          │
 │         │         │ SELECT    │           │          │
 │         │         ├──────────►│           │          │
 │         │         │ Events ◄──┤           │          │
 │         │ Events  │           │           │          │
 │         │◄────────┤           │           │          │
 │ Display │         │           │           │          │
 │◄────────┤         │           │           │          │
 │         │         │           │           │          │
 │ Add to  │         │           │           │          │
 │ Cart    │         │           │           │          │
 ├────────►│         │           │           │          │
 │         │ POST /cart         │           │          │
 │         ├────────►│           │           │          │
 │         │         │ INSERT    │           │          │
 │         │         │ cart_items│           │          │
 │         │         ├──────────►│           │          │
 │         │         │ UPDATE    │           │          │
 │         │         │ quantity_ │           │          │
 │         │         │ reserved  │           │          │
 │         │         ├──────────►│           │          │
 │         │         │ OK ◄──────┤           │          │
 │         │ Success │           │           │          │
 │         │◄────────┤           │           │          │
 │         │         │           │           │          │
 │ Checkout│         │           │           │          │
 ├────────►│         │           │           │          │
 │         │ POST /orders       │           │          │
 │         ├────────►│           │           │          │
 │         │         │ BEGIN TX  │           │          │
 │         │         ├──────────►│           │          │
 │         │         │           │           │          │
 │         │         │ Check     │           │          │
 │         │         │ available │           │          │
 │         │         ├──────────►│           │          │
 │         │         │ OK ◄──────┤           │          │
 │         │         │           │           │          │
 │         │         │ INSERT    │           │          │
 │         │         │ order     │           │          │
 │         │         ├──────────►│           │          │
 │         │         │ order_id◄─┤           │          │
 │         │         │           │           │          │
 │         │         │ Process Payment       │          │
 │         │         ├──────────────────────►│          │
 │         │         │           │ Mobile    │          │
 │         │         │           │ Money API │          │
 │         │         │           │◄──────────┤          │
 │         │         │           │           │          │
 │         │         │ Payment Success       │          │
 │         │         │◄──────────────────────┤          │
 │         │         │           │           │          │
 │         │         │ UPDATE    │           │          │
 │         │         │ order     │           │          │
 │         │         │ status    │           │          │
 │         │         ├──────────►│           │          │
 │         │         │           │           │          │
 │         │         │ Generate  │           │          │
 │         │         │ Tickets   │           │          │
 │         │         ├──────────►│           │          │
 │         │         │ (with QR) │           │          │
 │         │         │ tickets◄──┤           │          │
 │         │         │           │           │          │
 │         │         │ COMMIT TX │           │          │
 │         │         ├──────────►│           │          │
 │         │         │           │           │          │
 │         │         │ Send Email            │          │
 │         │         ├────────────────────────────────►│
 │         │         │           │           │ (PDF+QR) │
 │         │         │           │           │          │
 │         │ Order   │           │           │          │
 │         │ Created │           │           │          │
 │         │◄────────┤           │           │          │
 │ Success │         │           │           │          │
 │◄────────┤         │           │           │          │
 │         │         │           │           │          │
```

### 3.2 Check-in avec QR Code

```
┌─────────────────────────────────────────────────────────────────┐
│       DIAGRAMME DE SÉQUENCE - Check-in QR Code                  │
└─────────────────────────────────────────────────────────────────┘

Organizer  Scanner App    API      Database    Audit
   │           │           │           │          │
   │ Scan QR   │           │           │          │
   ├──────────►│           │           │          │
   │           │ POST /checkin        │          │
   │           ├──────────►│           │          │
   │           │  {qr_code}│           │          │
   │           │           │           │          │
   │           │           │ SELECT    │          │
   │           │           │ ticket    │          │
   │           │           ├──────────►│          │
   │           │           │ ticket ◄──┤          │
   │           │           │           │          │
   │           │           │ IF valid: │          │
   │           │           │ UPDATE    │          │
   │           │           │ status    │          │
   │           │           ├──────────►│          │
   │           │           │ OK ◄──────┤          │
   │           │           │           │          │
   │           │           │ INSERT audit         │
   │           │           ├─────────────────────►│
   │           │           │           │ log ◄────┤
   │           │           │           │          │
   │           │ Success   │           │          │
   │           │◄──────────┤           │          │
   │ ✅ Valid  │           │           │          │
   │◄──────────┤           │           │          │
   │           │           │           │          │
```

### 3.3 Tarification Dynamique

```
┌─────────────────────────────────────────────────────────────────┐
│     DIAGRAMME DE SÉQUENCE - Tarification Dynamique              │
└─────────────────────────────────────────────────────────────────┘

Trigger     Database    Pricing Engine
  │            │              │
  │ Ticket     │              │
  │ Sold       │              │
  ├───────────►│              │
  │            │              │
  │            │ UPDATE       │
  │            │ quantity_sold│
  │            │ ++           │
  │            │              │
  │            │ Calculate %  │
  │            │ sold         │
  │            │              │
  │            │ IF % >= threshold
  │            ├─────────────►│
  │            │              │
  │            │ Calculate new│
  │            │ price        │
  │            │◄─────────────┤
  │            │              │
  │            │ UPDATE price │
  │            │              │
  │            │ INSERT       │
  │            │ price_history│
  │            │              │
  │ Price      │              │
  │ Updated    │              │
  │◄───────────┤              │
  │            │              │
```

---

## 🔄 4. Diagramme d'Activité

### Processus d'Achat Complet

```
┌─────────────────────────────────────────────────────────────────┐
│          DIAGRAMME D'ACTIVITÉ - Achat de Billet                 │
└─────────────────────────────────────────────────────────────────┘

                    ●  (Début)
                    │
                    ▼
          ┌──────────────────┐
          │ Rechercher       │
          │ Événement        │
          └─────────┬────────┘
                    │
                    ▼
          ┌──────────────────┐
          │ Sélectionner     │
          │ Événement        │
          └─────────┬────────┘
                    │
                    ▼
          ┌──────────────────┐
          │ Choisir          │
          │ Catégorie Billet │
          └─────────┬────────┘
                    │
                    ▼
          ┌──────────────────┐
          │ Vérifier         │
          │ Disponibilité    │
          └─────────┬────────┘
                    │
            ◇───────┴───────◇
            │               │
     [Non disponible]  [Disponible]
            │               │
            ▼               ▼
    ┌──────────────┐  ┌──────────────┐
    │ S'inscrire   │  │ Ajouter au   │
    │ Liste        │  │ Panier       │
    │ d'Attente    │  └──────┬───────┘
    └──────┬───────┘         │
           │                 ▼
           │         ◇───────┴───────◇
           │         │               │
           │    [Continuer]    [Commander]
           │         │               │
           │         ▼               ▼
           │  ┌──────────────┐  ┌──────────────┐
           │  │ Retour à la  │  │ Récapitulatif│
           │  │ Recherche    │  │ Commande     │
           │  └──────────────┘  └──────┬───────┘
           │                           │
           │                           ▼
           │                  ┌──────────────────┐
           │                  │ Saisir Code      │
           │                  │ Promo (optionnel)│
           │                  └────────┬─────────┘
           │                           │
           │                           ▼
           │                  ┌──────────────────┐
           │                  │ Choisir Méthode  │
           │                  │ de Paiement      │
           │                  └────────┬─────────┘
           │                           │
           │                    ◇──────┴──────◇
           │                    │             │
           │               [Mobile]      [Card]
           │                    │             │
           │                    ▼             │
           │           ┌──────────────────┐   │
           │           │ Orange Money     │   │
           │           │ Airtel Money     │   │
           │           │ Telma Money      │   │
           │           └─────────┬────────┘   │
           │                     │            │
           │                     └────┬───────┘
           │                          │
           │                          ▼
           │                 ┌──────────────────┐
           │                 │ Traiter Paiement │
           │                 └────────┬─────────┘
           │                          │
           │                  ◇───────┴───────◇
           │                  │               │
           │            [Échec]         [Succès]
           │                  │               │
           │                  ▼               ▼
           │         ┌──────────────┐  ┌──────────────┐
           │         │ Réessayer ou │  │ Générer      │
           │         │ Annuler      │  │ Billets      │
           │         └──────────────┘  └──────┬───────┘
           │                                  │
           │                                  ▼
           │                          ┌──────────────┐
           │                          │ Envoyer      │
           │                          │ Email + PDF  │
           │                          └──────┬───────┘
           │                                 │
           │                                 ▼
           │                          ┌──────────────┐
           │                          │ Attribuer    │
           │                          │ Points       │
           │                          │ Fidélité     │
           │                          └──────┬───────┘
           │                                 │
           └─────────────────────────────────┘
                                            │
                                            ▼
                                            ● (Fin)
```

---

## 📊 5. Diagramme d'États

### États d'un Billet

```
┌─────────────────────────────────────────────────────────────────┐
│            DIAGRAMME D'ÉTATS - Cycle de Vie Billet              │
└─────────────────────────────────────────────────────────────────┘

                        ●  (Création)
                        │
                        ▼
                  ┌───────────┐
                  │   VALID   │◄────────┐
                  │           │         │
                  └─────┬─────┘         │
                        │               │
          ┌─────────────┼─────────────┐ │
          │             │             │ │
          │ [transfer]  │ [check_in]  │ │ [revert]
          │             │             │ │
          ▼             ▼             ▼ │
    ┌───────────┐  ┌───────────┐  ┌───────────┐
    │TRANSFERRED│  │   USED    │  │ CANCELLED │
    │           │  │           │  │           │
    └─────┬─────┘  └───────────┘  └─────┬─────┘
          │                              │
          │ [accept]                     │ [refund]
          │                              │
          ▼                              ▼
    ┌───────────┐                  ┌───────────┐
    │  ACTIVE   │                  │ REFUNDED  │
    │ (new owner)│                  │           │
    └───────────┘                  └───────────┘
          │
          └──────────────────────────────┘
```

### États d'une Commande

```
┌─────────────────────────────────────────────────────────────────┐
│          DIAGRAMME D'ÉTATS - Cycle de Vie Commande              │
└─────────────────────────────────────────────────────────────────┘

                    ●  (Création)
                    │
                    ▼
              ┌───────────┐
              │  PENDING  │
              │           │
              └─────┬─────┘
                    │
        ┌───────────┼───────────┐
        │           │           │
        │ [pay]     │ [timeout] │ [cancel]
        │           │           │
        ▼           ▼           ▼
  ┌───────────┐ ┌───────────┐ ┌───────────┐
  │PROCESSING │ │  EXPIRED  │ │ CANCELLED │
  │           │ │           │ │           │
  └─────┬─────┘ └───────────┘ └───────────┘
        │
        │
   ◇────┴────◇
   │         │
[fail]    [success]
   │         │
   ▼         ▼
┌───────┐ ┌───────────┐
│FAILED │ │ COMPLETED │
│       │ │           │
└───────┘ └─────┬─────┘
              │
              │ [refund_request]
              │
              ▼
          ┌───────────┐
          │ REFUNDED  │
          │           │
          └───────────┘
```

---

## 🎯 Conclusion sur l'UML

### ✅ Avantages Démontrés

1. **Clarté de la Conception**
   - Les diagrammes UML rendent la structure du système immédiatement compréhensible
   - Facilite la communication entre équipes (dev, PO, clients)

2. **Documentation Vivante**
   - Les diagrammes servent de documentation technique
   - Plus facile à maintenir qu'un long document texte

3. **Détection Précoce des Problèmes**
   - Visualiser les flux permet d'identifier les failles
   - Optimiser avant l'implémentation = gain de temps

4. **Onboarding Rapide**
   - Un nouveau développeur comprend le système en 30 minutes avec ces diagrammes
   - Sans UML, il faudrait des jours/semaines

### 🛠️ Outils Recommandés

**Pour créer des UML :**
- **Draw.io / Diagrams.net** (gratuit, en ligne)
- **PlantUML** (code → diagramme, idéal pour versioning)
- **Lucidchart** (payant, très complet)
- **Visual Paradigm** (professionnel)
- **StarUML** (desktop, open-source)

**Pour générer depuis la BDD :**
- **MySQL Workbench** (reverse engineering)
- **DBeaver** (génère des ERD)
- **SchemaSpy** (doc automatique avec UML)

### 📚 Standards UML Utilisés

- **UML 2.5** (dernière version standard)
- **Notation OMG** (Object Management Group)
- **Conventions** :
  - Noms de classes en PascalCase
  - Attributs en camelCase
  - Visibilité : `-` private, `+` public, `#` protected
  - Relations : association, composition, agrégation, héritage

---

## 🚀 Utilisation Pratique

### Pour les Développeurs

```bash
# Utilisez PlantUML pour générer des diagrammes depuis du code
@startuml
class User {
  - id: BIGINT
  - email: VARCHAR
  + login()
}
class Event {
  - id: BIGINT
  - title: VARCHAR
}
User "1" -- "*" Event : organizes
@enduml
```

### Pour les Architectes

- ✅ Utiliser les diagrammes de classes pour valider la structure
- ✅ Diagrammes de séquence pour les flux critiques
- ✅ Diagrammes d'activité pour les processus métier complexes

### Pour les Product Owners

- ✅ Diagramme de cas d'utilisation = User Stories visuelles
- ✅ Validation des fonctionnalités avec les stakeholders
- ✅ Priorisation basée sur les dépendances visibles

---

**L'UML n'est pas une perte de temps, c'est un investissement qui se rentabilise dès la phase de développement et tout au long de la maintenance du projet.**

---

**Dernière mise à jour** : Octobre 2025  
**Version** : 1.0.0  
**Standard** : UML 2.5

