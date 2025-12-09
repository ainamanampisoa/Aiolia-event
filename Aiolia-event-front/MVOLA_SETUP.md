# Configuration MVola - Guide de Setup

## 📋 Variables d'environnement requises

Créez un fichier `.env.local` dans `Aiolia-event-front/` avec les variables suivantes :

```bash
# MVola API Configuration - SANDBOX
MVOLA_CONSUMER_KEY=votre_consumer_key_ici
MVOLA_CONSUMER_SECRET=votre_consumer_secret_ici

# Informations du marchand
MVOLA_PARTNER_MSISDN=0340017983
MVOLA_PARTNER_NAME=VotreEntreprise

# URLs API
MVOLA_BASE_URL=https://devapi.mvola.mg
MVOLA_AUTH_URL=https://devapi.mvola.mg/oauth/token

# URLs de callback (à adapter selon votre domaine)
MVOLA_CALLBACK_URL=http://localhost:8000/api/mvola/callback
MVOLA_WEBHOOK_URL=http://localhost:8000/api/mvola/webhook

# Environnement
MVOLA_ENVIRONMENT=sandbox
```

## 🔑 Où trouver vos credentials ?

1. **Consumer Key** et **Consumer Secret** : Vous les avez déjà obtenus ✅
2. **Partner MSISDN** : Numéro de téléphone MVola de votre compte marchand
3. **Partner Name** : Nom de votre entreprise/partenaire

## 📝 Instructions

1. Copiez les variables ci-dessus dans votre `.env.local`
2. Remplacez les valeurs par vos vraies credentials
3. Redémarrez votre serveur Symfony après modification

## ⚠️ Important

- Ne commitez JAMAIS le fichier `.env.local` (il est déjà dans `.gitignore`)
- Utilisez le sandbox pour tous les tests
- Ne passez en production qu'après validation complète

