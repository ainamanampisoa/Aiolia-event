# 🔧 Configuration MVola - Instructions

## ✅ Ce qui a été fait

1. ✅ Service `MvolaPaymentClient` mis à jour selon la documentation officielle
2. ✅ Configuration des services Symfony
3. ✅ Support de l'authentification OAuth
4. ✅ Méthodes pour initier, vérifier et récupérer les transactions

## 📝 Configuration requise

### Étape 1 : Créer le fichier `.env.local`

Créez un fichier `.env.local` dans le dossier `Aiolia-event-front/` avec le contenu suivant :

```bash
# MVola API Configuration - SANDBOX
MVOLA_CONSUMER_KEY=votre_consumer_key_ici
MVOLA_CONSUMER_SECRET=votre_consumer_secret_ici

# Informations du marchand
# En sandbox, vous pouvez utiliser votre propre numéro MVola
MVOLA_PARTNER_MSISDN=03412345678  # Votre numéro MVola personnel (pour sandbox)
MVOLA_PARTNER_NAME=AioliaEvent    # Nom de votre application

# URLs API
MVOLA_BASE_URL=https://devapi.mvola.mg
MVOLA_CALLBACK_URL=http://localhost:8000/api/mvola/callback
```

### Étape 2 : Remplir vos credentials

Remplacez les valeurs suivantes par vos vraies informations :

- **MVOLA_CONSUMER_KEY** : Votre Consumer Key (vous l'avez déjà ✅)
- **MVOLA_CONSUMER_SECRET** : Votre Consumer Secret (vous l'avez déjà ✅)
- **MVOLA_PARTNER_MSISDN** : Le numéro de téléphone MVola de votre compte marchand
- **MVOLA_PARTNER_NAME** : Le nom de votre entreprise/partenaire

### 🔍 Où trouver Partner MSISDN et Partner Name ?

Ces informations ne sont **pas toujours visibles** dans l'interface développeur. Voici comment les obtenir :

#### Option 1 : Dans l'interface MVola
1. Retournez à la page principale de votre application
2. Cherchez dans les onglets :
   - **"Details"** ou **"Application Details"**
   - **"Settings"** ou **"Configuration"**
   - **"Merchant Information"** ou **"Account Information"**

#### Option 2 : Utiliser des valeurs de test (Sandbox)
Pour le **sandbox**, vous pouvez utiliser :
- **MVOLA_PARTNER_MSISDN** : Votre propre numéro MVola (celui avec lequel vous vous êtes inscrit)
- **MVOLA_PARTNER_NAME** : Le nom de votre application ou entreprise (ex: `AioliaEvent`)

#### Option 3 : Contacter le support MVola
Si vous ne trouvez pas ces informations, contactez le support MVola pour obtenir :
- Le **Partner MSISDN** (numéro marchand)
- Le **Partner Name** officiel

### Étape 3 : Configurer le Callback URL dans l'interface MVola

Dans l'interface MVola (section que vous voyez actuellement) :

1. Trouvez le champ **"Callback URL"**
2. Remplacez `http://url-to-webapp` par votre URL de callback :
   ```
   http://localhost:8000/api/mvola/callback
   ```
   (Pour la production, utilisez votre domaine réel)

3. **Sauvegardez** les modifications

### Étape 3 : Redémarrer le serveur

Après avoir créé/modifié le fichier `.env.local`, redémarrez votre serveur Symfony :

```bash
# Arrêter le serveur
symfony server:stop

# Redémarrer
symfony server:start -d
```

## 🧪 Tester la configuration

Une fois configuré, vous pouvez tester l'intégration en utilisant le service `MvolaPaymentClient` dans votre code.

## 📚 Documentation

- Voir `MVOLA_SETUP.md` pour plus de détails
- Documentation API : `API_MerchantPay.pdf`

## ⚠️ Important

- Ne commitez JAMAIS le fichier `.env.local` (déjà dans `.gitignore`)
- Utilisez uniquement le sandbox pour les tests
- Ne passez en production qu'après validation complète

## 🔍 Vérification

Pour vérifier que la configuration est correcte, vous pouvez :

1. Vérifier que le fichier `.env.local` existe
2. Vérifier que toutes les variables sont définies
3. Tester une transaction en sandbox

