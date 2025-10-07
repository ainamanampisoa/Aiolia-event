# 🔄 Guide de Migration - Aiolia Event

Ce guide explique comment migrer et déployer la base de données Aiolia Event en production.

---

## 📋 Table des Matières

1. [Prérequis](#prérequis)
2. [Environnements](#environnements)
3. [Migration Initiale](#migration-initiale)
4. [Migrations Versionnées](#migrations-versionnées)
5. [Rollback](#rollback)
6. [Tests de Migration](#tests-de-migration)
7. [Checklist Pré-Production](#checklist-pré-production)
8. [Monitoring Post-Migration](#monitoring-post-migration)

---

## 🔧 Prérequis

### Serveur de Base de Données

- **MySQL** 8.0+ ou **MariaDB** 10.5+
- **RAM** : Minimum 4 GB (recommandé 8 GB+)
- **Disque** : Minimum 50 GB SSD
- **CPU** : Minimum 2 cores (recommandé 4+)

### Outils Nécessaires

```bash
# Installer MySQL client
sudo apt-get install mysql-client

# Installer Percona Toolkit (pour migrations)
sudo apt-get install percona-toolkit

# Installer pt-online-schema-change
# (pour migrations sans downtime)
```

### Accès Requis

- Accès SSH au serveur
- Utilisateur MySQL avec privilèges `ALL PRIVILEGES`
- Backups de la base existante (si applicable)

---

## 🌍 Environnements

### Structure Recommandée

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   LOCAL     │────▶│   STAGING   │────▶│ PRODUCTION  │
│ Development │     │   Testing   │     │    Live     │
└─────────────┘     └─────────────┘     └─────────────┘
```

### Configuration par Environnement

#### Local (Development)

```bash
DB_HOST=localhost
DB_PORT=3306
DB_NAME=aiolia_event_dev
DB_USER=root
DB_PASSWORD=local_password
```

#### Staging

```bash
DB_HOST=staging-db.aiolia-event.com
DB_PORT=3306
DB_NAME=aiolia_event_staging
DB_USER=aiolia_staging
DB_PASSWORD=staging_secure_password
```

#### Production

```bash
DB_HOST=prod-db.aiolia-event.com
DB_PORT=3306
DB_NAME=aiolia_event_prod
DB_USER=aiolia_prod
DB_PASSWORD=prod_ultra_secure_password
```

---

## 🚀 Migration Initiale

### Étape 1 : Créer la Base de Données

```bash
# Se connecter au serveur MySQL
mysql -h $DB_HOST -u root -p

# Créer la base de données
CREATE DATABASE aiolia_event_prod 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

# Créer l'utilisateur applicatif
CREATE USER 'aiolia_prod'@'%' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON aiolia_event_prod.* TO 'aiolia_prod'@'%';
FLUSH PRIVILEGES;

EXIT;
```

### Étape 2 : Exécuter les Scripts SQL

```bash
# Variables d'environnement
export DB_HOST="prod-db.aiolia-event.com"
export DB_NAME="aiolia_event_prod"
export DB_USER="root"
export DB_PASSWORD="root_password"

# 1. Schéma principal
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < schema.sql

# Vérifier
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "SHOW TABLES;"

# 2. Triggers
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < triggers.sql

# Vérifier
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "SHOW TRIGGERS;"

# 3. Procédures stockées
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < procedures.sql

# Vérifier
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "SHOW PROCEDURE STATUS WHERE Db='$DB_NAME';"

# 4. Données de base (SEULEMENT les données essentielles, PAS les données de test)
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < seeds_production.sql

# 5. Index d'optimisation
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < indexes_optimization.sql
```

### Étape 3 : Vérification Post-Migration

```bash
# Script de vérification
cat > verify_migration.sql << 'EOF'
-- Vérifier les tables
SELECT 
    COUNT(*) as total_tables,
    SUM(table_rows) as total_rows,
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
FROM information_schema.tables
WHERE table_schema = 'aiolia_event_prod';

-- Vérifier les triggers
SELECT COUNT(*) as total_triggers
FROM information_schema.triggers
WHERE trigger_schema = 'aiolia_event_prod';

-- Vérifier les procédures
SELECT COUNT(*) as total_procedures
FROM information_schema.routines
WHERE routine_schema = 'aiolia_event_prod'
  AND routine_type = 'PROCEDURE';

-- Vérifier les contraintes
SELECT COUNT(*) as total_foreign_keys
FROM information_schema.table_constraints
WHERE constraint_schema = 'aiolia_event_prod'
  AND constraint_type = 'FOREIGN KEY';
EOF

mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD < verify_migration.sql
```

**Résultats attendus** :
- ✅ 60+ tables créées
- ✅ 30+ triggers
- ✅ 15+ procédures stockées
- ✅ 100+ contraintes de clés étrangères

---

## 🔄 Migrations Versionnées

### Structure des Migrations

```
database/migrations/
├── V001__initial_schema.sql
├── V002__add_event_media.sql
├── V003__add_dynamic_pricing.sql
├── V004__add_wallet_system.sql
└── README.md
```

### Format de Nommage

```
V{VERSION}__{DESCRIPTION}.sql

Exemples :
- V001__initial_schema.sql
- V002__add_event_capacity.sql
- V003__modify_ticket_prices.sql
```

### Exemple de Migration

**Fichier : `V005__add_notification_channels.sql`**

```sql
-- ============================================================================
-- Migration V005 : Ajout de canaux de notification supplémentaires
-- Date : 2025-10-08
-- Auteur : Dev Team
-- Description : Ajoute support WhatsApp et Telegram aux notifications
-- ============================================================================

-- Vérifier que la migration n'a pas déjà été appliquée
SELECT COUNT(*) INTO @already_applied 
FROM information_schema.columns 
WHERE table_schema = 'aiolia_event_prod'
  AND table_name = 'notifications'
  AND column_name = 'channel'
  AND column_type LIKE "%whatsapp%";

-- Appliquer la migration seulement si nécessaire
SET @sql = IF(@already_applied = 0,
  "ALTER TABLE notifications 
   MODIFY COLUMN channel ENUM('email', 'push', 'sms', 'in_app', 'whatsapp', 'telegram') NOT NULL",
  "SELECT 'Migration already applied' as status");

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Mettre à jour les préférences de notification
ALTER TABLE notification_preferences 
ADD COLUMN whatsapp_enabled BOOLEAN DEFAULT FALSE AFTER sms_event_reminder,
ADD COLUMN telegram_enabled BOOLEAN DEFAULT FALSE AFTER whatsapp_enabled;

-- Log de la migration
INSERT INTO migration_history (version, description, applied_at)
VALUES ('V005', 'Add WhatsApp and Telegram notification channels', NOW());

-- ============================================================================
-- Rollback (à exécuter manuellement si nécessaire)
-- ============================================================================
/*
ALTER TABLE notifications 
MODIFY COLUMN channel ENUM('email', 'push', 'sms', 'in_app') NOT NULL;

ALTER TABLE notification_preferences 
DROP COLUMN whatsapp_enabled,
DROP COLUMN telegram_enabled;

DELETE FROM migration_history WHERE version = 'V005';
*/
```

### Table de Suivi des Migrations

```sql
-- Créer la table de suivi
CREATE TABLE IF NOT EXISTS migration_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    version VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    applied_by VARCHAR(100),
    execution_time_ms INT,
    status ENUM('success', 'failed', 'rolled_back') DEFAULT 'success',
    INDEX idx_version (version),
    INDEX idx_applied_at (applied_at)
);
```

### Appliquer une Migration

```bash
#!/bin/bash
# Script : apply_migration.sh

MIGRATION_FILE=$1
VERSION=$(echo $MIGRATION_FILE | grep -oP 'V\d+')
START_TIME=$(date +%s%3N)

echo "📦 Applying migration: $MIGRATION_FILE"
echo "🔢 Version: $VERSION"

# Backup avant migration
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME > "backup_before_${VERSION}_$(date +%Y%m%d_%H%M%S).sql"

# Appliquer la migration
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < $MIGRATION_FILE

if [ $? -eq 0 ]; then
    END_TIME=$(date +%s%3N)
    EXECUTION_TIME=$((END_TIME - START_TIME))
    
    echo "✅ Migration successful"
    echo "⏱️  Execution time: ${EXECUTION_TIME}ms"
    
    # Enregistrer dans l'historique
    mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "
        UPDATE migration_history 
        SET execution_time_ms = $EXECUTION_TIME, 
            applied_by = '$USER',
            status = 'success'
        WHERE version = '$VERSION';
    "
else
    echo "❌ Migration failed"
    exit 1
fi
```

### Migrations Sans Downtime

Pour les changements sur de grosses tables en production, utilisez `pt-online-schema-change` :

```bash
# Exemple : Ajouter une colonne sur la table events
pt-online-schema-change \
  --alter "ADD COLUMN new_field VARCHAR(255)" \
  --host=$DB_HOST \
  --user=$DB_USER \
  --password=$DB_PASSWORD \
  D=aiolia_event_prod,t=events \
  --execute \
  --no-drop-old-table \
  --chunk-size=1000 \
  --max-lag=5s \
  --progress=percentage,5
```

**Avantages** :
- ✅ Pas de lock de table
- ✅ Pas de downtime
- ✅ Rollback possible

---

## ⏪ Rollback

### Stratégie de Rollback

#### 1. Rollback Simple (dernière migration)

```bash
# Restaurer depuis le backup
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < backup_before_V005_20251008_143022.sql

# Marquer comme rolled back
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "
    UPDATE migration_history 
    SET status = 'rolled_back' 
    WHERE version = 'V005';
"
```

#### 2. Rollback avec Script Inverse

Chaque migration devrait inclure un script de rollback :

```sql
-- rollback/R005__rollback_notification_channels.sql

ALTER TABLE notifications 
MODIFY COLUMN channel ENUM('email', 'push', 'sms', 'in_app') NOT NULL;

ALTER TABLE notification_preferences 
DROP COLUMN whatsapp_enabled,
DROP COLUMN telegram_enabled;

UPDATE migration_history 
SET status = 'rolled_back' 
WHERE version = 'V005';
```

### Checklist Rollback

- [ ] Backup complet avant rollback
- [ ] Vérifier les dépendances (triggers, procédures)
- [ ] Tester le rollback en staging d'abord
- [ ] Communiquer avec l'équipe
- [ ] Monitorer après rollback

---

## 🧪 Tests de Migration

### Test en Environnement Isolé

```bash
# 1. Créer une copie de la base de prod
mysqldump -h prod-db -u root -p aiolia_event_prod > prod_dump.sql

# 2. Créer une base de test
mysql -h test-db -u root -p -e "CREATE DATABASE aiolia_event_test CHARACTER SET utf8mb4;"

# 3. Importer
mysql -h test-db -u root -p aiolia_event_test < prod_dump.sql

# 4. Appliquer la migration
mysql -h test-db -u root -p aiolia_event_test < migrations/V005__add_notification_channels.sql

# 5. Vérifier
mysql -h test-db -u root -p aiolia_event_test -e "DESCRIBE notifications;"
```

### Tests Automatisés

```bash
#!/bin/bash
# test_migration.sh

set -e  # Arrêter en cas d'erreur

echo "🧪 Testing migration: $1"

# Créer une base temporaire
TEST_DB="aiolia_test_$(date +%s)"
mysql -h localhost -u root -p$MYSQL_ROOT_PASSWORD -e "CREATE DATABASE $TEST_DB CHARACTER SET utf8mb4;"

# Appliquer le schéma
mysql -h localhost -u root -p$MYSQL_ROOT_PASSWORD $TEST_DB < schema.sql
mysql -h localhost -u root -p$MYSQL_ROOT_PASSWORD $TEST_DB < triggers.sql
mysql -h localhost -u root -p$MYSQL_ROOT_PASSWORD $TEST_DB < procedures.sql

# Appliquer la migration à tester
mysql -h localhost -u root -p$MYSQL_ROOT_PASSWORD $TEST_DB < $1

# Tests de validation
echo "✅ Running validation tests..."

# Test 1 : Vérifier la structure
mysql -h localhost -u root -p$MYSQL_ROOT_PASSWORD $TEST_DB -e "SHOW TABLES;" > /dev/null

# Test 2 : Vérifier les contraintes
CONSTRAINTS=$(mysql -h localhost -u root -p$MYSQL_ROOT_PASSWORD $TEST_DB -e "
    SELECT COUNT(*) FROM information_schema.table_constraints 
    WHERE constraint_schema = '$TEST_DB' AND constraint_type = 'FOREIGN KEY';
" -sN)

if [ $CONSTRAINTS -lt 50 ]; then
    echo "❌ Foreign key constraints check failed"
    exit 1
fi

# Test 3 : Tester les procédures
mysql -h localhost -u root -p$MYSQL_ROOT_PASSWORD $TEST_DB -e "CALL check_ticket_availability(1, 5, @a, @r);" 2>/dev/null || true

# Nettoyage
mysql -h localhost -u root -p$MYSQL_ROOT_PASSWORD -e "DROP DATABASE $TEST_DB;"

echo "✅ All tests passed"
```

---

## ✅ Checklist Pré-Production

### 1 Semaine Avant

- [ ] **Backup complet** de la base de données actuelle (si migration existante)
- [ ] **Test de la migration** en environnement staging
- [ ] **Validation** par l'équipe QA
- [ ] **Documentation** mise à jour
- [ ] **Plan de rollback** préparé et testé

### 3 Jours Avant

- [ ] **Communication** avec les utilisateurs (si downtime)
- [ ] **Fenêtre de maintenance** planifiée
- [ ] **Monitoring** préparé (Grafana, DataDog, etc.)
- [ ] **Scripts de migration** revus par un senior dev

### Jour J - Avant Migration

- [ ] **Backup final** juste avant migration
- [ ] **Vérification de l'espace disque** (min 30% libre)
- [ ] **Mode maintenance** activé (si nécessaire)
- [ ] **Logs MySQL** activés pour troubleshooting

### Jour J - Pendant Migration

- [ ] **Timer démarré** pour tracking
- [ ] **Exécution étape par étape** avec validation
- [ ] **Monitoring en temps réel** des métriques
- [ ] **Communication régulière** sur le progress

### Jour J - Après Migration

- [ ] **Tests de fumée** (smoke tests)
- [ ] **Vérification des données** (intégrité)
- [ ] **Performance check** (requêtes principales)
- [ ] **Mode maintenance désactivé**
- [ ] **Communication succès** à l'équipe

### 24h Après

- [ ] **Monitoring intensif** des erreurs
- [ ] **Vérification des logs** d'application
- [ ] **Feedback utilisateurs** collecté
- [ ] **Backup post-migration** créé

---

## 📊 Monitoring Post-Migration

### Métriques Critiques à Surveiller

```sql
-- 1. Taille de la base de données
SELECT 
    table_schema,
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
FROM information_schema.tables
WHERE table_schema = 'aiolia_event_prod';

-- 2. Requêtes lentes
SELECT 
    ROUND(avg_timer_wait / 1000000000000, 2) AS avg_time_sec,
    count_star AS calls,
    LEFT(digest_text, 100) AS query
FROM performance_schema.events_statements_summary_by_digest
WHERE schema_name = 'aiolia_event_prod'
ORDER BY avg_timer_wait DESC
LIMIT 10;

-- 3. Erreurs de contraintes
SELECT 
    COUNT(*) as constraint_errors
FROM information_schema.innodb_trx
WHERE trx_state = 'LOCK WAIT';

-- 4. Connexions actives
SELECT 
    COUNT(*) as active_connections,
    SUM(CASE WHEN command = 'Sleep' THEN 1 ELSE 0 END) as sleeping
FROM information_schema.processlist;
```

### Dashboard de Monitoring

Créer un dashboard avec :

- **CPU & RAM** du serveur MySQL
- **IOPS** (lectures/écritures disque)
- **Temps de réponse** des requêtes principales
- **Nombre de connexions** actives
- **Taux d'erreur** des transactions
- **Espace disque** disponible

### Alertes Critiques

```bash
# Exemple avec Prometheus + Alertmanager

# Alert 1 : Espace disque < 20%
- alert: LowDiskSpace
  expr: (node_filesystem_avail_bytes / node_filesystem_size_bytes) < 0.2
  for: 5m
  
# Alert 2 : Trop de connexions
- alert: TooManyConnections
  expr: mysql_global_status_threads_connected > 400
  for: 2m

# Alert 3 : Requêtes lentes
- alert: SlowQueries
  expr: rate(mysql_global_status_slow_queries[1m]) > 10
  for: 5m
```

---

## 🆘 Troubleshooting

### Problème : Migration échoue à mi-chemin

```bash
# 1. Vérifier l'erreur exacte
tail -f /var/log/mysql/error.log

# 2. Restaurer depuis le backup
mysql -h $DB_HOST -u root -p $DB_NAME < backup_before_migration.sql

# 3. Corriger le script de migration

# 4. Réessayer
```

### Problème : Performance dégradée après migration

```bash
# 1. Reconstruire les statistiques
ANALYZE TABLE events, tickets, orders;

# 2. Optimiser les tables
OPTIMIZE TABLE events, tickets, orders;

# 3. Vérifier les index manquants
SHOW INDEX FROM events;
```

### Problème : Données inconsistantes

```sql
-- Vérifier l'intégrité référentielle
SELECT 
    t.table_name,
    COUNT(*) as orphaned_rows
FROM information_schema.tables t
LEFT JOIN information_schema.key_column_usage k ON t.table_name = k.table_name
WHERE t.table_schema = 'aiolia_event_prod'
GROUP BY t.table_name;
```

---

## 📞 Support

En cas de problème lors d'une migration :

1. **Ne pas paniquer** - Les backups sont là pour ça
2. **Documenter l'erreur** - Logs complets
3. **Contacter** : migration-support@aiolia-event.com
4. **Slack** : #db-migrations (si urgent)

---

## 📚 Ressources

- [Flyway Documentation](https://flywaydb.org/documentation/)
- [Liquibase Best Practices](https://www.liquibase.org/get-started/best-practices)
- [Percona Toolkit](https://www.percona.com/doc/percona-toolkit/)
- [MySQL Migration Guide](https://dev.mysql.com/doc/refman/8.0/en/upgrading.html)

---

**Dernière mise à jour** : Octobre 2025  
**Version** : 1.0.0  
**Auteur** : Équipe DevOps Aiolia Event

