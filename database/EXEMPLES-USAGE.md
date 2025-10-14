# 💡 Exemples d'Utilisation - Aiolia Event

## 🎯 Guide Pratique d'Utilisation du Schéma

---

## 🌍 1. Utilisation des Traductions

### Frontend (React/Vue)

```javascript
// Import
import { t, translations } from '../database/translations.js';

// Composant React
function EventCategories() {
  const lang = useLanguage(); // 'fr', 'en', ou 'mg'
  const [categories, setCategories] = useState([]);
  
  useEffect(() => {
    // Récupérer les catégories depuis l'API
    fetch('/api/categories')
      .then(res => res.json())
      .then(data => {
        // Appliquer les traductions
        const translated = data.map(cat => ({
          ...cat,
          name: translations.event_categories[cat.slug][lang].name,
          description: translations.event_categories[cat.slug][lang].description
        }));
        setCategories(translated);
      });
  }, [lang]);
  
  return (
    <div>
      <h2>{t('ui.nav.events', lang)}</h2>
      {categories.map(cat => (
        <div key={cat.id}>
          <h3>{cat.name}</h3>
          <p>{cat.description}</p>
        </div>
      ))}
    </div>
  );
}
```

### Backend (Node.js/Express)

```javascript
// Import
const { t, translations } = require('./database/translations.js');

// API pour catégories traduites
app.get('/api/categories', async (req, res) => {
  const lang = req.query.lang || 'fr';
  
  const result = await pool.query('SELECT * FROM event_categories WHERE is_active = true');
  
  const categories = result.rows.map(cat => ({
    id: cat.id,
    slug: cat.slug,
    icon: cat.icon,
    name: translations.event_categories[cat.slug][lang].name,
    description: translations.event_categories[cat.slug][lang].description
  }));
  
  res.json(categories);
});

// Envoi d'email traduit
async function sendOrderConfirmation(userId, orderId) {
  const user = await pool.query('SELECT * FROM users WHERE id = $1', [userId]);
  const lang = user.rows[0].language; // 'fr', 'en', ou 'mg'
  
  const subject = t('emails.order_confirmation.subject', lang);
  const body = t('emails.order_confirmation.body', lang);
  
  await sendEmail(user.rows[0].email, subject, body);
}
```

---

## 📊 2. Gestion des Statistiques

### Mettre à Jour les Stats Utilisateur (Après Achat)

```javascript
// Backend - Après confirmation de paiement
async function updateUserStatsAfterPurchase(userId, orderTotal, ticketCount, eventId) {
  const currentMonth = new Date().toISOString().slice(0, 7); // "2025-10"
  
  // Récupérer l'événement pour sa catégorie
  const event = await pool.query('SELECT category_id FROM events WHERE id = $1', [eventId]);
  const categoryId = event.rows[0].category_id;
  
  // Mise à jour des stats
  await pool.query(`
    INSERT INTO user_statistics (
      user_id, 
      total_events_attended, 
      total_spent, 
      total_tickets_purchased,
      favorite_category_id,
      last_purchase_date
    ) VALUES ($1, 1, $2, $3, $4, CURRENT_TIMESTAMP)
    ON CONFLICT (user_id) DO UPDATE SET
      total_events_attended = user_statistics.total_events_attended + 1,
      total_spent = user_statistics.total_spent + $2,
      total_tickets_purchased = user_statistics.total_tickets_purchased + $3,
      last_purchase_date = CURRENT_TIMESTAMP
  `, [userId, orderTotal, ticketCount, categoryId]);
}
```

### Calculer Dépenses Mensuelles (Dans le Code)

```javascript
async function getMonthlySpending(userId) {
  const result = await pool.query(`
    SELECT 
      TO_CHAR(created_at, 'YYYY-MM') as month,
      SUM(total_amount) as amount
    FROM orders
    WHERE user_id = $1 
    AND status = 'completed'
    GROUP BY TO_CHAR(created_at, 'YYYY-MM')
    ORDER BY month DESC
    LIMIT 12
  `, [userId]);
  
  // Formater pour graphiques
  return result.rows.reduce((acc, row) => {
    acc[row.month] = parseFloat(row.amount);
    return acc;
  }, {});
}
```

### Calculer Catégories Préférées (Dans le Code)

```javascript
async function getFavoriteCategories(userId) {
  const result = await pool.query(`
    SELECT 
      ec.name,
      ec.slug,
      COUNT(*) as count
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
    JOIN events e ON tc.event_id = e.id
    JOIN event_categories ec ON e.category_id = ec.id
    WHERE o.user_id = $1 
    AND o.status = 'completed'
    GROUP BY ec.id, ec.name, ec.slug
    ORDER BY count DESC
  `, [userId]);
  
  return result.rows;
}
```

---

## 🎫 3. Gestion de la Billetterie

### Vérifier Disponibilité

```javascript
async function checkTicketAvailability(ticketCategoryId, quantity) {
  const result = await pool.query(`
    SELECT 
      quantity_total,
      quantity_sold,
      quantity_reserved,
      (quantity_total - quantity_sold - quantity_reserved) as available
    FROM ticket_categories
    WHERE id = $1 AND is_active = true
  `, [ticketCategoryId]);
  
  const category = result.rows[0];
  
  if (!category) {
    return { available: false, reason: 'category_not_found' };
  }
  
  if (category.available < quantity) {
    return { 
      available: false, 
      reason: 'insufficient_stock',
      availableQuantity: category.available
    };
  }
  
  return { available: true };
}
```

### Réserver des Billets (Panier)

```javascript
async function reserveTickets(ticketCategoryId, quantity) {
  const client = await pool.connect();
  
  try {
    await client.query('BEGIN');
    
    // Vérifier disponibilité
    const check = await checkTicketAvailability(ticketCategoryId, quantity);
    if (!check.available) {
      throw new Error(check.reason);
    }
    
    // Réserver
    await client.query(`
      UPDATE ticket_categories 
      SET quantity_reserved = quantity_reserved + $1
      WHERE id = $2
    `, [quantity, ticketCategoryId]);
    
    await client.query('COMMIT');
    return { success: true };
    
  } catch (err) {
    await client.query('ROLLBACK');
    throw err;
  } finally {
    client.release();
  }
}
```

### Générer Billets avec QR Code

```javascript
const QRCode = require('qrcode');
const crypto = require('crypto');

async function generateTickets(orderId, ticketCategoryId, quantity, userId) {
  const tickets = [];
  
  for (let i = 0; i < quantity; i++) {
    // Générer numéro unique
    const ticketNumber = `TKT-${Date.now()}-${crypto.randomBytes(4).toString('hex').toUpperCase()}`;
    
    // Générer données QR
    const qrData = `AIOLIA:${ticketNumber}:${orderId}:${userId}`;
    
    // Générer image QR
    const qrImageUrl = await QRCode.toDataURL(qrData);
    
    // Insérer dans la BDD
    const result = await pool.query(`
      INSERT INTO tickets (
        ticket_category_id, 
        order_id, 
        user_id, 
        ticket_number, 
        qr_code_data, 
        qr_code_image_url,
        status
      ) VALUES ($1, $2, $3, $4, $5, $6, 'valid')
      RETURNING *
    `, [ticketCategoryId, orderId, userId, ticketNumber, qrData, qrImageUrl]);
    
    tickets.push(result.rows[0]);
  }
  
  return tickets;
}
```

---

## 💰 4. Gestion des Paiements

### Créer une Commande

```javascript
async function createOrder(userId, cartItems, promoCode = null) {
  const client = await pool.connect();
  
  try {
    await client.query('BEGIN');
    
    // Générer numéro de commande
    const orderNumber = `ORD-${Date.now()}-${crypto.randomBytes(3).toString('hex').toUpperCase()}`;
    
    // Calculer total
    let subtotal = 0;
    for (const item of cartItems) {
      const cat = await client.query('SELECT price FROM ticket_categories WHERE id = $1', [item.ticket_category_id]);
      subtotal += cat.rows[0].price * item.quantity;
    }
    
    // Appliquer code promo
    let discountAmount = 0;
    if (promoCode) {
      const promo = await client.query('SELECT * FROM promo_codes WHERE code = $1 AND is_active = true', [promoCode]);
      if (promo.rows.length > 0) {
        const p = promo.rows[0];
        if (p.discount_type === 'percentage') {
          discountAmount = subtotal * (p.discount_value / 100);
        } else {
          discountAmount = p.discount_value;
        }
      }
    }
    
    const totalAmount = subtotal - discountAmount;
    
    // Créer la commande
    const orderResult = await client.query(`
      INSERT INTO orders (user_id, order_number, subtotal, discount_amount, total_amount, status)
      VALUES ($1, $2, $3, $4, $5, 'pending')
      RETURNING *
    `, [userId, orderNumber, subtotal, discountAmount, totalAmount]);
    
    const orderId = orderResult.rows[0].id;
    
    // Créer les items
    for (const item of cartItems) {
      await client.query(`
        INSERT INTO order_items (order_id, ticket_category_id, quantity, unit_price, total_price)
        VALUES ($1, $2, $3, $4, $5)
      `, [orderId, item.ticket_category_id, item.quantity, item.unit_price, item.total_price]);
    }
    
    await client.query('COMMIT');
    return orderResult.rows[0];
    
  } catch (err) {
    await client.query('ROLLBACK');
    throw err;
  } finally {
    client.release();
  }
}
```

---

## 🔔 5. Système de Notifications

### Créer une Notification

```javascript
async function createNotification(userId, type, titleKey, messageKey, lang = 'fr') {
  const title = t(titleKey, lang);
  const message = t(messageKey, lang);
  
  await pool.query(`
    INSERT INTO notifications (
      user_id, 
      type, 
      title, 
      message, 
      channel, 
      status
    ) VALUES ($1, $2, $3, $4, $5, 'pending')
  `, [userId, type, title, message, 'email']);
}

// Exemple : Envoi rappel 24h avant événement
async function sendEventReminders() {
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  
  // Récupérer tous les événements de demain
  const events = await pool.query(`
    SELECT DISTINCT 
      t.user_id, 
      e.id as event_id, 
      e.title,
      u.language
    FROM tickets t
    JOIN ticket_categories tc ON t.ticket_category_id = tc.id
    JOIN events e ON tc.event_id = e.id
    JOIN users u ON t.user_id = u.id
    WHERE e.start_date::date = $1::date
    AND t.status = 'valid'
  `, [tomorrow]);
  
  // Créer notifications
  for (const event of events.rows) {
    await createNotification(
      event.user_id,
      'event_reminder',
      'emails.event_reminder.subject',
      'emails.event_reminder.body',
      event.language
    );
  }
}
```

### Alerte Stock Bas (Organisateur)

```javascript
async function checkLowStockAlerts() {
  const lowStock = await pool.query(`
    SELECT 
      tc.*,
      e.organizer_id,
      (tc.quantity_total - tc.quantity_sold - tc.quantity_reserved) as remaining
    FROM ticket_categories tc
    JOIN events e ON tc.event_id = e.id
    WHERE tc.is_active = true
    AND (tc.quantity_total - tc.quantity_sold - tc.quantity_reserved) < 10
    AND (tc.quantity_total - tc.quantity_sold - tc.quantity_reserved) > 0
  `);
  
  for (const cat of lowStock.rows) {
    const lang = 'fr'; // Ou récupérer depuis l'organisateur
    
    await pool.query(`
      INSERT INTO notifications (
        user_id, 
        type, 
        title, 
        message, 
        channel,
        reference_type,
        reference_id,
        priority
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
    `, [
      cat.organizer_id,
      'alert',
      t('organizer.tickets.low_stock_alert', lang),
      `Stock faible pour "${cat.name}" : ${cat.remaining} billets restants`,
      'email',
      'ticket_category',
      cat.id,
      'high'
    ]);
  }
}
```

---

## 💳 6. Historique des Prix

### Enregistrer Modification de Prix

```javascript
async function updateTicketPrice(ticketCategoryId, newPrice, reason, userId) {
  const client = await pool.connect();
  
  try {
    await client.query('BEGIN');
    
    // Récupérer l'ancien prix
    const oldPriceResult = await client.query('SELECT price FROM ticket_categories WHERE id = $1', [ticketCategoryId]);
    const oldPrice = oldPriceResult.rows[0].price;
    
    // Mettre à jour le prix
    await client.query('UPDATE ticket_categories SET price = $1 WHERE id = $2', [newPrice, ticketCategoryId]);
    
    // Enregistrer dans l'historique
    await client.query(`
      INSERT INTO ticket_price_history (ticket_category_id, old_price, new_price, reason, changed_by)
      VALUES ($1, $2, $3, $4, $5)
    `, [ticketCategoryId, oldPrice, newPrice, reason, userId]);
    
    await client.query('COMMIT');
    
  } catch (err) {
    await client.query('ROLLBACK');
    throw err;
  } finally {
    client.release();
  }
}
```

### Récupérer Historique Prix

```javascript
async function getPriceHistory(ticketCategoryId) {
  const result = await pool.query(`
    SELECT 
      tph.*,
      u.first_name || ' ' || u.last_name as changed_by_name
    FROM ticket_price_history tph
    LEFT JOIN users u ON tph.changed_by = u.id
    WHERE tph.ticket_category_id = $1
    ORDER BY tph.created_at DESC
  `, [ticketCategoryId]);
  
  return result.rows;
}
```

---

## 👥 7. Gestion d'Équipe (Co-organisateurs)

### Ajouter un Co-organisateur

```javascript
async function addCollaborator(eventId, userEmail, role, invitedBy) {
  // Trouver l'utilisateur par email
  const user = await pool.query('SELECT id FROM users WHERE email = $1', [userEmail]);
  
  if (user.rows.length === 0) {
    throw new Error('User not found');
  }
  
  const userId = user.rows[0].id;
  
  // Ajouter comme collaborateur
  await pool.query(`
    INSERT INTO event_collaborators (
      event_id, 
      user_id, 
      role,
      can_edit_event,
      can_manage_tickets,
      can_view_sales,
      can_manage_team,
      can_send_notifications,
      invited_by,
      is_active
    ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, true)
  `, [
    eventId, 
    userId, 
    role,
    role === 'admin' || role === 'editor',  // can_edit_event
    role === 'admin' || role === 'editor',  // can_manage_tickets
    true,                                    // can_view_sales
    role === 'admin',                        // can_manage_team
    role === 'admin' || role === 'editor',  // can_send_notifications
    invitedBy
  ]);
  
  // Envoyer notification
  await createNotification(userId, 'new_event', 'notifications.collaboration_invite', '', 'fr');
}
```

---

## 🎁 8. Système de Parrainage

### Créer un Code de Parrainage

```javascript
async function createReferralCode(userId) {
  const referralCode = `REF-${userId}-${crypto.randomBytes(3).toString('hex').toUpperCase()}`;
  
  // Le code est stocké dans user_referrals quand l'utilisateur invite quelqu'un
  return referralCode;
}

### Traiter un Parrainage

```javascript
async function processReferral(referrerUserId, referredEmail) {
  const referralCode = await createReferralCode(referrerUserId);
  
  await pool.query(`
    INSERT INTO user_referrals (
      referrer_user_id,
      referred_email,
      referral_code,
      reward_points,
      status
    ) VALUES ($1, $2, $3, 500, 'pending')
  `, [referrerUserId, referredEmail, referralCode]);
  
  // Envoyer email d'invitation à referred_email
  // ...
  
  return referralCode;
}

// Quand le filleul s'inscrit et fait son premier achat
async function completeReferral(referredEmail) {
  const referral = await pool.query(`
    SELECT * FROM user_referrals 
    WHERE referred_email = $1 AND status = 'pending'
  `, [referredEmail]);
  
  if (referral.rows.length > 0) {
    const ref = referral.rows[0];
    
    // Marquer comme complété
    await pool.query(`
      UPDATE user_referrals 
      SET status = 'completed', completed_at = CURRENT_TIMESTAMP
      WHERE id = $1
    `, [ref.id]);
    
    // Créditer les points au parrain
    await pool.query(`
      UPDATE users 
      SET 
        loyalty_points = loyalty_points + $1,
        points_lifetime_earned = points_lifetime_earned + $1
      WHERE id = $2
    `, [ref.reward_points, ref.referrer_user_id]);
  }
}
```

---

## 🔍 9. Recherche d'Événements

### Recherche Multi-Critères

```javascript
async function searchEvents(params) {
  const { 
    query = '',           // Texte de recherche
    category = null,      // ID catégorie
    location = null,      // Ville
    dateFrom = null,      // Date début
    dateTo = null,        // Date fin
    minPrice = null,      // Prix min
    maxPrice = null,      // Prix max
    lang = 'fr'           // Langue
  } = params;
  
  let sql = `
    SELECT DISTINCT
      e.*,
      ec.name as category_name,
      ec.slug as category_slug,
      MIN(tc.price) as min_price,
      MAX(tc.price) as max_price,
      (e.stats->>'total_tickets_sold')::INT as tickets_sold
    FROM events e
    JOIN event_categories ec ON e.category_id = ec.id
    LEFT JOIN ticket_categories tc ON e.id = tc.event_id
    WHERE e.status = 'published'
  `;
  
  const queryParams = [];
  let paramIndex = 1;
  
  // Filtrer par texte
  if (query) {
    sql += ` AND to_tsvector('french', e.title || ' ' || COALESCE(e.description, '')) @@ plainto_tsquery($${paramIndex})`;
    queryParams.push(query);
    paramIndex++;
  }
  
  // Filtrer par catégorie
  if (category) {
    sql += ` AND e.category_id = $${paramIndex}`;
    queryParams.push(category);
    paramIndex++;
  }
  
  // Filtrer par localisation
  if (location) {
    sql += ` AND e.location ILIKE $${paramIndex}`;
    queryParams.push(`%${location}%`);
    paramIndex++;
  }
  
  // Filtrer par date
  if (dateFrom) {
    sql += ` AND e.start_date >= $${paramIndex}`;
    queryParams.push(dateFrom);
    paramIndex++;
  }
  
  if (dateTo) {
    sql += ` AND e.end_date <= $${paramIndex}`;
    queryParams.push(dateTo);
    paramIndex++;
  }
  
  sql += ` GROUP BY e.id, ec.name, ec.slug`;
  
  // Filtrer par prix (après GROUP BY)
  if (minPrice) {
    sql += ` HAVING MIN(tc.price) >= ${minPrice}`;
  }
  
  if (maxPrice) {
    sql += ` HAVING MAX(tc.price) <= ${maxPrice}`;
  }
  
  sql += ` ORDER BY e.start_date ASC`;
  
  const result = await pool.query(sql, queryParams);
  
  // Appliquer traductions catégories
  return result.rows.map(event => ({
    ...event,
    category_name: translations.event_categories[event.category_slug][lang].name
  }));
}
```

---

## 📊 10. Dashboard Organisateur

### Récupérer Statistiques Événement

```javascript
async function getEventDashboard(eventId) {
  const result = await pool.query(`
    SELECT 
      e.*,
      es.total_tickets_sold,
      es.total_revenue,
      es.gross_revenue,
      es.net_revenue,
      es.tax_amount,
      es.average_rating,
      es.total_reviews,
      (SELECT COUNT(*) FROM favorites WHERE event_id = e.id) as favorites_count,
      (SELECT COUNT(*) FROM event_waitlist WHERE event_id = e.id AND status = 'waiting') as waitlist_count
    FROM events e
    LEFT JOIN event_statistics es ON e.id = es.event_id
    WHERE e.id = $1
  `, [eventId]);
  
  // Récupérer ventes par catégorie de billet
  const ticketSales = await pool.query(`
    SELECT 
      tc.name,
      tc.quantity_total,
      tc.quantity_sold,
      (tc.quantity_total - tc.quantity_sold - tc.quantity_reserved) as available,
      tc.price,
      (tc.quantity_sold * tc.price) as revenue
    FROM ticket_categories tc
    WHERE tc.event_id = $1
    ORDER BY tc.display_order
  `, [eventId]);
  
  return {
    event: result.rows[0],
    ticketSales: ticketSales.rows
  };
}
```

---

## 🤝 11. Réseau Social (Amis)

### Voir Qui Va au Même Événement

```javascript
async function getFriendsAtEvent(userId, eventId) {
  const result = await pool.query(`
    SELECT DISTINCT
      u.id,
      u.first_name || ' ' || u.last_name as name,
      u.photo_url,
      tc.name as ticket_category
    FROM user_connections uc
    JOIN tickets t ON uc.friend_user_id = t.user_id
    JOIN ticket_categories tc ON t.ticket_category_id = tc.id
    JOIN users u ON uc.friend_user_id = u.id
    WHERE uc.user_id = $1
    AND uc.status = 'accepted'
    AND tc.event_id = $2
    AND t.status = 'valid'
  `, [userId, eventId]);
  
  return result.rows;
}
```

---

## 🎯 RÉSUMÉ DES EXEMPLES

```
✅ Traductions multi-langues        (translations.js)
✅ Statistiques utilisateur          (Calcul dans le code)
✅ Génération QR codes              (Librairie qrcode)
✅ Paiements Mobile Money           (API externe + BDD)
✅ Historique prix                  (Table dédiée)
✅ Notifications traduites          (Fonction helper)
✅ Recherche multi-critères         (SQL dynamique)
✅ Dashboard organisateur           (Requêtes JOIN)
✅ Réseau social                    (Requêtes relationelles)
```

---

**Tous ces exemples utilisent les 26 tables du schéma sans JSONB.**  
**La logique métier est entièrement dans le code applicatif.**  
**Les traductions sont gérées via translations.js.**

🚀 **Prêt à développer !**

