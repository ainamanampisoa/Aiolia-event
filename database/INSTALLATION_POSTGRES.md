# Installation du système de validation (PostgreSQL)

## Méthode 1 : Exécuter directement depuis psql

```bash
# Se connecter à la base de données
psql -U aiolia_user -d aiolia_event

# Dans psql, exécuter le fichier
\i /home/fifah/Documents/GitHub/Aiolia-event/database/user_validation_system_postgres.sql

# Ou bien, quitter psql et exécuter depuis le terminal
\q
```

## Méthode 2 : Exécuter depuis le terminal

```bash
cd /home/fifah/Documents/GitHub/Aiolia-event

# Exécuter la migration
psql -U aiolia_user -d aiolia_event -f database/user_validation_system_postgres.sql
```

## Vérification de l'installation

Une fois la migration exécutée, vérifiez que les tables ont été créées :

```sql
-- Lister toutes les tables
\dt

-- Voir la structure de la table users (avec le nouveau champ)
\d users

-- Voir la structure de user_validation_requests
\d user_validation_requests

-- Voir la structure de audit_logs
\d audit_logs

-- Vérifier que le log de migration a été inséré
SELECT * FROM audit_logs WHERE action = 'system_migration';
```

## Différences PostgreSQL vs MySQL

### Types de données
- `BIGINT AUTO_INCREMENT` → `BIGSERIAL`
- `JSON` → `JSONB` (plus performant en PostgreSQL)
- `ENGINE=InnoDB` → Non nécessaire (PostgreSQL)
- `CHARSET=utf8mb4` → Non nécessaire (PostgreSQL supporte UTF-8 nativement)

### Commentaires
- MySQL : `COMMENT 'texte'` directement dans la définition
- PostgreSQL : `COMMENT ON COLUMN table.column IS 'texte'` après création

### Fonctions JSON
- MySQL : `JSON_OBJECT()`, `JSON_ARRAY()`
- PostgreSQL : Syntaxe JSON native : `'{"key": "value"}'::jsonb`

## Dépannage

### Erreur : "column already exists"
Si `account_status` existe déjà :
```sql
ALTER TABLE users DROP COLUMN account_status;
-- Puis réexécuter la migration
```

### Erreur : "table already exists"
```sql
DROP TABLE IF EXISTS audit_logs CASCADE;
DROP TABLE IF EXISTS user_validation_requests CASCADE;
-- Puis réexécuter la migration
```

### Voir les erreurs détaillées
```sql
-- Activer le mode verbeux
\set VERBOSITY verbose
```

## Rollback (annuler la migration)

Si vous souhaitez annuler la migration :

```sql
-- Supprimer les tables
DROP TABLE IF EXISTS audit_logs CASCADE;
DROP TABLE IF EXISTS user_validation_requests CASCADE;

-- Supprimer la colonne account_status
ALTER TABLE users DROP COLUMN IF EXISTS account_status;

-- Supprimer les index
DROP INDEX IF EXISTS idx_account_status;
```

