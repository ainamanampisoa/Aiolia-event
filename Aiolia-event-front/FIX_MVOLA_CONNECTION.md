# 🔧 Correction de l'erreur de connexion MVola

## Problème

L'erreur `Failed to open stream: HTTP request failed!` indique que l'application ne peut pas se connecter à l'API MVola.

## Solutions

### 1. Vérifier la configuration dans `.env`

Vérifiez que votre fichier `.env` contient les bonnes valeurs :

```env
# URL de base MVola (sandbox pour développement)
MVOLA_BASE_URL=https://devapi.mvola.mg

# OU pour la production :
# MVOLA_BASE_URL=https://api.mvola.mg

# Autres variables MVola
MVOLA_CONSUMER_KEY=votre_consumer_key
MVOLA_CONSUMER_SECRET=votre_consumer_secret
MVOLA_PARTNER_MSISDN=+261XXXXXXXXX
MVOLA_PARTNER_NAME=VotreNomPartenaire
MVOLA_CALLBACK_URL=http://localhost:8000/api/mvola/callback
```

**⚠️ IMPORTANT** : 
- Utilisez **`https://devapi.mvola.mg`** pour le développement (sandbox)
- Utilisez **`https://api.mvola.mg`** pour la production
- **NE PAS utiliser d'adresse IP directe** (comme `104.18.18.187`)

### 2. Vérifier la connexion internet

Testez la connexion à l'API MVola :

```bash
# Test sandbox
curl -I https://devapi.mvola.mg

# Test production
curl -I https://api.mvola.mg
```

### 3. Vérifier les certificats SSL

Si vous avez des problèmes de certificat SSL, vous pouvez temporairement désactiver la vérification (UNIQUEMENT en développement) :

Modifiez `MvolaPaymentClient.php` ligne 44-48 :

```php
$this->httpClient = HttpClient::create([
    'timeout' => 30,
    'verify_peer' => false,  // ⚠️ UNIQUEMENT EN DEV
    'verify_host' => false,  // ⚠️ UNIQUEMENT EN DEV
]);
```

**⚠️ NE JAMAIS faire cela en production !**

### 4. Mode développement sans MVola (simulation)

Si vous ne pouvez pas vous connecter à MVola en développement, vous pouvez temporairement simuler le paiement :

Dans `PaymentService.php`, modifiez la méthode `initiateMvolaPayment` pour ajouter un mode simulation :

```php
private function initiateMvolaPayment(int $orderId, float $amount, array $paymentData): array
{
    // Mode simulation pour développement
    if ($_ENV['APP_ENV'] === 'dev' && ($_ENV['MVOLA_SIMULATE'] ?? 'false') === 'true') {
        return [
            'success' => true,
            'serverCorrelationId' => 'SIM-' . uniqid(),
            'transactionReference' => 'SIM-ORDER-' . $orderId,
        ];
    }
    
    // Code normal MVola...
}
```

Puis dans `.env` :
```env
MVOLA_SIMULATE=true
```

### 5. Vérifier les logs

Consultez les logs MVola pour plus de détails :

```bash
tail -f var/log/mvola.log
```

## Vérification rapide

1. ✅ Vérifier que `MVOLA_BASE_URL` est bien configuré (pas d'IP directe)
2. ✅ Vérifier votre connexion internet
3. ✅ Vérifier que les certificats SSL sont valides
4. ✅ Consulter les logs pour plus de détails

## Contact

Si le problème persiste, vérifiez :
- Les credentials MVola (consumer_key, consumer_secret)
- L'accès réseau à l'API MVola
- Les logs détaillés dans `var/log/mvola.log`
