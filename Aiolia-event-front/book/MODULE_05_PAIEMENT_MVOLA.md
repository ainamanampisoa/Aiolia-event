# 💳 Module 05 : Paiement MVola

## Description

Le module MVola gère l'intégration avec le système de paiement mobile MVola (Telma Madagascar). Il permet aux utilisateurs de payer leurs billets via leur compte MVola, avec gestion des callbacks et webhooks pour confirmer les transactions.

---

## 📂 Fichiers concernés

| Type | Fichier |
|------|---------|
| Contrôleur | `src/Controller/MvolaController.php` |
| Service | `src/Service/MvolaPaymentClient.php` |
| Service | `src/Service/PaymentService.php` |
| Configuration | `.env` (variables MVola) |
| Logs | `var/log/mvola.log` |

---

## 🎯 Fonctionnalités

### 1. Initiation du paiement
- Création d'une transaction MVola
- Génération du `serverCorrelationId`
- Envoi de la demande de paiement au client

### 2. Callback MVola
- **Route** : PUT/POST `/api/mvola/callback`
- Réception des notifications de transaction
- Mise à jour du statut de la commande
- Création des billets après paiement réussi

### 3. Webhook MVola
- **Route** : PUT/POST `/api/mvola/webhook`
- Alternative au callback
- Même traitement que le callback

### 4. Vérification de statut (Polling)
- **Route** : GET `/api/mvola/status/{serverCorrelationId}`
- Vérification manuelle du statut d'une transaction
- Utile en cas de non-réception du callback

---

## 🔄 Flux de paiement MVola

```
┌─────────────────┐
│   Utilisateur   │
│ clique "Payer"  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ PaymentService  │
│ initiateMvola() │
└────────┬────────┘
         │
         ├──► Création commande (status: pending)
         ├──► Création transaction (status: initiated)
         ├──► Appel API MVola
         │
         ▼
┌─────────────────┐
│   API MVola     │
│ Merchant Pay    │
└────────┬────────┘
         │
         ├──► Retour serverCorrelationId
         │
         ▼
┌─────────────────┐
│  Notification   │
│  push MVola     │
│ sur téléphone   │
└────────┬────────┘
         │ L'utilisateur confirme
         ▼
┌─────────────────┐
│   MVola envoie  │
│    callback     │
└────────┬────────┘
         │ PUT /api/mvola/callback
         ▼
┌─────────────────┐
│ MvolaController │
│   callback()    │
└────────┬────────┘
         │
         ├──► Vérification serverCorrelationId
         ├──► Mise à jour transaction
         ├──► Si succès: création billets
         ├──► Mise à jour commande (status: paid)
         │
         ▼
┌─────────────────┐
│    Billets      │
│     créés       │
└─────────────────┘
```

---

## 📋 Scénarios d'utilisation

### Scénario 1 : Paiement réussi

1. **Rakoto** valide son panier (75 000 MGA)
2. Il choisit MVola comme méthode de paiement
3. Il entre son numéro : 034 00 000 00
4. Il clique sur "Payer 75 000 MGA"
5. Le système initie la transaction MVola
6. **Rakoto** reçoit une notification sur son téléphone MVola
7. Il entre son code PIN MVola pour confirmer
8. MVola envoie un callback au serveur Aiolia
9. Les billets sont créés automatiquement
10. La page de confirmation s'affiche
11. **Rakoto** reçoit un email avec ses billets

### Scénario 2 : Paiement en sandbox

1. En environnement de test, MVola retourne `status: pending`
2. Le système interprète `pending` comme un paiement **réussi**
3. Les billets sont créés normalement
4. Note : En production, le statut sera `completed` ou `success`

### Scénario 3 : Paiement échoué

1. **Bema** n'a pas assez de solde MVola
2. MVola envoie un callback avec `status: failed`
3. La commande passe en `status: failed`
4. Les billets ne sont pas créés
5. **Bema** est informé de l'échec
6. Il peut réessayer avec une autre méthode

---

## 🛠️ Points techniques

### Configuration MVola (.env)

```env
MVOLA_CONSUMER_KEY=your_consumer_key
MVOLA_CONSUMER_SECRET=your_consumer_secret
MVOLA_MERCHANT_NUMBER=0340000000
MVOLA_API_URL=https://devapi.mvola.mg
MVOLA_CALLBACK_URL=https://your-domain.com/api/mvola/callback
```

### Mapping des statuts MVola

```php
private function mapMvolaStatusToEnum(?string $mvolaStatus): string
{
    // Valeurs valides: 'initiated', 'processing', 'paid', 'failed', 'refunded'
    $statusMap = [
        'completed' => 'processing', // Sandbox: 'processing' au lieu de 'paid'
        'success' => 'processing',
        'paid' => 'processing',
        'failed' => 'failed',
        'failure' => 'failed',
        'error' => 'failed',
        'processing' => 'processing',
        'pending' => 'processing', // En sandbox, 'pending' = succès
        'initiated' => 'initiated',
        'refunded' => 'refunded',
    ];
    
    return $statusMap[strtolower($mvolaStatus ?? '')] ?? 'initiated';
}
```

### Gestion du callback

```php
public function callback(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    
    $serverCorrelationId = $data['serverCorrelationId'] ?? null;
    $transactionStatus = $data['transactionStatus'] ?? null;
    
    // Chercher la transaction en BDD
    $transaction = $this->findTransactionByCorrelationId($serverCorrelationId);
    
    // Mettre à jour le statut
    $this->updateTransactionStatus($transaction['id'], $transactionStatus, $data);
    
    // Si paiement réussi, créer les billets
    $successStatuses = ['completed', 'success', 'paid', 'processing', 'pending'];
    if (in_array(strtolower($transactionStatus ?? ''), $successStatuses, true)) {
        $this->handleSuccessfulPayment($transaction['order_id'], $data);
    }
    
    return new JsonResponse(['status' => 'success']);
}
```

### Création des billets après paiement

```php
private function handleSuccessfulPayment(int $orderId, array $callbackData): void
{
    // Créer les billets
    $result = $this->paymentService->createTicketsAfterPayment($orderId);
    
    if ($result['success']) {
        $this->logger->info('Paiement réussi et billets créés', [
            'order_id' => $orderId,
            'tickets_count' => count($result['tickets'] ?? []),
        ]);
    }
}
```

---

## 📱 Routes

| Route | Méthode | Description |
|-------|---------|-------------|
| `/api/mvola/callback` | PUT/POST | Callback MVola (reçoit les notifications) |
| `/api/mvola/webhook` | PUT/POST | Webhook MVola (alternative) |
| `/api/mvola/status/{id}` | GET | Vérifier le statut d'une transaction |

---

## 🔒 Sécurité

| Mesure | Description |
|--------|-------------|
| HTTPS | Obligatoire pour les callbacks |
| Validation | Vérification du serverCorrelationId |
| Logging | Toutes les transactions sont loguées |
| Idempotence | Éviter les doubles traitements |

---

## 📊 Structure de données

### Table `payment_transactions`

```sql
CREATE TABLE aiolia.payment_transactions (
    id SERIAL PRIMARY KEY,
    order_id INTEGER REFERENCES aiolia.orders(id),
    mvola_correlation_id VARCHAR(255),
    status payment_status_enum, -- 'initiated', 'processing', 'paid', 'failed', 'refunded'
    amount DECIMAL(15,2),
    callback_data JSONB,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
```

### Payload callback MVola (exemple)

```json
{
    "serverCorrelationId": "abc123-def456-789",
    "transactionStatus": "pending",
    "transactionReference": "MVOLA-REF-001",
    "amount": 75000,
    "currency": "MGA",
    "debitParty": [
        { "key": "msisdn", "value": "0340000000" }
    ],
    "creditParty": [
        { "key": "msisdn", "value": "0340000001" }
    ]
}
```

---

## 🐛 Debugging

### Logs MVola

Les logs sont écrits dans `var/log/mvola.log` :

```
[2024-01-15 10:30:45] [INFO] MVola callback reçu | Context: {"method":"PUT","content":"{...}"}
[2024-01-15 10:30:45] [INFO] MVola callback - statut transaction | Context: {"server_correlation_id":"abc123","transaction_status":"pending","is_successful":true}
[2024-01-15 10:30:46] [INFO] Paiement réussi détecté - Appel de handleSuccessfulPayment
```

### Vérification manuelle

```bash
# Vérifier le statut d'une transaction
curl -X GET https://your-domain.com/api/mvola/status/abc123-def456-789
```

---

## 🔗 Dépendances

- **MvolaPaymentClient** : Client API MVola
- **PaymentService** : Logique de paiement
- **Doctrine DBAL** : Accès base de données
- **Monolog** : Système de logs

