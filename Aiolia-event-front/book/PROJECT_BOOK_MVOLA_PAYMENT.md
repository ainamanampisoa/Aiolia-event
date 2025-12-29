# 📖 Livre du Projet : Paiement Mobile Money (Mvola)

Le paiement Mobile Money via Mvola est au cœur de l'expérience Aiolia-event. Cette intégration permet aux utilisateurs de payer leurs billets directement depuis leur téléphone mobile, sans avoir besoin de carte bancaire ou de compte bancaire traditionnel. C'est une solution adaptée au contexte malgache, où le Mobile Money est largement adopté.

---

## 💳 a. Présentation de l'intégration Mvola

Mvola est le service de Mobile Money d'Orange Money Madagascar. L'intégration avec Aiolia-event permet aux utilisateurs de payer leurs billets en utilisant leur solde Mvola, offrant une expérience de paiement rapide, sécurisée et accessible.

### L'expérience utilisateur
Lors du processus de paiement, l'utilisateur choisit "Payer avec Mvola" comme mode de paiement. Le système :
1. Génère une transaction unique avec un identifiant de corrélation
2. Initie le paiement via l'API Mvola
3. L'utilisateur reçoit une notification sur son téléphone pour confirmer le paiement
4. Une fois confirmé, le paiement est validé automatiquement
5. Les billets sont générés et disponibles immédiatement

### Sous le capot 🛠️
L'intégration Mvola utilise MvolaPaymentClient, un service dédié qui :
- Gère l'authentification avec l'API Mvola (tokens OAuth)
- Initie les transactions de paiement
- Gère les callbacks et webhooks pour la confirmation
- Traite les erreurs et les cas d'échec
- Maintient une traçabilité complète de toutes les transactions

---

## 🔄 b. Flux de paiement

Le flux de paiement Mvola suit un processus sécurisé en plusieurs étapes pour garantir la fiabilité et la sécurité de chaque transaction.

### Étape 1 : Initiation de la transaction
Lorsque l'utilisateur confirme son paiement :
- PaymentService::processPayment() est appelé
- Une transaction est créée dans payment_transactions avec le statut "pending"
- Un serverCorrelationId unique est généré pour suivre la transaction
- L'API Mvola est appelée avec les détails du paiement (montant, numéro de téléphone, référence)

### Étape 2 : Confirmation utilisateur
L'utilisateur reçoit une notification sur son téléphone Mvola :
- Il doit confirmer le paiement en saisissant son code PIN
- La transaction est en attente jusqu'à confirmation
- Un délai de 15 minutes est généralement accordé pour confirmer

### Étape 3 : Callback Mvola
Une fois le paiement confirmé par l'utilisateur, Mvola envoie un callback à notre serveur :
- MvolaController::callback() reçoit la notification
- Le statut de la transaction est mis à jour (succès ou échec)
- Si le paiement est réussi :
  - La commande passe au statut "paid"
  - Les billets sont générés automatiquement
  - Le panier est vidé
  - Des notifications sont envoyées à l'utilisateur

### Étape 4 : Webhook de confirmation
En complément du callback, un webhook peut être configuré pour une confirmation supplémentaire :
- MvolaController::webhook() traite les notifications webhook
- Cela permet une redondance et une meilleure traçabilité

### Les coulisses techniques ⚙️
Le flux utilise plusieurs composants :
1. **MvolaPaymentClient** : Client API pour communiquer avec Mvola
2. **PaymentService** : Orchestre le processus de paiement
3. **MvolaController** : Gère les callbacks et webhooks
4. **Base de données** : Stocke toutes les transactions pour audit et suivi

---

## 🔍 c. Vérification du statut des transactions

Le système permet de vérifier le statut d'une transaction à tout moment, offrant transparence et traçabilité.

### L'expérience utilisateur
L'utilisateur peut :
- Voir le statut de sa transaction dans "Mes commandes"
- Recevoir des notifications en temps réel sur l'état du paiement
- Consulter l'historique de toutes ses transactions

### Sous le capot 🛠️
**Vérification manuelle** :
- MvolaController::checkStatus() permet de vérifier le statut d'une transaction via son serverCorrelationId
- L'API Mvola est interrogée pour obtenir le statut actuel
- Le statut en base de données est mis à jour si nécessaire

**Statuts possibles** :
- **pending** : Transaction initiée, en attente de confirmation
- **completed** : Paiement confirmé et validé
- **failed** : Échec du paiement (solde insuffisant, code PIN incorrect, etc.)
- **cancelled** : Transaction annulée par l'utilisateur ou expirée
- **refunded** : Transaction remboursée

---

## 🛡️ d. Sécurité et gestion des erreurs

La sécurité est primordiale dans le traitement des paiements. Le système intègre plusieurs mécanismes pour garantir la protection des transactions.

### Mesures de sécurité
**Authentification** :
- Toutes les communications avec l'API Mvola sont authentifiées via OAuth 2.0
- Les tokens sont régénérés automatiquement avant expiration
- Les credentials sont stockés de manière sécurisée dans les variables d'environnement

**Validation** :
- Chaque transaction est validée avant d'être traitée
- Les montants sont vérifiés pour éviter les incohérences
- Les numéros de téléphone sont validés avant l'envoi à Mvola

**Traçabilité** :
- Toutes les transactions sont enregistrées dans payment_transactions
- Les callbacks et webhooks sont loggés pour audit
- Un fichier de log dédié (mvola.log) enregistre toutes les interactions

### Gestion des erreurs
**Erreurs de paiement** :
- Si le paiement échoue, l'utilisateur est informé clairement
- La raison de l'échec est affichée (solde insuffisant, code PIN incorrect, etc.)
- La commande reste en statut "pending" et peut être réessayée

**Erreurs techniques** :
- En cas d'erreur de communication avec Mvola, la transaction est marquée comme "en erreur"
- Un mécanisme de retry peut être implémenté pour les erreurs temporaires
- Les erreurs sont loggées pour investigation

**Timeout** :
- Si une transaction reste en "pending" trop longtemps (délai dépassé), elle est automatiquement annulée
- Les billets réservés sont libérés
- L'utilisateur peut recréer sa commande

---

## 📊 e. Remboursements

Le système gère également les remboursements via Mvola, notamment en cas d'annulation d'événement.

### Processus de remboursement
Lorsqu'un événement est annulé et qu'un remboursement est nécessaire :
1. RefundService::refundOrder() est appelé
2. Une transaction de remboursement est initiée via l'API Mvola
3. Le montant est crédité sur le compte Mvola de l'utilisateur
4. La transaction originale est marquée comme "refunded"
5. L'utilisateur est notifié du remboursement

### Les coulisses techniques ⚙️
Le remboursement utilise la même infrastructure que le paiement :
- MvolaPaymentClient::refund() initie le remboursement
- Le callback Mvola confirme le remboursement
- Les billets sont marqués comme "refunded" dans la base de données
- L'historique de commande est mis à jour

---

## 🎭 Scénario d'utilisation : Le paiement de Rivo

Suivons **Rivo**, un utilisateur qui achète des billets pour un festival.

### 1. Initiation du paiement
Rivo a sélectionné 2 billets VIP à 50 000 MGA chacun (total : 100 000 MGA). Il clique sur "Payer avec Mvola" et saisit son numéro de téléphone (032 00 000 00).

### 2. Confirmation sur téléphone
Quelques secondes plus tard, Rivo reçoit une notification sur son téléphone : "Confirmez le paiement de 100 000 MGA pour Aiolia-event". Il ouvre l'application Mvola, saisit son code PIN et confirme.

### 3. Validation automatique
Dès la confirmation, le système reçoit le callback Mvola. La transaction est validée, les billets sont générés, et Rivo est redirigé vers la page de confirmation. Il voit ses billets avec leurs codes QR.

### 4. Cas d'erreur (alternative)
Si Rivo avait un solde insuffisant, il aurait reçu un message d'erreur clair : "Solde insuffisant. Votre solde Mvola est de 45 000 MGA, mais le montant requis est de 100 000 MGA." Il pourrait alors recharger son compte Mvola et réessayer.

---

> [!TIP]
> **Le saviez-vous ?**
> Les transactions Mvola sont tracées de bout en bout. Si vous avez un problème avec un paiement, notre équipe peut vérifier le statut exact de votre transaction directement auprès de Mvola grâce au serverCorrelationId unique.




