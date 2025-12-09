# 📋 Configuration du fichier .env - Aiolia Event Front

## 📝 Instructions

1. Créez un fichier `.env.local` dans le dossier `Aiolia-event-front/`
2. Copiez le contenu ci-dessous dans ce fichier
3. Remplacez les valeurs par vos vraies credentials
4. Redémarrez votre serveur Symfony après modification

## 🔐 Contenu du fichier .env.local

```bash
# ============================================================================
# Configuration Symfony - Aiolia Event Front
# ============================================================================

# Environnement de l'application
APP_ENV=dev
APP_SECRET=change_this_secret_key_in_production_min_32_characters
APP_DEBUG=true

# ============================================================================
# Configuration Base de Données
# ============================================================================
# Format: postgresql://[user[:password]@][host][:port][/dbname][?param=value&param=value]
# Exemple pour PostgreSQL local:
DATABASE_URL=postgresql://username:password@127.0.0.1:5432/aiolia_event?serverVersion=16&charset=utf8

# ============================================================================
# Configuration JWT (JSON Web Token)
# ============================================================================
# Générer avec: openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
# Puis: openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_jwt_passphrase_here

# ============================================================================
# Configuration Email (Mailer)
# ============================================================================
# Format DSN: smtp://[user[:password]@]host[:port][?encryption=tls&auth_mode=login]
# Exemple Gmail: smtp://username:password@smtp.gmail.com:587?encryption=tls&auth_mode=login
# Exemple Mailtrap (dev): smtp://username:password@smtp.mailtrap.io:2525?encryption=tls
MAILER_DSN=smtp://localhost:1025

# Adresse email expéditeur
MAIL_FROM_ADDRESS=noreply@aiolia-event.mg
MAIL_FROM_NAME=Aiolia Event

# ============================================================================
# Configuration MVola Payment
# ============================================================================
# API Configuration - SANDBOX
MVOLA_CONSUMER_KEY=votre_consumer_key_ici
MVOLA_CONSUMER_SECRET=votre_consumer_secret_ici

# Informations du marchand
# Format du numéro: 034xxxxxxx (sans espaces, sans +261)
# IMPORTANT: Utilisez le format exact comme dans Postman (ex: 0382795455)
MVOLA_PARTNER_MSISDN=0382795455
MVOLA_PARTNER_NAME=AioliaEvent

# URLs API
MVOLA_BASE_URL=https://devapi.mvola.mg
MVOLA_AUTH_URL=https://devapi.mvola.mg/token

# URLs de callback (à adapter selon votre domaine)
# Pour développement local:
MVOLA_CALLBACK_URL=http://localhost:8000/api/mvola/callback
# Pour production, remplacer par votre domaine:
# MVOLA_CALLBACK_URL=https://votre-domaine.com/api/mvola/callback

# Environnement
MVOLA_ENVIRONMENT=sandbox

# ============================================================================
# Configuration Cloudinary (si utilisé)
# ============================================================================
# CLOUDINARY_URL=cloudinary://api_key:api_secret@cloud_name
```

## 🔑 Variables MVola - Détails

### Variables requises pour Mvola :

1. **MVOLA_CONSUMER_KEY** : Votre Consumer Key obtenue depuis l'interface MVola
2. **MVOLA_CONSUMER_SECRET** : Votre Consumer Secret obtenue depuis l'interface MVola
3. **MVOLA_PARTNER_MSISDN** : Numéro de téléphone MVola du compte marchand
   - Format: `0382795455` (sans espaces, sans +261, sans 0 au début si déjà présent)
   - C'est le numéro qui reçoit les paiements
4. **MVOLA_PARTNER_NAME** : Nom de votre entreprise/partenaire (ex: `AioliaEvent`)
5. **MVOLA_BASE_URL** : URL de l'API MVola
   - Sandbox: `https://devapi.mvola.mg`
   - Production: `https://api.mvola.mg`
6. **MVOLA_CALLBACK_URL** : URL où MVola enverra les notifications de paiement
   - Dev: `http://localhost:8000/api/mvola/callback`
   - Prod: `https://votre-domaine.com/api/mvola/callback`

## ⚠️ Important

- ✅ Le fichier `.env.local` est dans `.gitignore` et ne sera **PAS** commité
- ✅ Ne commitez **JAMAIS** vos credentials réels
- ✅ Utilisez le **sandbox** pour tous les tests
- ✅ Ne passez en **production** qu'après validation complète
- ✅ Redémarrez le serveur après modification: `symfony server:stop && symfony server:start -d`

## 🔍 Vérification

Pour vérifier que votre configuration est correcte :

1. Vérifiez que le fichier `.env.local` existe
2. Vérifiez que toutes les variables MVola sont définies
3. Testez une transaction en sandbox
4. Consultez les logs dans `var/log/dev.log` en cas d'erreur



