# 🗄️ Base de Données Aiolia Event

Cette documentation décrit la structure et l'utilisation de la base de données pour la plateforme **Aiolia Event**.

---

## 📁 Structure des Fichiers

```
database/
├── README.md                    # Ce fichier
├── CONCEPTION_SQL.md           # Documentation complète de la conception
├── schema.sql                  # Schéma principal de la BDD (tables)
├── triggers.sql                # Triggers automatiques
├── procedures.sql              # Procédures stockées
├── seeds.sql                   # Données de base et de test
├── indexes_optimization.sql    # Index supplémentaires pour performance
└── migrations/                 # (À créer) Migrations versionnées
```

---

## 🚀 Installation

### Prérequis

- MySQL 8.0+ ou MariaDB 10.5+
- Privilèges suffisants pour créer des bases de données
- Au moins 500 MB d'espace disque disponible

### Installation Rapide

```bash
# 1. Créer la base de données
mysql -u root -p -e "CREATE DATABASE aiolia_event CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Importer le schéma
mysql -u root -p aiolia_event < schema.sql

# 3. Importer les triggers
mysql -u root -p aiolia_event < triggers.sql

# 4. Importer les procédures stockées
mysql -u root -p aiolia_event < procedures.sql

# 5. Importer les données de base
mysql -u root -p aiolia_event < seeds.sql

# 6. (Optionnel) Ajouter les index d'optimisation
mysql -u root -p aiolia_event < indexes_optimization.sql
```

### Installation avec Docker

```bash
# Créer un conteneur MySQL
docker run -d \
  --name aiolia-mysql \
  -e MYSQL_ROOT_PASSWORD=your_password \
  -e MYSQL_DATABASE=aiolia_event \
  -p 3306:3306 \
  -v $(pwd)/database:/docker-entrypoint-initdb.d \
  mysql:8.0

# Les fichiers SQL seront automatiquement exécutés au premier démarrage
```

---

## 📊 Schéma de Base de Données

### Vue d'ensemble

La base de données est organisée en **20 modules principaux** :

1. **Authentification & Utilisateurs** - Gestion des comptes et permissions
2. **Catégories & Événements** - Catalogues d'événements
3. **Billets** - Gestion de la billetterie et QR codes
4. **Codes Promo** - Système de promotions
5. **Commandes & Paiements** - Transactions et Mobile Money
6. **Panier** - Panier d'achat persistant
7. **Favoris** - Wishlist et événements favoris
8. **Historique** - Recherches et vues d'événements
9. **Portefeuille** - Points de fidélité et solde
10. **Parrainage** - Programme de parrainage
11. **Mini-Jeu** - Gamification "Ticket Chance"
12. **Social** - Amis et événements partagés
13. **Liste d'Attente** - File d'attente pour événements complets
14. **Notifications** - Multi-canal (email, push, SMS)
15. **Avis** - Évaluations et commentaires
16. **Statistiques** - Analytics temps réel
17. **Rapports** - Génération de rapports
18. **Audit** - Logs de traçabilité
19. **Configuration** - Paramètres système
20. **Multi-langue** - Support internationalisation

### Tables Principales

| Table | Description | Enregistrements estimés |
|-------|-------------|------------------------|
| `users` | Utilisateurs (clients + organisateurs) | 10K - 1M |
| `events` | Événements | 1K - 100K |
| `tickets` | Billets individuels | 100K - 10M |
| `orders` | Commandes | 50K - 5M |
| `notifications` | Notifications | 500K - 50M |
| `event_views` | Historique de vues | 1M - 100M |

---

## 🔧 Configuration

### Paramètres MySQL Recommandés

Ajoutez dans votre fichier `my.cnf` ou `my.ini` :

```ini
[mysqld]
# Encodage
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci

# Performance
max_connections=500
innodb_buffer_pool_size=2G
innodb_log_file_size=512M
innodb_flush_log_at_trx_commit=2

# Cache
query_cache_type=1
query_cache_size=128M
query_cache_limit=4M

# Slow query log
slow_query_log=1
long_query_time=1
slow_query_log_file=/var/log/mysql/slow.log

# Binlog (pour réplication)
log_bin=mysql-bin
binlog_format=ROW
expire_logs_days=7
```

### Créer un Utilisateur Dédié

```sql
-- Utilisateur pour l'application (lecture/écriture)
CREATE USER 'aiolia_app'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT SELECT, INSERT, UPDATE, DELETE ON aiolia_event.* TO 'aiolia_app'@'localhost';

-- Utilisateur pour les rapports (lecture seule)
CREATE USER 'aiolia_readonly'@'localhost' IDENTIFIED BY 'readonly_password';
GRANT SELECT ON aiolia_event.* TO 'aiolia_readonly'@'localhost';

-- Utilisateur admin (tous les privilèges)
CREATE USER 'aiolia_admin'@'localhost' IDENTIFIED BY 'admin_password';
GRANT ALL PRIVILEGES ON aiolia_event.* TO 'aiolia_admin'@'localhost';

FLUSH PRIVILEGES;
```

---

## 🔄 Maintenance

### Jobs CRON Recommandés

```bash
# Fichier : /etc/cron.d/aiolia-event

# Recalcul des statistiques (chaque heure)
0 * * * * mysql_user mysql -u aiolia_app -p'password' -e "CALL calculate_all_event_statistics();"

# Statistiques quotidiennes (chaque jour à 1h)
0 1 * * * mysql_user mysql -u aiolia_app -p'password' -e "CALL generate_daily_sales_stats(CURDATE() - INTERVAL 1 DAY);"

# Nettoyage des paniers expirés (toutes les 15 min)
*/15 * * * * mysql_user mysql -u aiolia_app -p'password' -e "DELETE FROM aiolia_event.cart WHERE expires_at < NOW();"

# Libération des réservations expirées (toutes les 5 min)
*/5 * * * * mysql_user mysql -u aiolia_app -p'password' -e "UPDATE aiolia_event.ticket_categories tc SET quantity_reserved = (SELECT COUNT(*) FROM aiolia_event.cart_items ci WHERE ci.ticket_category_id = tc.id);"

# Archivage des logs (chaque mois le 1er)
0 2 1 * * mysql_user mysql -u aiolia_app -p'password' -e "DELETE FROM aiolia_event.audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);"

# Optimisation des tables (chaque dimanche à 3h)
0 3 * * 0 mysql_user mysql -u aiolia_app -p'password' -e "OPTIMIZE TABLE aiolia_event.events, aiolia_event.tickets, aiolia_event.orders, aiolia_event.notifications;"
```

### Backup Automatique

```bash
#!/bin/bash
# Fichier : /usr/local/bin/backup-aiolia-db.sh

BACKUP_DIR="/var/backups/aiolia-event"
DATE=$(date +%Y%m%d_%H%M%S)
FILENAME="aiolia_event_${DATE}.sql.gz"

# Créer le répertoire si nécessaire
mkdir -p $BACKUP_DIR

# Backup complet
mysqldump \
  --user=aiolia_admin \
  --password=admin_password \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  aiolia_event | gzip > "${BACKUP_DIR}/${FILENAME}"

# Garder seulement les 30 derniers jours
find $BACKUP_DIR -name "aiolia_event_*.sql.gz" -mtime +30 -delete

echo "Backup créé : ${FILENAME}"
```

Ajoutez au crontab :
```bash
# Backup quotidien à 2h du matin
0 2 * * * /usr/local/bin/backup-aiolia-db.sh
```

---

## 📈 Monitoring

### Requêtes Utiles

```sql
-- Taille de la base de données
SELECT 
    table_schema AS "Database",
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS "Size (MB)"
FROM information_schema.tables
WHERE table_schema = 'aiolia_event'
GROUP BY table_schema;

-- Taille par table
SELECT 
    table_name AS "Table",
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)",
    table_rows AS "Rows"
FROM information_schema.tables
WHERE table_schema = 'aiolia_event'
ORDER BY (data_length + index_length) DESC;

-- Index non utilisés (après quelques semaines de prod)
SELECT 
    object_schema AS database_name,
    object_name AS table_name,
    index_name
FROM performance_schema.table_io_waits_summary_by_index_usage
WHERE index_name IS NOT NULL
  AND count_star = 0
  AND object_schema = 'aiolia_event'
ORDER BY object_name;

-- Requêtes les plus lentes
SELECT 
    ROUND(avg_timer_wait / 1000000000000, 2) AS avg_time_sec,
    count_star AS calls,
    digest_text AS query
FROM performance_schema.events_statements_summary_by_digest
WHERE schema_name = 'aiolia_event'
ORDER BY avg_timer_wait DESC
LIMIT 20;

-- Connexions actives
SELECT 
    user,
    host,
    db,
    command,
    time,
    state,
    info
FROM information_schema.processlist
WHERE db = 'aiolia_event'
ORDER BY time DESC;
```

### Métriques à Surveiller

1. **Espace disque** : La BDD peut grossir rapidement
2. **Temps de réponse** : Requêtes > 1 seconde
3. **Connexions** : Nombre de connexions actives
4. **Locks** : Tables verrouillées trop longtemps
5. **Réplication** : Si configurée, vérifier le lag

---

## 🧪 Tests

### Tester les Procédures Stockées

```sql
-- Test : Vérifier disponibilité d'un billet
CALL check_ticket_availability(1, 5, @available, @remaining);
SELECT @available, @remaining;

-- Test : Créer une commande depuis le panier
CALL create_order_from_cart(1, 'BIENVENUE2025', @order_id, @success, @message);
SELECT @order_id, @success, @message;

-- Test : Scanner un billet
CALL checkin_ticket('AIOLIA-123456-TKT-000001-000001', 2, @success, @message);
SELECT @success, @message;
```

### Seed Data pour Tests

Le fichier `seeds.sql` contient déjà des données de test :

- **Utilisateurs** : `admin@aiolia-event.com`, `organizer@aiolia-event.com`, `user@aiolia-event.com`
- **Mot de passe** : `Password123!` (à changer en production !)
- **Événement de démo** : Festival de Musique Malagasy 2025
- **Codes promo** : `BIENVENUE2025`, `EARLY50`, `FIDELE5000`

---

## 🔐 Sécurité

### Checklist Sécurité

- [x] Mots de passe hashés avec bcrypt (min 10 rounds)
- [x] Tokens JWT avec expiration (15 min)
- [x] Refresh tokens révocables
- [x] Aucune donnée sensible en clair (CB, etc.)
- [x] Logs d'audit complets
- [x] Connexions SSL/TLS obligatoires
- [x] Permissions utilisateurs restrictives
- [x] Input validation côté serveur
- [x] Rate limiting sur API
- [x] Protection contre SQL injection (requêtes préparées)

### Configuration SSL

```ini
[mysqld]
require_secure_transport=ON
ssl-ca=/path/to/ca.pem
ssl-cert=/path/to/server-cert.pem
ssl-key=/path/to/server-key.pem
```

---

## 🌍 Multi-Langue

Le système supporte plusieurs langues via la table `translations`.

### Ajouter une traduction

```sql
-- Traduire le titre d'un événement
INSERT INTO translations (
    entity_type,
    entity_id,
    field_name,
    language,
    translated_value
) VALUES (
    'event',
    1,
    'title',
    'en',
    'Malagasy Music Festival 2025'
);
```

### Récupérer le contenu traduit

```sql
-- Événement en anglais
SELECT 
    e.id,
    COALESCE(t_title.translated_value, e.title) AS title,
    COALESCE(t_desc.translated_value, e.description) AS description
FROM events e
LEFT JOIN translations t_title ON 
    t_title.entity_type = 'event' 
    AND t_title.entity_id = e.id 
    AND t_title.field_name = 'title'
    AND t_title.language = 'en'
LEFT JOIN translations t_desc ON 
    t_desc.entity_type = 'event' 
    AND t_desc.entity_id = e.id 
    AND t_desc.field_name = 'description'
    AND t_desc.language = 'en'
WHERE e.id = 1;
```

---

## 📚 Ressources

### Documentation

- [Conception SQL Complète](CONCEPTION_SQL.md) - Analyse détaillée de l'architecture
- [Index & Optimisation](indexes_optimization.sql) - Guide d'optimisation des performances

### Outils Recommandés

- **phpMyAdmin** : Interface web pour gérer la BDD
- **MySQL Workbench** : Modélisation et gestion visuelle
- **DBeaver** : Client SQL multi-plateforme
- **Adminer** : Alternative légère à phpMyAdmin
- **pt-query-digest** : Analyse des slow queries (Percona Toolkit)

### Liens Utiles

- [MySQL Documentation](https://dev.mysql.com/doc/)
- [MariaDB Knowledge Base](https://mariadb.com/kb/en/)
- [SQL Performance Explained](https://use-the-index-luke.com/)

---

## 🐛 Troubleshooting

### Problème : "Too many connections"

```sql
-- Augmenter le nombre de connexions max
SET GLOBAL max_connections = 500;

-- Identifier les connexions qui ne se ferment pas
SELECT * FROM information_schema.processlist;
```

### Problème : "Table is marked as crashed"

```sql
-- Réparer une table
REPAIR TABLE events;
```

### Problème : "Deadlock found"

```sql
-- Voir les transactions en cours
SELECT * FROM information_schema.innodb_trx;

-- Tuer une transaction problématique
KILL <trx_mysql_thread_id>;
```

### Problème : Requêtes lentes

```sql
-- Activer le slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;

-- Analyser une requête avec EXPLAIN
EXPLAIN SELECT * FROM events WHERE status = 'published';
```

---

## 📞 Support

Pour toute question ou problème :

- 📧 **Email** : dev@aiolia-event.com
- 🐛 **Issues** : https://github.com/aiolia-event/issues
- 📚 **Wiki** : https://github.com/aiolia-event/wiki

---

## 📄 Licence

© 2025 Aiolia Event. Tous droits réservés.

---

**Dernière mise à jour** : Octobre 2025  
**Version** : 1.0.0

