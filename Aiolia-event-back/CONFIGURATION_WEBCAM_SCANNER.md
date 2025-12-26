# Configuration Webcam pour le Scanner de Billets

## 📋 Prérequis

Pour que le scanner de billets fonctionne correctement, plusieurs configurations sont nécessaires :

### 1. **HTTPS (Requis pour la plupart des navigateurs)**

La plupart des navigateurs modernes exigent HTTPS pour accéder à la caméra.

**Solution locale (développement) :**
```bash
# Utiliser Symfony CLI avec HTTPS
symfony server:start --port=8000 --tls

# Ou utiliser ngrok pour créer un tunnel HTTPS
ngrok http 8000
```

**Solution production :**
- Utiliser un certificat SSL valide
- Configurer HTTPS sur votre serveur web (Apache/Nginx)

### 2. **Permissions du Navigateur**

#### Chrome/Edge
1. Cliquez sur l'icône de cadenas dans la barre d'adresse
2. Sélectionnez "Paramètres du site"
3. Activez "Caméra" → "Autoriser"
4. Rechargez la page

#### Firefox
1. Cliquez sur l'icône de cadenas dans la barre d'adresse
2. Cliquez sur "Autoriser" pour la caméra
3. Ou allez dans `about:preferences#privacy` → Permissions → Caméra

#### Safari
1. Safari → Préférences → Sites web → Caméra
2. Autorisez l'accès pour votre site

### 3. **Permissions Système (macOS)**

Si vous êtes sur macOS :
1. Système → Confidentialité et sécurité → Caméra
2. Autorisez votre navigateur (Chrome, Firefox, Safari)

### 4. **Permissions Système (Windows)**

1. Paramètres → Confidentialité → Caméra
2. Activez "Autoriser les applications à accéder à votre caméra"
3. Autorisez votre navigateur

### 5. **Vérification de la Webcam**

Avant d'utiliser le scanner :
- Testez votre webcam avec une autre application (Zoom, Teams, etc.)
- Vérifiez que la webcam n'est pas utilisée par une autre application
- Redémarrez le navigateur si nécessaire

## 🔧 Configuration du Code

Le scanner essaie automatiquement plusieurs configurations de caméra :

1. **Caméra frontale** (`facingMode: "user"`) - Pour PC
2. **Caméra arrière** (`facingMode: "environment"`) - Pour mobile
3. **Première caméra disponible** - Fallback

## 🐛 Dépannage

### Erreur : "NotAllowedError"
**Solution :** Autorisez l'accès à la caméra dans les paramètres du navigateur

### Erreur : "NotFoundError"
**Solution :** 
- Vérifiez que votre webcam est connectée
- Testez-la avec une autre application
- Redémarrez votre ordinateur

### Erreur : "NotReadableError"
**Solution :**
- Fermez les autres applications qui utilisent la caméra
- Redémarrez le navigateur

### La caméra démarre mais le cadre de scan n'apparaît pas
**Solution :**
- Videz le cache du navigateur
- Utilisez un autre navigateur
- Vérifiez la console du navigateur (F12) pour les erreurs

## 📱 Test sur Mobile

Sur mobile, le scanner utilise automatiquement la caméra arrière. Assurez-vous que :
- Vous utilisez HTTPS
- Les permissions de caméra sont autorisées
- Vous utilisez un navigateur moderne (Chrome, Safari, Firefox)

## ✅ Checklist de Configuration

- [ ] HTTPS activé (ou localhost pour le développement)
- [ ] Permissions caméra autorisées dans le navigateur
- [ ] Permissions système autorisées (macOS/Windows)
- [ ] Webcam fonctionne dans d'autres applications
- [ ] Aucune autre application n'utilise la caméra
- [ ] Navigateur à jour
- [ ] Cache du navigateur vidé si nécessaire

## 🔗 Ressources

- [Documentation HTML5-QRCode](https://github.com/mebjas/html5-qrcode)
- [MDN - MediaDevices.getUserMedia()](https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia)
- [Chrome - Permissions de caméra](https://support.google.com/chrome/answer/2693767)

- postgres : https://www.postgresql.org/
- chat : https://chatgpt.com/c/6943f046-1c3c-832b-9c50-482a03185441
- postman : https://www.postman.com/