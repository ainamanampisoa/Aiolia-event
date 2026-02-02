# 🔧 Correction du problème de résolution DNS MVola

## Problème

L'erreur `Could not resolve host "devapi.mvola.mg"` indique que PHP ne peut pas résoudre le nom de domaine MVola.

## Solutions

### Solution 1 : Ajouter l'entrée dans /etc/hosts (Recommandé pour développement)

Ajoutez cette ligne dans `/etc/hosts` :

```bash
sudo nano /etc/hosts
```

Ajoutez :
```
104.18.18.187 devapi.mvola.mg
```

Puis testez :
```bash
ping devapi.mvola.mg
```

### Solution 2 : Vérifier la configuration DNS

Vérifiez que votre serveur peut résoudre les DNS :

```bash
# Test depuis le terminal
nslookup devapi.mvola.mg

# Test depuis PHP
php -r "echo gethostbyname('devapi.mvola.mg');"
```

### Solution 3 : Configurer un serveur DNS

Si vous utilisez systemd-resolved :

```bash
# Vérifier le statut
systemctl status systemd-resolved

# Vérifier la configuration
cat /etc/systemd/resolved.conf
```

### Solution 4 : Utiliser l'IP directement (Temporaire, non recommandé)

Si rien ne fonctionne, vous pouvez temporairement utiliser l'IP dans `.env` :

```env
MVOLA_BASE_URL=https://104.18.18.187
```

**⚠️ ATTENTION** : Cela peut causer des problèmes avec les certificats SSL car le certificat est émis pour `devapi.mvola.mg`, pas pour l'IP.

### Solution 5 : Vérifier la configuration PHP

Vérifiez que PHP peut faire des requêtes réseau :

```php
<?php
// Test DNS
$host = 'devapi.mvola.mg';
$ip = gethostbyname($host);
echo "IP: $ip\n";

// Test connexion
$ch = curl_init("https://devapi.mvola.mg");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Code: $httpCode\n";
curl_close($ch);
```

## Vérification

Après avoir appliqué une solution, testez :

```bash
# Depuis le terminal
curl -I https://devapi.mvola.mg

# Depuis PHP
php -r "echo file_get_contents('https://devapi.mvola.mg');"
```

## Logs

Les logs indiqueront si la résolution DNS fonctionne :
- `[MVola] DNS résolu via gethostbyname: devapi.mvola.mg -> 104.18.18.187` ✅
- `[MVola] ERREUR: Impossible de résoudre DNS pour devapi.mvola.mg` ❌
