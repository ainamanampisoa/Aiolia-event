# 📊 Calcul des Statistiques dans le Code Backend

## 🎯 Contexte

Les tables `user_statistics` et `event_statistics` ont été **supprimées** du schéma.  
Toutes les statistiques sont maintenant **calculées à la volée** dans votre code backend.

---

## 👤 1. STATISTIQUES UTILISATEUR

### Total Événements Assistés

```javascript
async function getTotalEventsAttended(userId) {
  const result = await pool.query(`
    SELECT COUNT(DISTINCT tc.event_id) as total
    FROM tickets t
    JOIN ticket_categories tc ON t.ticket_category_id = tc.id
    WHERE t.user_id = $1 
    AND t.status IN ('valid', 'used')
  `, [userId]);
  
  return result.rows[0].total;
}
```

### Total Dépensé

```javascript
async function getTotalSpent(userId) {
  const result = await pool.query(`
    SELECT COALESCE(SUM(total_amount), 0) as total_spent
    FROM orders
    WHERE user_id = $1 
    AND status = 'completed'
  `, [userId]);
  
  return parseFloat(result.rows[0].total_spent);
}
```

### Total Billets Achetés

```javascript
async function getTotalTicketsPurchased(userId) {
  const result = await pool.query(`
    SELECT COUNT(*) as total
    FROM tickets
    WHERE user_id = $1 
    AND status != 'cancelled'
  `, [userId]);
  
  return result.rows[0].total;
}
```

### Catégorie Préférée

```javascript
async function getFavoriteCategory(userId) {
  const result = await pool.query(`
    SELECT 
      ec.id,
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
    LIMIT 1
  `, [userId]);
  
  return result.rows[0] || null;
}
```

### Dépenses Mensuelles (12 derniers mois)

```javascript
async function getMonthlySpending(userId) {
  const result = await pool.query(`
    SELECT 
      TO_CHAR(created_at, 'YYYY-MM') as month,
      SUM(total_amount) as amount
    FROM orders
    WHERE user_id = $1 
    AND status = 'completed'
    AND created_at >= CURRENT_TIMESTAMP - INTERVAL '12 months'
    GROUP BY TO_CHAR(created_at, 'YYYY-MM')
    ORDER BY month DESC
  `, [userId]);
  
  // Formater en objet {month: amount}
  return result.rows.reduce((acc, row) => {
    acc[row.month] = parseFloat(row.amount);
    return acc;
  }, {});
}
```

### Distribution par Catégories

```javascript
async function getCategoriesDistribution(userId) {
  const result = await pool.query(`
    SELECT 
      ec.name,
      ec.slug,
      COUNT(*) as count,
      SUM(oi.total_price) as total_spent
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
  
  // Formater en objet {category: {count, amount}}
  return result.rows.reduce((acc, row) => {
    acc[row.slug] = {
      name: row.name,
      count: parseInt(row.count),
      spent: parseFloat(row.total_spent)
    };
    return acc;
  }, {});
}
```

### Statistiques Complètes Utilisateur

```javascript
async function getUserStatistics(userId) {
  const [
    totalEventsAttended,
    totalSpent,
    totalTickets,
    favoriteCategory,
    monthlySpending,
    categoriesDistribution
  ] = await Promise.all([
    getTotalEventsAttended(userId),
    getTotalSpent(userId),
    getTotalTicketsPurchased(userId),
    getFavoriteCategory(userId),
    getMonthlySpending(userId),
    getCategoriesDistribution(userId)
  ]);
  
  const lastPurchase = await pool.query(`
    SELECT MAX(created_at) as last_date
    FROM orders
    WHERE user_id = $1 AND status = 'completed'
  `, [userId]);
  
  return {
    total_events_attended: totalEventsAttended,
    total_spent: totalSpent,
    total_tickets_purchased: totalTickets,
    favorite_category: favoriteCategory,
    last_purchase_date: lastPurchase.rows[0].last_date,
    monthly_spending: monthlySpending,
    categories_distribution: categoriesDistribution
  };
}
```

---

## 🎉 2. STATISTIQUES ÉVÉNEMENT

### Total Billets Vendus

```javascript
async function getTotalTicketsSold(eventId) {
  const result = await pool.query(`
    SELECT COUNT(*) as total
    FROM tickets t
    JOIN ticket_categories tc ON t.ticket_category_id = tc.id
    WHERE tc.event_id = $1 
    AND t.status NOT IN ('cancelled', 'refunded')
  `, [eventId]);
  
  return result.rows[0].total;
}
```

### Revenu Total

```javascript
async function getTotalRevenue(eventId) {
  const result = await pool.query(`
    SELECT COALESCE(SUM(oi.total_price), 0) as total_revenue
    FROM order_items oi
    JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
    JOIN orders o ON oi.order_id = o.id
    WHERE tc.event_id = $1 
    AND o.status = 'completed'
  `, [eventId]);
  
  return parseFloat(result.rows[0].total_revenue);
}
```

### Statistiques Financières Détaillées

```javascript
async function getEventFinancialStats(eventId) {
  // Récupérer le taux de taxe de l'événement
  const eventData = await pool.query(`
    SELECT tax_rate, tax_included FROM events WHERE id = $1
  `, [eventId]);
  
  const event = eventData.rows[0];
  
  // Calculer les revenus
  const revenueData = await pool.query(`
    SELECT 
      COALESCE(SUM(oi.total_price), 0) as gross_revenue,
      COALESCE(SUM(o.discount_amount), 0) as total_discounts
    FROM order_items oi
    JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
    JOIN orders o ON oi.order_id = o.id
    WHERE tc.event_id = $1 
    AND o.status = 'completed'
  `, [eventId]);
  
  const grossRevenue = parseFloat(revenueData.rows[0].gross_revenue);
  
  // Calculer la TVA
  let taxAmount = 0;
  if (event.tax_rate > 0) {
    if (event.tax_included) {
      taxAmount = grossRevenue - (grossRevenue / (1 + event.tax_rate / 100));
    } else {
      taxAmount = grossRevenue * (event.tax_rate / 100);
    }
  }
  
  // Commission plateforme (5% par défaut)
  const platformFeeRate = 0.05;
  const platformFees = grossRevenue * platformFeeRate;
  
  // Frais de traitement paiement (estimé à 2%)
  const paymentFeeRate = 0.02;
  const paymentFees = grossRevenue * paymentFeeRate;
  
  // Remboursements
  const refundsData = await pool.query(`
    SELECT COALESCE(SUM(total_amount), 0) as total_refunds
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
    WHERE tc.event_id = $1 
    AND o.status = 'refunded'
  `, [eventId]);
  
  const refundsTotal = parseFloat(refundsData.rows[0].total_refunds);
  
  // Revenu net
  const netRevenue = grossRevenue - taxAmount - refundsTotal;
  
  // Paiement organisateur
  const organizerPayout = netRevenue - platformFees - paymentFees;
  
  return {
    gross_revenue: grossRevenue,
    tax_amount: taxAmount,
    net_revenue: netRevenue,
    platform_fees: platformFees,
    payment_processing_fees: paymentFees,
    refunds_total: refundsTotal,
    organizer_payout: organizerPayout
  };
}
```

### Note Moyenne & Total Avis

```javascript
async function getEventRatings(eventId) {
  const result = await pool.query(`
    SELECT 
      COALESCE(AVG(rating), 0) as average_rating,
      COUNT(*) as total_reviews
    FROM reviews
    WHERE event_id = $1 
    AND is_published = true
  `, [eventId]);
  
  return {
    average_rating: parseFloat(result.rows[0].average_rating).toFixed(2),
    total_reviews: result.rows[0].total_reviews
  };
}
```

### Prix Moyen du Billet

```javascript
async function getAverageTicketPrice(eventId) {
  const result = await pool.query(`
    SELECT AVG(tc.price) as average_price
    FROM ticket_categories tc
    WHERE tc.event_id = $1 
    AND tc.is_active = true
  `, [eventId]);
  
  return parseFloat(result.rows[0].average_price || 0);
}
```

### Taux de Conversion

```javascript
async function getConversionRate(eventId) {
  const result = await pool.query(`
    SELECT 
      e.views_count,
      (SELECT COUNT(*) FROM tickets t
       JOIN ticket_categories tc ON t.ticket_category_id = tc.id
       WHERE tc.event_id = e.id) as tickets_sold
    FROM events e
    WHERE e.id = $1
  `, [eventId]);
  
  const { views_count, tickets_sold } = result.rows[0];
  
  if (views_count === 0) return 0;
  
  const conversionRate = (tickets_sold / views_count) * 100;
  return parseFloat(conversionRate.toFixed(2));
}
```

### Statistiques Complètes Événement

```javascript
async function getEventStatistics(eventId) {
  const [
    totalTicketsSold,
    totalRevenue,
    financialStats,
    ratings,
    averagePrice,
    conversionRate
  ] = await Promise.all([
    getTotalTicketsSold(eventId),
    getTotalRevenue(eventId),
    getEventFinancialStats(eventId),
    getEventRatings(eventId),
    getAverageTicketPrice(eventId),
    getConversionRate(eventId)
  ]);
  
  const favorites = await pool.query(`
    SELECT COUNT(*) as total
    FROM favorites
    WHERE event_id = $1
  `, [eventId]);
  
  return {
    total_tickets_sold: totalTicketsSold,
    total_revenue: totalRevenue,
    ...financialStats,
    average_ticket_price: averagePrice,
    conversion_rate: conversionRate,
    average_rating: parseFloat(ratings.average_rating),
    total_reviews: ratings.total_reviews,
    total_favorites: favorites.rows[0].total,
    last_calculated_at: new Date()
  };
}
```

---

## 📈 3. API ROUTES RECOMMANDÉES

### Route Statistiques Utilisateur

```javascript
// GET /api/users/:id/statistics
app.get('/api/users/:id/statistics', async (req, res) => {
  try {
    const stats = await getUserStatistics(req.params.id);
    res.json(stats);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});
```

### Route Statistiques Événement

```javascript
// GET /api/events/:id/statistics
app.get('/api/events/:id/statistics', async (req, res) => {
  try {
    const stats = await getEventStatistics(req.params.id);
    res.json(stats);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});
```

### Route Dashboard Organisateur

```javascript
// GET /api/organizer/dashboard
app.get('/api/organizer/dashboard', async (req, res) => {
  const organizerId = req.user.id; // Depuis le token JWT
  
  try {
    // Récupérer tous les événements de l'organisateur
    const events = await pool.query(`
      SELECT id, title, start_date, status 
      FROM events 
      WHERE organizer_id = $1
      ORDER BY start_date DESC
    `, [organizerId]);
    
    // Calculer les stats pour chaque événement
    const eventsWithStats = await Promise.all(
      events.rows.map(async (event) => ({
        ...event,
        stats: await getEventStatistics(event.id)
      }))
    );
    
    res.json(eventsWithStats);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});
```

---

## ⚡ 4. OPTIMISATION & CACHE

### Utiliser un Cache (Redis)

```javascript
const redis = require('redis');
const client = redis.createClient();

async function getEventStatisticsWithCache(eventId) {
  const cacheKey = `event:${eventId}:stats`;
  
  // Vérifier le cache
  const cached = await client.get(cacheKey);
  if (cached) {
    return JSON.parse(cached);
  }
  
  // Calculer les stats
  const stats = await getEventStatistics(eventId);
  
  // Mettre en cache pour 5 minutes
  await client.setex(cacheKey, 300, JSON.stringify(stats));
  
  return stats;
}
```

### Job CRON pour Pré-Calcul

```javascript
// S'exécute toutes les heures
const cron = require('node-cron');

cron.schedule('0 * * * *', async () => {
  console.log('Pré-calcul des statistiques...');
  
  // Récupérer tous les événements actifs
  const events = await pool.query(`
    SELECT id FROM events 
    WHERE status IN ('published', 'ongoing')
  `);
  
  // Pré-calculer et mettre en cache
  for (const event of events.rows) {
    const stats = await getEventStatistics(event.id);
    await client.setex(`event:${event.id}:stats`, 3600, JSON.stringify(stats));
  }
  
  console.log(`✅ ${events.rows.length} événements pré-calculés`);
});
```

---

## 📊 5. DASHBOARD ORGANISATEUR COMPLET

```javascript
async function getOrganizerDashboard(organizerId) {
  // Statistiques globales
  const globalStats = await pool.query(`
    SELECT 
      COUNT(DISTINCT e.id) as total_events,
      COUNT(DISTINCT e.id) FILTER (WHERE e.status = 'published') as active_events,
      COUNT(DISTINCT t.id) as total_tickets_sold,
      COALESCE(SUM(o.total_amount), 0) as total_revenue
    FROM events e
    LEFT JOIN ticket_categories tc ON e.id = tc.event_id
    LEFT JOIN tickets t ON tc.id = t.ticket_category_id AND t.status != 'cancelled'
    LEFT JOIN orders o ON t.order_id = o.id AND o.status = 'completed'
    WHERE e.organizer_id = $1
  `, [organizerId]);
  
  // Top 5 événements par revenus
  const topEvents = await pool.query(`
    SELECT 
      e.id,
      e.title,
      COUNT(DISTINCT t.id) as tickets_sold,
      COALESCE(SUM(oi.total_price), 0) as revenue
    FROM events e
    LEFT JOIN ticket_categories tc ON e.id = tc.event_id
    LEFT JOIN order_items oi ON tc.id = oi.ticket_category_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
    LEFT JOIN tickets t ON tc.id = t.ticket_category_id AND t.status != 'cancelled'
    WHERE e.organizer_id = $1
    GROUP BY e.id, e.title
    ORDER BY revenue DESC
    LIMIT 5
  `, [organizerId]);
  
  // Ventes par mois (6 derniers mois)
  const monthlySales = await pool.query(`
    SELECT 
      TO_CHAR(o.created_at, 'YYYY-MM') as month,
      COUNT(DISTINCT o.id) as orders_count,
      SUM(o.total_amount) as revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
    JOIN events e ON tc.event_id = e.id
    WHERE e.organizer_id = $1
    AND o.status = 'completed'
    AND o.created_at >= CURRENT_TIMESTAMP - INTERVAL '6 months'
    GROUP BY TO_CHAR(o.created_at, 'YYYY-MM')
    ORDER BY month DESC
  `, [organizerId]);
  
  return {
    global: globalStats.rows[0],
    top_events: topEvents.rows,
    monthly_sales: monthlySales.rows
  };
}
```

---

## 🔔 6. ALERTES AUTOMATIQUES

### Alerte Stock Bas (Job Automatique)

```javascript
// À exécuter toutes les heures
async function checkLowStockAndAlert() {
  const lowStock = await pool.query(`
    SELECT 
      tc.id,
      tc.name,
      tc.quantity_total,
      tc.quantity_sold,
      tc.quantity_reserved,
      (tc.quantity_total - tc.quantity_sold - tc.quantity_reserved) as remaining,
      e.id as event_id,
      e.title as event_title,
      e.organizer_id
    FROM ticket_categories tc
    JOIN events e ON tc.event_id = e.id
    WHERE tc.is_active = true
    AND (tc.quantity_total - tc.quantity_sold - tc.quantity_reserved) < 10
    AND (tc.quantity_total - tc.quantity_sold - tc.quantity_reserved) > 0
    AND e.status = 'published'
  `);
  
  for (const cat of lowStock.rows) {
    // Vérifier si une notification n'a pas déjà été envoyée récemment
    const existingAlert = await pool.query(`
      SELECT id FROM notifications
      WHERE user_id = $1
      AND type = 'alert'
      AND reference_id = $2
      AND created_at > CURRENT_TIMESTAMP - INTERVAL '24 hours'
    `, [cat.organizer_id, cat.id]);
    
    if (existingAlert.rows.length === 0) {
      // Créer la notification
      await pool.query(`
        INSERT INTO notifications (
          user_id,
          type,
          title,
          message,
          channel,
          priority,
          reference_type,
          reference_id
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
      `, [
        cat.organizer_id,
        'alert',
        'Alerte Stock Faible',
        `Il ne reste que ${cat.remaining} billets pour "${cat.name}" de l'événement "${cat.event_title}"`,
        'email',
        'high',
        'ticket_category',
        cat.id
      ]);
    }
  }
}
```

---

## 🎯 RÉSUMÉ

```
╔═══════════════════════════════════════════════════════╗
║        CALCUL DES STATISTIQUES - BONNES PRATIQUES    ║
╠═══════════════════════════════════════════════════════╣
║                                                       ║
║  ✅ Calculer à la volée (requêtes SQL)              ║
║  ✅ Utiliser un cache Redis (5-60 min)              ║
║  ✅ Job CRON pour pré-calcul (toutes les heures)    ║
║  ✅ Paralléliser avec Promise.all()                  ║
║  ✅ Optimiser avec index sur colonnes calculées      ║
║                                                       ║
║  ⚠️ Ne PAS calculer à chaque requête                ║
║  ⚠️ Ne PAS stocker dans la BDD (sauf cache)         ║
║  ⚠️ Surveiller les performances                      ║
║                                                       ║
╚═══════════════════════════════════════════════════════╝
```

---

**Avantages de cette approche:**
- ✅ Pas de désynchronisation (toujours à jour)
- ✅ Pas de maintenance de tables stats
- ✅ Flexibilité totale pour ajouter de nouvelles stats
- ✅ Pas de triggers complexes

**Inconvénients:**
- ⚠️ Requêtes plus complexes
- ⚠️ Nécessite un cache pour la performance
- ⚠️ Charge sur la BDD si mal optimisé

---

**Dernière mise à jour** : 14 Octobre 2025  
**Version** : 2.0 Final  
**Tables Statistiques** : 0 (calculées dans le backend)

