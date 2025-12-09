# 🧪 Guide de Test - Intégration MVola

## ✅ Configuration terminée !

Félicitations ! Vous avez terminé la configuration de l'intégration MVola. Voici comment tester.

## 📋 Checklist avant de tester

- [x] Fichier `.env.local` créé avec vos credentials
- [x] Table `payment_transactions` créée (exécuter la migration SQL)
- [x] Service `MvolaPaymentClient` configuré
- [x] Endpoints de callback créés
- [x] Intégration dans `PaymentService` terminée

## 🔧 Étape 1 : Créer la table payment_transactions

Exécutez la migration SQL :

```bash
cd Aiolia-event-front
psql -U votre_user -d votre_database -f migrations/create_payment_transactions_table.sql
```

Ou via votre client SQL préféré, exécutez le contenu du fichier :
`migrations/create_payment_transactions_table.sql`

## 🚀 Étape 2 : Redémarrer le serveur

```bash
cd Aiolia-event-front
symfony server:stop
symfony server:start -d
```

## 🧪 Étape 3 : Tester l'intégration

### Test 1 : Vérifier la configuration

Vérifiez que votre `.env.local` contient bien toutes les variables :

```bash
# Dans Aiolia-event-front/
cat .env.local | grep MVOLA
```

Vous devriez voir :
- `MVOLA_CONSUMER_KEY=...`
- `MVOLA_CONSUMER_SECRET=...`
- `MVOLA_PARTNER_MSISDN=...`
- `MVOLA_PARTNER_NAME=...`
- `MVOLA_BASE_URL=https://devapi.mvola.mg`
- `MVOLA_CALLBACK_URL=http://localhost:8000/api/mvola/callback`

### Test 2 : Tester l'authentification MVola

Créez un script de test simple ou utilisez le contrôleur pour vérifier que l'authentification fonctionne.

### Test 3 : Tester un paiement complet

1. **Connectez-vous** à votre application
2. **Ajoutez des billets** au panier
3. **Allez au paiement** (`/checkout/payment`)
4. **Remplissez le formulaire** :
   - Méthode de paiement : **M-Vola**
   - Nom et prénom
   - Email
   - **Numéro de téléphone MVola** (important !)
5. **Confirmez le paiement**

### Test 4 : Vérifier le flux

Après avoir initié le paiement :

1. **Vérifiez la base de données** :
   ```sql
   SELECT * FROM aiolia.payment_transactions ORDER BY created_at DESC LIMIT 1;
   SELECT * FROM aiolia.orders WHERE status = 'awaiting_payment' ORDER BY created_at DESC LIMIT 1;
   ```

2. **Vérifiez les logs** :
   ```bash
   tail -f var/log/dev.log | grep -i mvola
   ```

3. **Attendez le callback** MVola (ou utilisez le polling)

## 📊 Endpoints disponibles

### Callback MVola
- **URL** : `http://localhost:8000/api/mvola/callback`
- **Méthode** : `PUT` ou `POST`
- **Usage** : Appelé automatiquement par MVola après une transaction

### Webhook MVola
- **URL** : `http://localhost:8000/api/mvola/webhook`
- **Méthode** : `PUT` ou `POST`
- **Usage** : Alternative au callback

### Vérification du statut (Polling)
- **URL** : `http://localhost:8000/api/mvola/status/{serverCorrelationId}`
- **Méthode** : `GET`
- **Usage** : Vérifier manuellement le statut d'une transaction

## 🔍 Vérifications

### Vérifier qu'une transaction a été créée

```sql
SELECT 
    pt.id,
    pt.order_id,
    pt.mvola_correlation_id,
    pt.status,
    pt.amount,
    pt.customer_msisdn,
    o.status as order_status
FROM aiolia.payment_transactions pt
LEFT JOIN aiolia.orders o ON o.id = pt.order_id
ORDER BY pt.created_at DESC
LIMIT 5;
```

### Vérifier les callbacks reçus

```sql
SELECT 
    id,
    mvola_correlation_id,
    status,
    callback_data,
    created_at,
    updated_at
FROM aiolia.payment_transactions
WHERE callback_data IS NOT NULL
ORDER BY updated_at DESC
LIMIT 5;
```

## ⚠️ Points d'attention

### 1. Callback URL en local

Si vous testez en local, MVola ne pourra pas appeler `http://localhost:8000/api/mvola/callback`.

**Solutions :**
- Utilisez **ngrok** pour exposer votre serveur local
- Ou utilisez le **polling** pour vérifier le statut manuellement

### 2. Numéro de téléphone

Assurez-vous que le numéro de téléphone fourni dans le formulaire :
- Est un **vrai numéro MVola** (pour le sandbox, vous pouvez utiliser votre numéro)
- Est au **bon format** (03412345678)

### 3. Logs

Surveillez les logs pour détecter les erreurs :

```bash
tail -f var/log/dev.log
```

## 🐛 Dépannage

### Erreur : "Configuration MVola incomplète"

**Solution** : Vérifiez que toutes les variables sont définies dans `.env.local`

### Erreur : "Impossible de récupérer un access_token"

**Solution** : 
- Vérifiez vos Consumer Key et Secret
- Vérifiez que le Token Endpoint est correct : `https://developer.mvola.mg/oauth2/token`

### Erreur : "Transaction non trouvée" dans le callback

**Solution** : 
- Vérifiez que la transaction a bien été créée avant le callback
- Vérifiez le `serverCorrelationId` dans les logs

### Le callback n'est jamais appelé

**Solutions** :
- Utilisez le polling pour vérifier le statut : `/api/mvola/status/{serverCorrelationId}`
- Vérifiez que le Callback URL est correctement configuré
- En local, utilisez ngrok pour exposer votre serveur

## 📞 Support

Si vous rencontrez des problèmes :

1. **Vérifiez les logs** : `var/log/dev.log`
2. **Vérifiez la base de données** : Tables `payment_transactions` et `orders`
3. **Contactez le support MVola** si les erreurs viennent de l'API

## ✅ Prochaines étapes

Une fois que les tests fonctionnent :

1. ✅ Tester tous les scénarios (succès, échec, annulation)
2. ✅ Tester avec différents montants
3. ✅ Vérifier que les tickets sont bien créés après confirmation
4. ✅ Préparer le passage en production

---

**Bon test ! 🚀**

