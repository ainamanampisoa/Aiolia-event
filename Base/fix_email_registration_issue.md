# Résolution du problème d'inscription avec l'email fifalianavalea@gmail.com

## Problème

L'email `fifalianavalea@gmail.com` ne peut pas être utilisé pour créer un utilisateur car :
1. L'email existe déjà dans la base de données, OU
2. La contrainte UNIQUE sur l'email empêche la création d'un doublon

## Solutions

### Solution 1: Vérifier si l'utilisateur existe déjà

```bash
psql -h localhost -U aiolia_user -d aiolia_event -f Base/check_user_email.sql
```

### Solution 2: Supprimer l'utilisateur existant (si vous voulez le recréer)

**⚠️ ATTENTION: Cette opération supprime toutes les données associées à l'utilisateur**

```bash
psql -h localhost -U aiolia_user -d aiolia_event -f Base/delete_user_if_exists.sql
```

### Solution 3: Ajouter la contrainte UNIQUE sur l'email (si elle n'existe pas)

Si Doctrine a créé une contrainte UNIQUE mais qu'elle n'est pas dans le schéma SQL :

```bash
psql -h localhost -U aiolia_user -d aiolia_event -f Base/add_email_unique_constraint.sql
```

### Solution 4: Utiliser un autre email

Si vous voulez simplement tester l'inscription, utilisez un autre email, par exemple :
- `fifalianavalea2@gmail.com`
- `test.fifalianavalea@gmail.com`
- `fifalianavalea+test@gmail.com` (Gmail supporte les alias avec `+`)

## Messages d'erreur possibles

1. **"Cette adresse email est déjà utilisée."**
   - L'utilisateur existe déjà dans la base de données
   - Solution: Supprimer l'utilisateur existant ou utiliser un autre email

2. **Erreur de contrainte UNIQUE en base de données**
   - La contrainte UNIQUE sur l'email empêche la création
   - Solution: Vérifier qu'il n'y a pas de doublon, supprimer l'utilisateur existant

3. **Contrainte UNIQUE sur (first_name, last_name)**
   - Si vous essayez de créer un utilisateur avec le même nom et prénom qu'un utilisateur existant
   - Solution: Utiliser des noms différents

## Notes importantes

- Le champ `email` dans la table `users` utilise le type `CITEXT` (case-insensitive), donc `FifalianaValea@Gmail.com` et `fifalianavalea@gmail.com` sont considérés comme identiques
- L'entité User dans Doctrine a `unique: true` sur l'email, ce qui crée automatiquement une contrainte UNIQUE lors des migrations
- La méthode `normalizeEmail()` dans AuthService convertit tous les emails en minuscules avant vérification


