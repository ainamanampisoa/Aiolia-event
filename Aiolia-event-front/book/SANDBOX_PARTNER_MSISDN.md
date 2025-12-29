# 📱 Partner MSISDN en Sandbox - Guide

## ❓ Question : Quel numéro utiliser pour MVOLA_PARTNER_MSISDN en sandbox ?

### ✅ Réponse courte

**Oui, vous pouvez utiliser votre propre numéro MVola** pour le sandbox, **MAIS** il y a quelques points à vérifier :

## 🔍 Options pour le sandbox

### Option 1 : Votre propre numéro MVola (Recommandé pour commencer)

**Utilisez votre numéro MVola personnel** (celui avec lequel vous vous êtes inscrit au portail développeur).

```bash
MVOLA_PARTNER_MSISDN=03412345678  # Votre numéro MVola
```

**Avantages :**
- ✅ Facile à obtenir (c'est votre numéro)
- ✅ Fonctionne généralement en sandbox
- ✅ Vous pouvez tester avec votre propre compte

**Inconvénients :**
- ⚠️ Assurez-vous que votre compte MVola est activé pour les transactions marchand

### Option 2 : Numéro de test fourni par MVola

Certains portails développeur fournissent un **numéro de test spécifique** pour le sandbox.

**Où le trouver :**
1. Dans l'interface développeur MVola
2. Dans la section "Sandbox" ou "Test Environment"
3. Dans la documentation sandbox
4. Dans un email de confirmation

**Si vous le trouvez :**
```bash
MVOLA_PARTNER_MSISDN=0340017983  # Numéro de test fourni par MVola
```

### Option 3 : Contacter le support MVola

Si vous n'êtes pas sûr, contactez le support MVola pour demander :
- Le **Partner MSISDN** à utiliser pour le sandbox
- Confirmation que votre numéro personnel peut être utilisé

## 🧪 Comment tester

### Test 1 : Avec votre numéro personnel

1. Utilisez votre numéro MVola dans `.env.local`
2. Essayez d'initier une transaction de test
3. Si ça fonctionne → ✅ Parfait !
4. Si erreur → Essayez l'Option 2 ou 3

### Test 2 : Vérifier dans l'interface MVola

1. Retournez dans l'interface développeur MVola
2. Cherchez dans :
   - **"Sandbox Configuration"**
   - **"Test Environment"**
   - **"Merchant Information"**
   - Un champ nommé **"Test MSISDN"** ou **"Sandbox MSISDN"**

## 📋 Configuration recommandée pour commencer

```bash
# MVola API Configuration - SANDBOX
MVOLA_CONSUMER_KEY=votre_consumer_key
MVOLA_CONSUMER_SECRET=votre_consumer_secret

# Utilisez votre numéro MVola personnel pour le sandbox
MVOLA_PARTNER_MSISDN=03412345678  # Remplacez par VOTRE numéro MVola

# Nom de votre application
MVOLA_PARTNER_NAME=AioliaEvent

# URLs
MVOLA_BASE_URL=https://devapi.mvola.mg
MVOLA_CALLBACK_URL=http://localhost:8000/api/mvola/callback
```

## ⚠️ Points importants

### 1. Format du numéro

Le numéro doit être au format :
- ✅ `03412345678` (sans espaces, sans +)
- ✅ `034 12 34 56 78` (avec espaces - sera normalisé automatiquement)
- ❌ `+2613412345678` (ne pas utiliser le préfixe +261)

### 2. En sandbox vs production

- **Sandbox** : Vous pouvez utiliser votre numéro personnel ou un numéro de test
- **Production** : Vous DEVEZ utiliser le numéro marchand officiel fourni par MVola

### 3. Vérification

Si vous obtenez une erreur lors de l'initiation d'une transaction :
- Vérifiez que le numéro est correct
- Vérifiez que votre compte MVola est activé
- Contactez le support MVola si nécessaire

## 🎯 Recommandation

**Pour commencer rapidement :**

1. ✅ Utilisez votre propre numéro MVola dans `MVOLA_PARTNER_MSISDN`
2. ✅ Testez une transaction en sandbox
3. ✅ Si ça fonctionne → Parfait !
4. ✅ Si erreur → Contactez le support MVola pour obtenir le bon numéro

## 📞 Support MVola

Si vous avez des doutes, contactez le support MVola :
- **Email** : support@mvola.mg ou developer@mvola.mg
- **Demandez** : "Quel Partner MSISDN dois-je utiliser pour le sandbox ?"

