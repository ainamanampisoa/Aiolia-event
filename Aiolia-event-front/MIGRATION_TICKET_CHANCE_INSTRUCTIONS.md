# 🎲 Instructions - Migration Ticket Chance

## ⚠️ Problème Actuel

La page "Ticket Chance" (/profile/ticket-chance) génère une erreur car les colonnes nécessaires n'existent pas dans la base de données.

**Erreur** :
```
column "play_type" does not exist
```

## ✅ Solution Temporaire Appliquée

J'ai modifié le code pour qu'il fonctionne **en mode dégradé** sans ces colonnes :
- ✅ Les tirages gratuits fonctionnent
- ❌ Les tirages bonus (achat > 50k MGA) sont désactivés temporairement

## 🔧 Solution Complète : Exécuter la Migration SQL

### Option 1 : Via pgAdmin ou Interface Graphique

1. Ouvrez pgAdmin ou votre interface PostgreSQL
2. Connectez-vous à la base de données `aiolia_event`
3. Ouvrez un éditeur de requêtes
4. Copiez-collez le contenu du fichier :
   ```
   /home/aina/Documents/MyProject/Aiolia-event/Base/ticket_chance_schema_update.sql
   ```
5. Exécutez la requête

### Option 2 : Via Terminal (avec mot de passe sudo)

```bash
# Dans un terminal
cd /home/aina/Documents/MyProject/Aiolia-event

# Méthode 1 : Avec sudo
sudo -u postgres psql -d aiolia_event -f Base/ticket_chance_schema_update.sql

# Méthode 2 : Se connecter en tant que postgres puis exécuter
sudo su - postgres
psql -d aiolia_event -f /home/aina/Documents/MyProject/Aiolia-event/Base/ticket_chance_schema_update.sql
exit
```

### Option 3 : Exécution Manuelle des Commandes SQL

Si vous avez des problèmes de permissions, exécutez ces commandes SQL **une par une** :

```sql
-- 1. Se connecter à la base de données
\c aiolia_event

-- 2. Ajouter la colonne play_type
ALTER TABLE aiolia.ticket_chance_entries 
ADD COLUMN IF NOT EXISTS play_type VARCHAR(20) NOT NULL DEFAULT 'free';

-- 3. Ajouter la colonne order_id
ALTER TABLE aiolia.ticket_chance_entries 
ADD COLUMN IF NOT EXISTS order_id BIGINT REFERENCES aiolia.orders(id) ON DELETE SET NULL;

-- 4. Ajouter la colonne prize_code
ALTER TABLE aiolia.ticket_chance_entries 
ADD COLUMN IF NOT EXISTS prize_code VARCHAR(50);

-- 5. Ajouter la colonne promo_code
ALTER TABLE aiolia.ticket_chance_entries 
ADD COLUMN IF NOT EXISTS promo_code VARCHAR(50);

-- 6. Ajouter la colonne metadata
ALTER TABLE aiolia.ticket_chance_entries 
ADD COLUMN IF NOT EXISTS metadata JSONB DEFAULT '{}'::jsonb;

-- 7. Créer les index pour la performance
CREATE INDEX IF NOT EXISTS idx_ticket_chance_entries_user_date 
    ON aiolia.ticket_chance_entries(user_id, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_ticket_chance_entries_play_type 
    ON aiolia.ticket_chance_entries(user_id, play_type);

CREATE INDEX IF NOT EXISTS idx_ticket_chance_entries_order 
    ON aiolia.ticket_chance_entries(order_id) WHERE order_id IS NOT NULL;

-- 8. Vérifier que tout a été créé
SELECT 
    column_name, 
    data_type, 
    is_nullable
FROM information_schema.columns
WHERE table_schema = 'aiolia' 
  AND table_name = 'ticket_chance_entries'
ORDER BY ordinal_position;
```

### Option 4 : Script PHP Symfony (Alternative)

Créez un fichier `update_ticket_chance_schema.php` :

```php
<?php
// update_ticket_chance_schema.php
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$conn = pg_connect(
    "host=localhost port=5432 dbname=aiolia_event user=aiolia_user password=aiolia2025"
);

if (!$conn) {
    die("Connexion échouée\n");
}

$queries = [
    "ALTER TABLE aiolia.ticket_chance_entries ADD COLUMN IF NOT EXISTS play_type VARCHAR(20) NOT NULL DEFAULT 'free'",
    "ALTER TABLE aiolia.ticket_chance_entries ADD COLUMN IF NOT EXISTS order_id BIGINT REFERENCES aiolia.orders(id) ON DELETE SET NULL",
    "ALTER TABLE aiolia.ticket_chance_entries ADD COLUMN IF NOT EXISTS prize_code VARCHAR(50)",
    "ALTER TABLE aiolia.ticket_chance_entries ADD COLUMN IF NOT EXISTS promo_code VARCHAR(50)",
    "ALTER TABLE aiolia.ticket_chance_entries ADD COLUMN IF NOT EXISTS metadata JSONB DEFAULT '{}'::jsonb",
    "CREATE INDEX IF NOT EXISTS idx_ticket_chance_entries_user_date ON aiolia.ticket_chance_entries(user_id, created_at DESC)",
    "CREATE INDEX IF NOT EXISTS idx_ticket_chance_entries_play_type ON aiolia.ticket_chance_entries(user_id, play_type)",
    "CREATE INDEX IF NOT EXISTS idx_ticket_chance_entries_order ON aiolia.ticket_chance_entries(order_id) WHERE order_id IS NOT NULL"
];

foreach ($queries as $i => $query) {
    echo "Exécution requête " . ($i + 1) . "...\n";
    $result = pg_query($conn, $query);
    if ($result) {
        echo "✓ OK\n";
    } else {
        echo "✗ Erreur: " . pg_last_error($conn) . "\n";
    }
}

pg_close($conn);
echo "\nTerminé !\n";
```

Puis exécutez :
```bash
php update_ticket_chance_schema.php
```

## ✅ Vérification

Après avoir exécuté la migration, vérifiez que tout fonctionne :

```bash
# 1. Vider le cache Symfony
cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front
php bin/console cache:clear

# 2. Accéder à la page
# http://votre-domaine.local/profile/ticket-chance
```

## 🎯 Fonctionnalités Ajoutées après Migration

Une fois la migration terminée, vous aurez accès à :

✅ **Tirages gratuits** : 1 tous les 7 jours  
✅ **Tirages bonus** : 1 tirage gratuit pour chaque achat ≥ 50 000 MGA  
✅ **Historique détaillé** : Type de tirage (gratuit/bonus)  
✅ **Traçabilité** : Lien avec les commandes pour les bonus  
✅ **Métadonnées** : Informations supplémentaires en JSON  

## 📊 Structure de la Table Après Migration

```sql
ticket_chance_entries:
- id (bigint)
- user_id (bigint)
- event_id (bigint) - nullable
- prize_type (promotion_type_enum)
- prize_value (numeric)
- prize_code (varchar) - NEW
- promo_code (varchar) - NEW
- play_type (varchar) - NEW: 'free' ou 'bonus'
- order_id (bigint) - NEW: référence la commande
- metadata (jsonb) - NEW: données supplémentaires
- status (ticket_chance_status_enum)
- created_at (timestamp)
- claimed_at (timestamp) - nullable
```

## 🐛 Dépannage

### Erreur : "must be owner of table"
**Solution** : Utilisez l'utilisateur postgres ou un super-utilisateur
```bash
sudo -u postgres psql -d aiolia_event -f Base/ticket_chance_schema_update.sql
```

### Erreur : "Peer authentication failed"
**Solution** : Forcez la connexion TCP avec `-h localhost`
```bash
PGPASSWORD=aiolia2025 psql -h localhost -U aiolia_user -d aiolia_event -f Base/ticket_chance_schema_update.sql
```

### Table "promo_codes" n'existe pas
**Note** : C'est normal si vous n'utilisez pas cette table. Ignorez cette erreur, les colonnes principales seront quand même créées.

## 📞 Support

Si vous rencontrez des problèmes :
1. Vérifiez les logs PostgreSQL : `/var/log/postgresql/`
2. Vérifiez les logs Symfony : `var/log/dev.log`
3. Testez la connexion : `php bin/console dbal:run-sql "SELECT version()"`

---

**Date** : 31 Décembre 2025  
**Statut** : Mode dégradé activé (tirages gratuits fonctionnels)  
**Action requise** : Exécuter la migration SQL pour activer toutes les fonctionnalités

