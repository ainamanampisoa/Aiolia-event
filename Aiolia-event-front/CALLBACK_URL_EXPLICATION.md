# 📞 Explication : Callback URL MVola

## ⚠️ Important : Ne vous inquiétez pas !

Le champ **Callback URL** dans l'interface OAuth MVola que vous voyez est **pour le flux OAuth**, pas pour MerchantPay.

## 🔄 Comment fonctionne le Callback pour MerchantPay ?

Pour l'API **MerchantPay**, le callback fonctionne différemment :

### 1. Le Callback URL est envoyé dans la requête

Quand vous initiez une transaction MerchantPay, vous envoyez le **Callback URL** dans le **header** `X-Callback-URL` de la requête, **pas** dans la configuration OAuth.

```php
// Dans votre code, le Callback URL est envoyé comme ceci :
$headers = [
    'X-Callback-URL' => 'http://localhost:8000/api/mvola/callback',
    // ... autres headers
];
```

### 2. Vous n'avez pas besoin de le configurer dans l'interface OAuth

Le Callback URL dans l'interface OAuth (`http://url-to-webapp`) est pour un autre type de flux (authorization code flow). Vous pouvez le laisser tel quel.

## ✅ Ce que vous devez faire

### 1. Créer le fichier `.env.local`

Ajoutez simplement le Callback URL dans votre configuration :

```bash
MVOLA_CALLBACK_URL=http://localhost:8000/api/mvola/callback
```

### 2. Le service l'utilisera automatiquement

Le service `MvolaPaymentClient` enverra automatiquement ce Callback URL dans le header `X-Callback-URL` lors de l'initiation d'une transaction.

### 3. Les endpoints sont déjà créés

J'ai créé le contrôleur `MvolaController` avec les endpoints :
- `/api/mvola/callback` - Pour recevoir les callbacks MVola
- `/api/mvola/webhook` - Alternative (webhook)
- `/api/mvola/status/{serverCorrelationId}` - Pour vérifier le statut (polling)

## 📋 Résumé

| Élément | Où le configurer | Utilisation |
|---------|------------------|-------------|
| **Callback URL OAuth** | Interface MVola (peut rester `http://url-to-webapp`) | Flux OAuth (non utilisé pour MerchantPay) |
| **Callback URL MerchantPay** | Fichier `.env.local` (`MVOLA_CALLBACK_URL`) | Envoyé dans le header `X-Callback-URL` lors de l'initiation |

## 🎯 Action requise

**Aucune action dans l'interface MVola n'est nécessaire !**

Il vous suffit de :
1. ✅ Créer le fichier `.env.local` avec `MVOLA_CALLBACK_URL`
2. ✅ Les endpoints sont déjà créés et prêts à recevoir les callbacks

## 🔍 Pour tester en local

Si vous testez en local, vous devrez utiliser un service comme **ngrok** pour exposer votre serveur local :

```bash
# Installer ngrok
# Puis :
ngrok http 8000

# Utiliser l'URL fournie par ngrok dans MVOLA_CALLBACK_URL
# Exemple : https://abc123.ngrok.io/api/mvola/callback
```

Mais pour le moment, vous pouvez simplement utiliser `http://localhost:8000/api/mvola/callback` dans votre `.env.local`.

