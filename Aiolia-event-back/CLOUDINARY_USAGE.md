# 📚 Guide d'utilisation de Cloudinary dans Aiolia Event

## 🎯 Exemples d'utilisation

### Dans un Controller

```php
use App\Service\CloudinaryService;
use App\Service\MediaService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EventController extends AbstractController
{
    #[Route('/upload', methods: ['POST'])]
    public function uploadMedia(
        Request $request,
        MediaService $mediaService,
        Event $event
    ): Response {
        /** @var UploadedFile $file */
        $file = $request->files->get('image');
        
        // Upload une image pour un événement
        $media = $mediaService->uploadEventMedia(
            $event,
            $file,
            'image',
            $this->getUser(),
            true // isPrimary
        );
        
        return $this->json([
            'success' => true,
            'url' => $media->getFileUrl(),
        ]);
    }
}
```

### Dans un Template Twig

```twig
{# Afficher l'image principale d'un événement #}
{% set primaryImage = event_primary_image(event) %}
{% if primaryImage %}
    {# URL originale #}
    <img src="{{ primaryImage.fileUrl }}" alt="{{ event.title }}">
    
    {# URL optimisée 800x600 #}
    <img src="{{ primaryImage|cloudinary_optimized(800, 600) }}" alt="{{ event.title }}">
    
    {# Thumbnail 200x200 #}
    <img src="{{ primaryImage|cloudinary_thumb(200) }}" alt="{{ event.title }}">
{% endif %}

{# Générer une URL depuis un public_id #}
<img src="{{ 'aiolia-event/events/123/images/photo'|cloudinary_url(400, 300) }}">
```

### Utilisation directe du CloudinaryService

```php
use App\Service\CloudinaryService;

class MyController
{
    public function __construct(
        private CloudinaryService $cloudinaryService
    ) {
    }
    
    public function example(UploadedFile $file): void
    {
        // Upload une image
        $result = $this->cloudinaryService->uploadImage(
            $file,
            'aiolia-event/events/123/images'
        );
        
        if ($result['success']) {
            $url = $result['url'];
            $publicId = $result['public_id'];
            $width = $result['width'];
            $height = $result['height'];
        }
        
        // Générer une URL optimisée
        $optimizedUrl = $this->cloudinaryService->getOptimizedUrl(
            'aiolia-event/events/123/images/photo',
            800,
            600,
            'fill'
        );
        
        // Générer un thumbnail
        $thumbUrl = $this->cloudinaryService->getThumbnailUrl(
            'aiolia-event/events/123/images/photo',
            200
        );
        
        // Supprimer un fichier
        $this->cloudinaryService->deleteFile(
            'aiolia-event/events/123/images/photo',
            'image'
        );
        
        // Lister les fichiers d'un dossier
        $files = $this->cloudinaryService->listFiles(
            'aiolia-event/events/123/images'
        );
        
        // Valider un type d'image
        if ($this->cloudinaryService->isValidImageType($file)) {
            // Upload...
        }
    }
}
```

## 🎨 Transformations disponibles

### Redimensionnement
```php
// Largeur fixe, hauteur proportionnelle
$cloudinaryService->getOptimizedUrl($publicId, 800, null);

// Hauteur fixe, largeur proportionnelle
$cloudinaryService->getOptimizedUrl($publicId, null, 600);

// Dimensions exactes avec crop
$cloudinaryService->getOptimizedUrl($publicId, 800, 600, 'fill');
```

### Types de crop
- `fill` : Remplit les dimensions (peut rogner)
- `fit` : Contient dans les dimensions (pas de rognage)
- `thumb` : Thumbnail centré
- `scale` : Redimensionne (peut déformer)

## 📦 Types de médias supportés

### Images
- JPEG, JPG
- PNG
- GIF
- WEBP

### Vidéos
- MP4
- MOV
- AVI
- WEBM

### Documents
- PDF
- DOC, DOCX
- XLS, XLSX
- TXT

## 🔐 Sécurité

Cloudinary gère automatiquement :
- ✅ HTTPS par défaut
- ✅ Protection contre les uploads malveillants
- ✅ Validation des types MIME
- ✅ Limitation de taille

## 🚀 Optimisations automatiques

Cloudinary applique automatiquement :
- ✅ Compression intelligente
- ✅ Conversion au meilleur format (WebP si supporté)
- ✅ Lazy loading
- ✅ Responsive images
- ✅ CDN global

## 💡 Bonnes pratiques

1. **Toujours définir une image principale** pour chaque événement
2. **Organiser les médias par dossier** (events/{id}/images, etc.)
3. **Utiliser les transformations** pour les thumbnails et aperçus
4. **Nettoyer les médias** non utilisés régulièrement
5. **Surveiller l'usage** sur le dashboard Cloudinary

