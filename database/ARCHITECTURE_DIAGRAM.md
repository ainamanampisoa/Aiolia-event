# 🏗️ Diagrammes d'Architecture - Aiolia Event

Ce document présente les diagrammes visuels de l'architecture de la base de données et des flux de données.

---

## 📊 Schéma Relationnel Complet

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         AIOLIA EVENT - DATABASE                         │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────────┐
│     USERS        │◄────────┐
├──────────────────┤         │
│ • id (PK)        │         │
│ • email          │         │
│ • password_hash  │         │
│ • role           │         │
│ • oauth_provider │         │
└────────┬─────────┘         │
         │                   │
         │ 1:1               │ 1:N (organizer)
         v                   │
┌──────────────────┐         │
│     WALLET       │         │
├──────────────────┤         │
│ • id (PK)        │         │
│ • user_id (FK)   │         │
│ • loyalty_points │         │
│ • balance        │         │
└──────────────────┘         │
                             │
                             │
         ┌───────────────────┘
         │
         │
         v
┌──────────────────┐        ┌──────────────────┐
│     EVENTS       │◄───────┤ EVENT_CATEGORIES │
├──────────────────┤  N:1   ├──────────────────┤
│ • id (PK)        │        │ • id (PK)        │
│ • organizer_id   │        │ • name           │
│ • category_id    │        │ • slug           │
│ • title          │        └──────────────────┘
│ • location       │
│ • start_date     │
│ • status         │
└────────┬─────────┘
         │
         │ 1:N
         v
┌──────────────────┐        ┌──────────────────┐
│ TICKET_CATEGORIES│◄───────┤ DYNAMIC_PRICING_ │
├──────────────────┤  1:N   │ RULES            │
│ • id (PK)        │        ├──────────────────┤
│ • event_id (FK)  │        │ • id (PK)        │
│ • name           │        │ • ticket_cat_id  │
│ • price          │        │ • threshold_%    │
│ • quantity_total │        │ • price_mult     │
│ • quantity_sold  │        └──────────────────┘
└────────┬─────────┘
         │
         │ 1:N
         v
┌──────────────────┐        ┌──────────────────┐
│     TICKETS      │◄───────┤     ORDERS       │
├──────────────────┤  N:1   ├──────────────────┤
│ • id (PK)        │        │ • id (PK)        │
│ • category_id    │        │ • user_id (FK)   │
│ • order_id (FK)  │        │ • order_number   │
│ • qr_code_data   │        │ • total_amount   │
│ • status         │        │ • promo_code_id  │
│ • current_owner  │        │ • status         │
└──────────────────┘        └────────┬─────────┘
                                     │
                                     │ 1:N
                                     v
                            ┌──────────────────┐
                            │   ORDER_ITEMS    │
                            ├──────────────────┤
                            │ • id (PK)        │
                            │ • order_id (FK)  │
                            │ • ticket_cat_id  │
                            │ • quantity       │
                            │ • unit_price     │
                            └──────────────────┘

┌──────────────────┐        ┌──────────────────┐
│   PROMO_CODES    │◄───────┤ PROMO_CODE_USAGE │
├──────────────────┤  1:N   ├──────────────────┤
│ • id (PK)        │        │ • id (PK)        │
│ • code           │        │ • promo_code_id  │
│ • discount_type  │        │ • user_id (FK)   │
│ • discount_value │        │ • order_id (FK)  │
│ • max_uses       │        │ • used_at        │
└──────────────────┘        └──────────────────┘

┌──────────────────┐
│    PAYMENTS      │
├──────────────────┤
│ • id (PK)        │
│ • order_id (FK)  │
│ • method         │
│ • amount         │
│ • transaction_id │
│ • status         │
└──────────────────┘

┌──────────────────┐        ┌──────────────────┐
│   FAVORITES      │        │   CART           │
├──────────────────┤        ├──────────────────┤
│ • user_id (FK)   │        │ • id (PK)        │
│ • event_id (FK)  │        │ • user_id (FK)   │
└──────────────────┘        └────────┬─────────┘
                                     │
                                     │ 1:N
                                     v
                            ┌──────────────────┐
                            │   CART_ITEMS     │
                            ├──────────────────┤
                            │ • cart_id (FK)   │
                            │ • ticket_cat_id  │
                            │ • quantity       │
                            └──────────────────┘

┌──────────────────┐        ┌──────────────────┐
│  NOTIFICATIONS   │        │ NOTIFICATION_    │
│                  │        │ PREFERENCES      │
├──────────────────┤        ├──────────────────┤
│ • id (PK)        │        │ • user_id (FK)   │
│ • user_id (FK)   │        │ • email_*        │
│ • type           │        │ • push_*         │
│ • message        │        │ • sms_*          │
│ • channel        │        └──────────────────┘
│ • status         │
└──────────────────┘

┌──────────────────┐        ┌──────────────────┐
│    REVIEWS       │        │   REVIEW_VOTES   │
├──────────────────┤        ├──────────────────┤
│ • id (PK)        │        │ • review_id (FK) │
│ • event_id (FK)  │───────►│ • user_id (FK)   │
│ • user_id (FK)   │  1:N   │ • is_helpful     │
│ • rating         │        └──────────────────┘
│ • comment        │
└──────────────────┘

┌──────────────────┐
│ EVENT_STATISTICS │
├──────────────────┤
│ • event_id (FK)  │
│ • total_views    │
│ • total_sales    │
│ • total_revenue  │
│ • conversion_rate│
│ • avg_rating     │
└──────────────────┘

┌──────────────────┐
│ USER_STATISTICS  │
├──────────────────┤
│ • user_id (FK)   │
│ • total_events   │
│ • total_spent    │
│ • loyalty_tier   │
└──────────────────┘
```

---

## 🔄 Flux d'Achat de Billet

```
┌─────────┐
│ CLIENT  │
└────┬────┘
     │
     │ 1. Browse events
     v
┌──────────────┐
│   EVENTS     │
│   Table      │
└──────┬───────┘
       │
       │ 2. View event details
       v
┌──────────────┐
│ EVENT_VIEWS  │ (log view)
└──────────────┘
       │
       │ 3. Add to cart
       v
┌──────────────┐
│    CART      │
│  CART_ITEMS  │
└──────┬───────┘
       │
       │ 4. Reserve tickets (temp)
       v
┌──────────────────┐
│ TICKET_CATEGORIES│
│ quantity_reserved│
│      ++          │
└──────────────────┘
       │
       │ 5. Checkout
       v
┌──────────────┐
│   ORDERS     │ (status: pending)
│ ORDER_ITEMS  │
└──────┬───────┘
       │
       │ 6. Process payment
       v
┌──────────────┐
│  PAYMENTS    │ (Mobile Money)
└──────┬───────┘
       │
       │ 7. Payment success
       v
┌──────────────┐
│   ORDERS     │ (status: completed)
└──────┬───────┘
       │
       │ 8. Generate tickets
       v
┌──────────────┐
│   TICKETS    │ (with QR codes)
└──────┬───────┘
       │
       │ 9. Send notification
       v
┌──────────────┐
│NOTIFICATIONS │ (email + PDF)
└──────────────┘
       │
       │ 10. Update stats
       v
┌──────────────────┐
│ EVENT_STATISTICS │
│ USER_STATISTICS  │
└──────────────────┘
       │
       │ 11. Award loyalty points
       v
┌──────────────────┐
│     WALLET       │
│ loyalty_points++ │
└──────────────────┘
```

---

## 💰 Flux de Tarification Dynamique

```
┌──────────────────┐
│   Ticket vendu   │
└────────┬─────────┘
         │
         v
┌─────────────────────┐
│ TRIGGER:            │
│ after_ticket_insert │
└────────┬────────────┘
         │
         v
┌─────────────────────┐
│ TICKET_CATEGORIES   │
│ quantity_sold++     │
└────────┬────────────┘
         │
         │ Calculate percentage sold
         v
┌─────────────────────┐
│ % = (sold / total)  │
│     * 100           │
└────────┬────────────┘
         │
         │ Check pricing rules
         v
┌─────────────────────┐
│ DYNAMIC_PRICING_    │
│ RULES               │
│                     │
│ IF % >= 50:         │
│   price *= 1.20     │
│ IF % >= 75:         │
│   price *= 1.30     │
│ IF % >= 90:         │
│   price *= 1.50     │
└────────┬────────────┘
         │
         │ Update price
         v
┌─────────────────────┐
│ TICKET_CATEGORIES   │
│ price = new_price   │
└────────┬────────────┘
         │
         │ Log change
         v
┌─────────────────────┐
│ TICKET_PRICE_       │
│ HISTORY             │
│ • old_price         │
│ • new_price         │
│ • reason: "dynamic" │
└─────────────────────┘
```

---

## 🎁 Flux du Programme de Fidélité

```
┌──────────────────┐
│ Order completed  │
└────────┬─────────┘
         │
         v
┌─────────────────────────┐
│ TRIGGER:                │
│ after_order_loyalty_    │
│ points                  │
└────────┬────────────────┘
         │
         │ Calculate points
         │ (1 point / 1000 MGA)
         v
┌─────────────────────────┐
│ points = FLOOR(         │
│   total_amount / 1000   │
│ )                       │
└────────┬────────────────┘
         │
         │ Create transaction
         v
┌─────────────────────────┐
│ WALLET_TRANSACTIONS     │
│ • type: credit          │
│ • points: X             │
│ • reference: order_id   │
└────────┬────────────────┘
         │
         │ TRIGGER: after_wallet_transaction
         v
┌─────────────────────────┐
│ WALLET                  │
│ • loyalty_points += X   │
│ • total_earned += X     │
└────────┬────────────────┘
         │
         │ Check tier upgrade
         v
┌─────────────────────────┐
│ IF points >= 5000:      │
│   tier = 'platinum'     │
│ ELSE IF points >= 2000: │
│   tier = 'gold'         │
│ ELSE IF points >= 500:  │
│   tier = 'silver'       │
│ ELSE:                   │
│   tier = 'bronze'       │
└────────┬────────────────┘
         │
         │ Update user stats
         v
┌─────────────────────────┐
│ USER_STATISTICS         │
│ • loyalty_tier = tier   │
└─────────────────────────┘
```

---

## 🎮 Flux du Mini-Jeu "Ticket Chance"

```
┌──────────────┐
│ User plays   │
│ mini-game    │
└──────┬───────┘
       │
       │ 1. Check eligibility
       v
┌─────────────────────────┐
│ SELECT COUNT(*)         │
│ FROM game_participations│
│ WHERE user_id = ?       │
│   AND DATE(played_at)   │
│       = CURDATE()       │
└────────┬────────────────┘
       │
       │ 2. If allowed (< max_daily)
       v
┌─────────────────────────┐
│ GAME_SETTINGS           │
│ • Get probabilities     │
│   - discount: 30%       │
│   - free_ticket: 5%     │
│   - points: 50%         │
│   - nothing: 15%        │
└────────┬────────────────┘
       │
       │ 3. Random draw
       v
┌─────────────────────────┐
│ RAND() determines prize │
└────────┬────────────────┘
       │
       ├─────────────────────┐
       │                     │
       │ Prize: DISCOUNT     │ Prize: POINTS
       v                     v
┌─────────────────┐  ┌─────────────────┐
│ PROMO_CODES     │  │ WALLET_         │
│ • Generate code │  │ TRANSACTIONS    │
│ • 10% off       │  │ • Credit points │
└─────────────────┘  └─────────────────┘
       │                     │
       └──────────┬──────────┘
                  │
                  │ 4. Record participation
                  v
       ┌─────────────────────────┐
       │ GAME_PARTICIPATIONS     │
       │ • user_id               │
       │ • prize_type            │
       │ • prize_value           │
       │ • is_claimed: FALSE     │
       │ • expires_at: +7 days   │
       └─────────┬───────────────┘
                 │
                 │ 5. Notify user
                 v
       ┌─────────────────────────┐
       │ NOTIFICATIONS           │
       │ "Congratulations!"      │
       └─────────────────────────┘
```

---

## 📊 Flux de Check-in (Scan QR Code)

```
┌──────────────────┐
│ Organizer scans  │
│ QR code          │
└────────┬─────────┘
         │
         │ 1. Read QR code data
         v
┌──────────────────────┐
│ CALL checkin_ticket( │
│   qr_code_data,      │
│   scanner_user_id,   │
│   @success,          │
│   @message           │
│ )                    │
└────────┬─────────────┘
         │
         │ 2. Find ticket
         v
┌──────────────────────┐
│ SELECT * FROM tickets│
│ WHERE qr_code_data=? │
└────────┬─────────────┘
         │
         ├──────────────────────┐
         │                      │
         │ Found & Valid        │ Not Found / Invalid
         v                      v
┌──────────────────┐   ┌──────────────────┐
│ UPDATE tickets   │   │ RETURN:          │
│ SET:             │   │ success = FALSE  │
│ • status = 'used'│   │ message = 'Error'│
│ • check_in_at    │   └──────────────────┘
│ • check_in_by    │
└────────┬─────────┘
         │
         │ 3. Log audit
         v
┌──────────────────────┐
│ AUDIT_LOGS           │
│ • action: check_in   │
│ • entity: ticket     │
│ • user: scanner_id   │
└──────────────────────┘
         │
         │ 4. Update stats
         v
┌──────────────────────┐
│ EVENT_STATISTICS     │
│ • check_ins++        │
└──────────────────────┘
```

---

## 🔔 Flux de Notifications

```
┌──────────────────┐
│ Trigger Event    │
│ (order complete, │
│  reminder, etc.) │
└────────┬─────────┘
         │
         │ 1. Get user preferences
         v
┌──────────────────────────┐
│ NOTIFICATION_PREFERENCES │
│ • email_enabled?         │
│ • push_enabled?          │
│ • sms_enabled?           │
└────────┬─────────────────┘
         │
         │ 2. Create notification(s)
         ├──────────┬──────────┬─────────┐
         │          │          │         │
         v          v          v         v
    ┌───────┐  ┌───────┐  ┌───────┐  ┌───────┐
    │ EMAIL │  │ PUSH  │  │  SMS  │  │IN-APP │
    └───┬───┘  └───┬───┘  └───┬───┘  └───┬───┘
        │          │          │          │
        │          │          │          │
        └──────────┴──────────┴──────────┘
                       │
                       │ 3. Queue notifications
                       v
            ┌─────────────────────┐
            │ NOTIFICATIONS       │
            │ • status: pending   │
            │ • channel: email    │
            │ • created_at: NOW() │
            └──────────┬──────────┘
                       │
                       │ 4. Background worker processes
                       v
            ┌─────────────────────┐
            │ Send via provider   │
            │ (SMTP, FCM, SMS API)│
            └──────────┬──────────┘
                       │
                       │ 5. Update status
                       v
            ┌─────────────────────┐
            │ NOTIFICATIONS       │
            │ • status: sent      │
            │ • sent_at: NOW()    │
            └─────────────────────┘
```

---

## 🌐 Architecture Système Complète

```
┌─────────────────────────────────────────────────────────────┐
│                         CLIENT LAYER                        │
├─────────────────────────────────────────────────────────────┤
│  Web Browser    │   Mobile App   │   Admin Dashboard        │
│  (React/Vue)    │  (React Native)│   (React)                │
└────────┬─────────────────┬─────────────────┬────────────────┘
         │                 │                 │
         │                 │                 │
         v                 v                 v
┌─────────────────────────────────────────────────────────────┐
│                         CDN / CACHE                         │
├─────────────────────────────────────────────────────────────┤
│  CloudFlare / AWS CloudFront                                │
│  • Static assets (images, CSS, JS)                          │
│  • API response caching                                     │
└────────┬────────────────────────────────────────────────────┘
         │
         │ HTTPS
         v
┌─────────────────────────────────────────────────────────────┐
│                      LOAD BALANCER                          │
├─────────────────────────────────────────────────────────────┤
│  Nginx / AWS ELB                                            │
│  • SSL Termination                                          │
│  • Rate Limiting                                            │
│  • Request routing                                          │
└────────┬────────────────────────────────────────────────────┘
         │
         ├─────────────┬─────────────┬─────────────┐
         v             v             v             v
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ API SERVER 1 │ │ API SERVER 2 │ │ API SERVER 3 │ │ API SERVER N │
├──────────────┤ ├──────────────┤ ├──────────────┤ ├──────────────┤
│ Node.js/PHP  │ │ Node.js/PHP  │ │ Node.js/PHP  │ │ Node.js/PHP  │
│ • Auth       │ │ • Events     │ │ • Payments   │ │ • Notifications│
│ • JWT        │ │ • Tickets    │ │ • Orders     │ │ • Analytics  │
└──────┬───────┘ └──────┬───────┘ └──────┬───────┘ └──────┬───────┘
       │                │                │                │
       │                └────────┬───────┘                │
       │                         │                        │
       ├─────────────────────────┼────────────────────────┤
       │                         │                        │
       v                         v                        v
┌──────────────┐         ┌──────────────┐        ┌──────────────┐
│    REDIS     │         │   MYSQL      │        │ MESSAGE QUEUE│
│    CACHE     │         │   DATABASE   │        │  (RabbitMQ)  │
├──────────────┤         ├──────────────┤        ├──────────────┤
│ • Sessions   │         │ • Master     │        │ • Email jobs │
│ • Stats      │         │ • Read       │        │ • SMS jobs   │
│ • Cart       │         │   Replicas   │        │ • Push notif │
└──────────────┘         └──────┬───────┘        └──────┬───────┘
                                │                       │
                                │                       │
                                v                       v
                         ┌──────────────┐      ┌──────────────┐
                         │   BACKUP     │      │   WORKERS    │
                         │   SERVER     │      ├──────────────┤
                         ├──────────────┤      │ • Email      │
                         │ • Daily      │      │ • SMS        │
                         │ • Incremental│      │ • Push       │
                         │ • S3 Storage │      │ • Stats calc │
                         └──────────────┘      └──────────────┘
                                │
                                v
                         ┌──────────────┐
                         │  MONITORING  │
                         ├──────────────┤
                         │ • Grafana    │
                         │ • Prometheus │
                         │ • ELK Stack  │
                         └──────────────┘
```

---

## 📈 Scaling Strategy

```
┌─────────────────────────────────────────────────────────────┐
│                   SCALING EVOLUTION                         │
└─────────────────────────────────────────────────────────────┘

PHASE 1: MONOLITH (0-1K users)
┌──────────────┐
│  Single      │
│  Server      │
│  • API       │
│  • Database  │
│  • Cache     │
└──────────────┘

PHASE 2: VERTICAL SCALING (1K-10K users)
┌──────────────┐
│  Bigger      │
│  Server      │
│  • More RAM  │
│  • More CPU  │
│  • SSD       │
└──────────────┘

PHASE 3: HORIZONTAL SCALING (10K-100K users)
┌──────────┐  ┌──────────┐  ┌──────────┐
│ API 1    │  │ API 2    │  │ API 3    │
└────┬─────┘  └────┬─────┘  └────┬─────┘
     │             │             │
     └─────────────┼─────────────┘
                   │
        ┌──────────┴──────────┐
        │                     │
    ┌───▼───┐           ┌─────▼─────┐
    │ Cache │           │ DB Master │
    └───────┘           └─────┬─────┘
                              │
                    ┌─────────┼─────────┐
                    │         │         │
               ┌────▼───┐ ┌───▼────┐ ┌─▼──────┐
               │Replica1│ │Replica2│ │Replica3│
               └────────┘ └────────┘ └────────┘

PHASE 4: MICROSERVICES (100K+ users)
┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐
│  Auth      │  │  Events    │  │  Payments  │  │  Notify    │
│  Service   │  │  Service   │  │  Service   │  │  Service   │
└──────┬─────┘  └──────┬─────┘  └──────┬─────┘  └──────┬─────┘
       │               │               │               │
       └───────────────┼───────────────┼───────────────┘
                       │               │
                  ┌────▼────┐     ┌────▼────┐
                  │  Cache  │     │ Message │
                  │ (Redis) │     │ Queue   │
                  └─────────┘     └─────────┘
                       │
            ┌──────────┼──────────┐
            │          │          │
        ┌───▼──┐   ┌───▼──┐   ┌──▼───┐
        │ DB 1 │   │ DB 2 │   │ DB 3 │
        │Users │   │Events│   │Orders│
        └──────┘   └──────┘   └──────┘
```

---

**Ce document est vivant et sera mis à jour au fur et à mesure de l'évolution du projet.**

**Dernière mise à jour** : Octobre 2025

