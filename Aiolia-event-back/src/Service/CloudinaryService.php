<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CloudinaryService
{
    private ?Cloudinary $cloudinary = null;

    public function __construct(
        private ParameterBagInterface $params
    ) {
    }

    /**
     * Initialise Cloudinary (lazy loading)
     */
    private function getCloudinary(): Cloudinary
    {
        if ($this->cloudinary === null) {
            // Récupérer les credentials depuis les variables d'environnement
            $cloudName = $_ENV['CLOUDINARY_CLOUD_NAME'] ?? 'demo';
            $apiKey = $_ENV['CLOUDINARY_API_KEY'] ?? '';
            $apiSecret = $_ENV['CLOUDINARY_API_SECRET'] ?? '';

            // Configuration de Cloudinary
            Configuration::instance([
                'cloud' => [
                    'cloud_name' => $cloudName,
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret,
                ],
                'url' => [
                    'secure' => true
                ]
            ]);

            $this->cloudinary = new Cloudinary();
        }

        return $this->cloudinary;
    }

    /**
     * Vérifie si Cloudinary est configuré
     */
    public function isConfigured(): bool
    {
        return !empty($_ENV['CLOUDINARY_CLOUD_NAME']) 
            && !empty($_ENV['CLOUDINARY_API_KEY']) 
            && !empty($_ENV['CLOUDINARY_API_SECRET']);
    }

    /**
     * Upload une image sur Cloudinary
     */
    public function uploadImage(UploadedFile $file, string $folder = 'events'): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Cloudinary n\'est pas configuré. Veuillez ajouter vos credentials dans .env.local',
            ];
        }

        try {
            $result = $this->getCloudinary()->uploadApi()->upload(
                $file->getPathname(),
                [
                    'folder' => $folder,
                    'resource_type' => 'image',
                    'transformation' => [
                        'quality' => 'auto',
                        'fetch_format' => 'auto'
                    ]
                ]
            );

            return [
                'success' => true,
                'url' => $result['secure_url'],
                'public_id' => $result['public_id'],
                'width' => $result['width'],
                'height' => $result['height'],
                'format' => $result['format'],
                'size' => $result['bytes'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload une vidéo sur Cloudinary
     */
    public function uploadVideo(UploadedFile $file, string $folder = 'events/videos'): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Cloudinary n\'est pas configuré. Veuillez ajouter vos credentials dans .env.local',
            ];
        }

        try {
            $result = $this->getCloudinary()->uploadApi()->upload(
                $file->getPathname(),
                [
                    'folder' => $folder,
                    'resource_type' => 'video',
                    'chunk_size' => 6000000, // 6MB chunks
                ]
            );

            return [
                'success' => true,
                'url' => $result['secure_url'],
                'public_id' => $result['public_id'],
                'duration' => $result['duration'] ?? null,
                'format' => $result['format'],
                'size' => $result['bytes'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload un fichier (PDF, etc.) sur Cloudinary
     */
    public function uploadFile(UploadedFile $file, string $folder = 'events/documents'): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Cloudinary n\'est pas configuré. Veuillez ajouter vos credentials dans .env.local',
            ];
        }

        try {
            $result = $this->getCloudinary()->uploadApi()->upload(
                $file->getPathname(),
                [
                    'folder' => $folder,
                    'resource_type' => 'raw',
                ]
            );

            return [
                'success' => true,
                'url' => $result['secure_url'],
                'public_id' => $result['public_id'],
                'format' => $result['format'],
                'size' => $result['bytes'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Supprime un fichier de Cloudinary
     */
    public function deleteFile(string $publicId, string $resourceType = 'image'): bool
    {
        try {
            $this->getCloudinary()->uploadApi()->destroy($publicId, [
                'resource_type' => $resourceType,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Génère une URL optimisée avec transformations
     */
    public function getOptimizedUrl(
        string $publicId,
        int $width = null,
        int $height = null,
        string $crop = 'fill'
    ): string {
        $transformations = [];

        if ($width) {
            $transformations['width'] = $width;
        }

        if ($height) {
            $transformations['height'] = $height;
        }

        if ($width || $height) {
            $transformations['crop'] = $crop;
        }

        $transformations['quality'] = 'auto';
        $transformations['fetch_format'] = 'auto';

        try {
            return $this->getCloudinary()->image($publicId)->toUrl($transformations);
        } catch (\Exception $e) {
            // En cas d'erreur, retourner l'URL de base
            return "https://res.cloudinary.com/" . $_ENV['CLOUDINARY_CLOUD_NAME'] . "/image/upload/" . $publicId;
        }
    }

    /**
     * Génère une URL de thumbnail
     */
    public function getThumbnailUrl(string $publicId, int $size = 200): string
    {
        return $this->getOptimizedUrl($publicId, $size, $size, 'thumb');
    }

    /**
     * Récupère les informations d'un fichier
     */
    public function getFileInfo(string $publicId, string $resourceType = 'image'): ?array
    {
        try {
            $result = $this->getCloudinary()->adminApi()->asset($publicId, [
                'resource_type' => $resourceType,
            ]);
            return $result->getArrayCopy();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Liste tous les fichiers d'un dossier
     */
    public function listFiles(string $folder, string $resourceType = 'image', int $maxResults = 100): array
    {
        try {
            $result = $this->getCloudinary()->adminApi()->assets([
                'type' => 'upload',
                'prefix' => $folder,
                'resource_type' => $resourceType,
                'max_results' => $maxResults,
            ]);

            return $result['resources'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Crée un dossier sur Cloudinary
     */
    public function createFolder(string $path): bool
    {
        try {
            $this->getCloudinary()->adminApi()->createFolder($path);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Valide le type de fichier
     */
    public function isValidImageType(UploadedFile $file): bool
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($file->getMimeType(), $allowedTypes);
    }

    /**
     * Valide le type de vidéo
     */
    public function isValidVideoType(UploadedFile $file): bool
    {
        $allowedTypes = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm'];
        return in_array($file->getMimeType(), $allowedTypes);
    }
}

