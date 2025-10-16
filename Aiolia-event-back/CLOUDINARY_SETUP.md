# 📸 Configuration Cloudinary pour Aiolia Event

Ce guide vous aide à configurer Cloudinary pour le stockage des médias (images, vidéos, documents).

## 🚀 Étapes de configuration

### 1. Créer un compte Cloudinary

1. Allez sur [https://cloudinary.com/users/register/free](https://cloudinary.com/users/register/free)
2. Créez votre compte gratuit
3. Vérifiez votre email

### 2. Récupérer vos credentials

1. Connectez-vous à [https://console.cloudinary.com/](https://console.cloudinary.com/)
2. Sur le Dashboard, vous verrez :
   - **Cloud Name** (nom de votre cloud)
   - **API Key** (clé API)
   - **API Secret** (secret API - cliquez sur "Show" pour le révéler)

### 3. Configurer votre application

#### Option A : Créer un fichier `.env.local`

```bash
# Dans Aiolia-event-back/.env.local
CLOUDINARY_CLOUD_NAME=votre_cloud_name
CLOUDINARY_API_KEY=123456789012345
CLOUDINARY_API_SECRET=abcdefghijklmnopqrstuvwxyz
```

#### Option B : Utiliser l'URL complète

```bash
# Dans Aiolia-event-back/.env.local
CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME
```

### 4. Tester la configuration

Redémarrez votre serveur Symfony :

```bash
symfony server:stop
symfony server:start -d
```

## 📁 Structure des dossiers Cloudinary

Les médias seront organisés ainsi :

```
aiolia-event/
├── events/
│   ├── {event_id}/
│   │   ├── images/
│   │   │   ├── image1.jpg
│   │   │   └── image2.png
│   │   ├── videos/
│   │   │   └── video1.mp4
│   │   └── documents/
│   │       └── document1.pdf
```

## 🎯 Fonctionnalités disponibles

### Upload d'images
```php
$cloudinaryService->uploadImage($file, 'aiolia-event/events/123/images');
```

### Upload de vidéos
```php
$cloudinaryService->uploadVideo($file, 'aiolia-event/events/123/videos');
```

### Upload de documents
```php
$cloudinaryService->uploadFile($file, 'aiolia-event/events/123/documents');
```

### Générer des URL optimisées
```php
// Image redimensionnée
$cloudinaryService->getOptimizedUrl($publicId, 800, 600);

// Thumbnail
$cloudinaryService->getThumbnailUrl($publicId, 200);
```

### Supprimer un fichier
```php
$cloudinaryService->deleteFile($publicId, 'image');
```

## 🔧 Avantages de Cloudinary

✅ **Stockage cloud** - Pas de gestion de serveur de fichiers  
✅ **CDN intégré** - Distribution mondiale rapide  
✅ **Optimisation automatique** - Compression et conversion  
✅ **Transformations** - Redimensionnement, crop, filtres  
✅ **Responsive** - Images adaptées aux devices  
✅ **Sécurisé** - HTTPS par défaut  
✅ **Gratuit** jusqu'à 25 GB stockage et 25 GB bande passante/mois  

## 📊 Limites du plan gratuit

- 25 GB de stockage
- 25 GB de bande passante/mois
- 25 crédits de transformation/mois
- Suffisant pour démarrer !

## 🆘 En cas de problème

Si vous voyez des erreurs :
1. Vérifiez que les credentials sont corrects
2. Vérifiez que `.env.local` est bien créé
3. Videz le cache : `php bin/console cache:clear`
4. Vérifiez les logs : `var/log/dev.log`

## 📞 Support

- Documentation : [https://cloudinary.com/documentation/php_integration](https://cloudinary.com/documentation/php_integration)
- Dashboard : [https://console.cloudinary.com/](https://console.cloudinary.com/)

