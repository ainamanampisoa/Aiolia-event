<?php

namespace App\Service\Admin;

use Cloudinary\Cloudinary;
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
            $cloudName = $this->getEnvValue('CLOUDINARY_CLOUD_NAME');
            $apiKey = $this->getEnvValue('CLOUDINARY_API_KEY');
            $apiSecret = $this->getEnvValue('CLOUDINARY_API_SECRET');

            if (!$cloudName || !$apiKey || !$apiSecret) {
                throw new \RuntimeException('Cloudinary credentials are missing in environment variables.');
            }

            $config = [
                'cloud' => [
                    'cloud_name' => $cloudName,
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret,
                ],
                'url' => [
                    'secure' => true,
                ],
            ];

            $this->cloudinary = new Cloudinary($config);
        }

        return $this->cloudinary;
    }

    /**
     * Vérifie si Cloudinary est configuré
     */
    public function isConfigured(): bool
    {
        return $this->getEnvValue('CLOUDINARY_CLOUD_NAME') &&
               $this->getEnvValue('CLOUDINARY_API_KEY') &&
               $this->getEnvValue('CLOUDINARY_API_SECRET');
    }

    /**
     * Upload une image avec optimisations
     */
    public function uploadImage(UploadedFile $file, string $folder = 'events', array $options = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Cloudinary n\'est pas configuré. Veuillez ajouter vos identifiants dans .env.local',
            ];
        }

        try {
            // Validation de la taille (max 5 MB pour plan gratuit - recommandé: 2-3 MB)
            $maxSize = 5 * 1024 * 1024; // 5 MB
            if ($file->getSize() > $maxSize) {
                return [
                    'success' => false,
                    'error' => sprintf(
                        'Le fichier est trop volumineux (%.2f MB). Taille maximale: %d MB. Veuillez compresser l\'image.',
                        $file->getSize() / 1024 / 1024,
                        $maxSize / 1024 / 1024
                    ),
                ];
            }

            // Options par défaut optimisées
            $defaultOptions = [
                'folder' => $folder,
                'resource_type' => 'image',
                'transformation' => [
                    'quality' => 'auto:good', // Compression automatique (auto:good = bon équilibre qualité/taille)
                    'fetch_format' => 'auto', // Format optimal (WebP pour navigateurs supportés)
                ],
                // Générer plusieurs formats à l'upload pour performance (optionnel)
                // 'eager' => [
                //     ['format' => 'webp', 'quality' => 'auto'],
                //     ['format' => 'jpg', 'quality' => 'auto'],
                // ],
                // Ne pas stocker les métadonnées EXIF pour réduire la taille
                'invalidate' => true, // Invalider le cache CDN
            ];

            $uploadOptions = array_replace_recursive($defaultOptions, $options);

            // Timeout plus long pour les uploads (plan gratuit peut être lent)
            $result = $this->getCloudinary()->uploadApi()->upload(
                $file->getPathname(),
                $uploadOptions
            );

            return [
                'success' => true,
                'url' => $result['secure_url'],
                'public_id' => $result['public_id'],
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'format' => $result['format'] ?? null,
                'size' => $result['bytes'] ?? $file->getSize(),
                'optimized_size' => $result['bytes'] ?? null, // Taille après optimisation Cloudinary
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'upload: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Upload une vidéo
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
                    'chunk_size' => 6000000,
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
     * Upload un fichier (PDF, etc.)
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
     * Supprime un fichier
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
     * Test de connectivité
     */
    public function testConnection(): array
    {
        $cloudName = $this->getEnvValue('CLOUDINARY_CLOUD_NAME');
        $apiKey = $this->getEnvValue('CLOUDINARY_API_KEY');
        $apiSecret = $this->getEnvValue('CLOUDINARY_API_SECRET');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            return [
                'success' => false,
                'message' => 'Invalid configuration: one or more credentials are missing.',
                'env' => [
                    'CLOUDINARY_CLOUD_NAME' => $cloudName,
                    'CLOUDINARY_API_KEY' => $apiKey,
                    'CLOUDINARY_API_SECRET' => $apiSecret,
                ]
            ];
        }

        try {
            $this->getCloudinary()->adminApi()->ping();

            return [
                'success' => true,
                'message' => 'Cloudinary configuration is valid.',
                'env' => [
                    'CLOUDINARY_CLOUD_NAME' => $cloudName,
                    'CLOUDINARY_API_KEY' => $apiKey,
                    'CLOUDINARY_API_SECRET' => $apiSecret,
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'env' => [
                    'CLOUDINARY_CLOUD_NAME' => $cloudName,
                    'CLOUDINARY_API_KEY' => $apiKey,
                    'CLOUDINARY_API_SECRET' => $apiSecret,
                ]
            ];
        }
    }

    /**
     * Récupère la valeur d'une variable d'environnement
     */
    private function getEnvValue(string $key): ?string
    {
        $parameterKey = sprintf('env(%s)', $key);

        if ($this->params->has($parameterKey)) {
            $value = $this->params->get($parameterKey);

            if (is_array($value)) {
                $value = $value[0] ?? null;
            }

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * Valide les types d'images autorisés
     */
    public function isValidImageType(UploadedFile $file): bool
    {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($file->getMimeType(), $allowedTypes, true);
    }

    /**
     * Valide la taille du fichier (recommandé: max 2-3 MB pour plan gratuit)
     */
    public function isValidImageSize(UploadedFile $file, int $maxSizeMB = 3): array
    {
        $maxSize = $maxSizeMB * 1024 * 1024;
        $fileSize = $file->getSize();
        
        if ($fileSize > $maxSize) {
            return [
                'valid' => false,
                'error' => sprintf(
                    'Le fichier est trop volumineux (%.2f MB). Taille maximale recommandée: %d MB.',
                    $fileSize / 1024 / 1024,
                    $maxSizeMB
                ),
                'size_mb' => round($fileSize / 1024 / 1024, 2),
                'max_size_mb' => $maxSizeMB,
            ];
        }
        
        return [
            'valid' => true,
            'size_mb' => round($fileSize / 1024 / 1024, 2),
        ];
    }

    /**
     * Récupère l'utilisation actuelle de Cloudinary (nécessite API Admin)
     */
    public function getUsage(): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'error' => 'Cloudinary n\'est pas configuré',
                    'available' => false,
                ];
            }

            $usage = $this->getCloudinary()->adminApi()->usage();

            // Structure de la réponse Cloudinary (plan Free)
            // - storage.usage (en bytes)
            // - bandwidth.usage (en bytes)
            // - credits.limit (en GB pour le plan gratuit)
            // - credits.usage (en GB)
            // - credits.used_percent (pourcentage)

            $storageUsed = $usage['storage']['usage'] ?? $usage['storage']['used'] ?? null;
            $bandwidthUsed = $usage['bandwidth']['usage'] ?? $usage['bandwidth']['used'] ?? null;

            // Les limites sont dans credits (pour plan gratuit)
            $creditsLimit = $usage['credits']['limit'] ?? null; // en GB
            $creditsUsed = $usage['credits']['usage'] ?? null; // en GB
            $creditsUsedPercent = $usage['credits']['used_percent'] ?? null;

            // Convertir credits en bytes pour cohérence (1 GB = 1024^3 bytes)
            $storageLimit = $creditsLimit ? ($creditsLimit * 1024 * 1024 * 1024) : null;
            $bandwidthLimit = $creditsLimit ? ($creditsLimit * 1024 * 1024 * 1024) : null;

            // Calculer les pourcentages
            $storagePercentage = null;
            if ($storageUsed !== null && $storageLimit !== null && $storageLimit > 0) {
                $storagePercentage = round(($storageUsed / $storageLimit) * 100, 2);
            }

            $bandwidthPercentage = null;
            if ($bandwidthUsed !== null && $bandwidthLimit !== null && $bandwidthLimit > 0) {
                $bandwidthPercentage = round(($bandwidthUsed / $bandwidthLimit) * 100, 2);
            }

            return [
                'available' => true,
                'plan' => $usage['plan'] ?? 'Unknown',
                'storage_used' => $storageUsed,
                'storage_used_mb' => $storageUsed ? round($storageUsed / 1024 / 1024, 2) : null,
                'storage_used_gb' => $storageUsed ? round($storageUsed / 1024 / 1024 / 1024, 2) : null,
                'storage_limit' => $storageLimit,
                'storage_limit_gb' => $creditsLimit,
                'bandwidth_used' => $bandwidthUsed,
                'bandwidth_used_mb' => $bandwidthUsed ? round($bandwidthUsed / 1024 / 1024, 2) : null,
                'bandwidth_used_gb' => $bandwidthUsed ? round($bandwidthUsed / 1024 / 1024 / 1024, 2) : null,
                'bandwidth_limit' => $bandwidthLimit,
                'bandwidth_limit_gb' => $creditsLimit,
                'storage_percentage' => $storagePercentage,
                'bandwidth_percentage' => $bandwidthPercentage,
                'credits_limit_gb' => $creditsLimit,
                'credits_used_gb' => $creditsUsed,
                'credits_used_percent' => $creditsUsedPercent,
                'last_updated' => $usage['last_updated'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'note' => 'L\'API usage() peut ne pas être disponible sur tous les plans Cloudinary. Vérifiez votre console Cloudinary pour les statistiques détaillées.',
            ];
        }
    }

    /**
     * Valide les types de vidéos autorisés
     */
    public function isValidVideoType(UploadedFile $file): bool
    {
        $allowedTypes = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm'];
        return in_array($file->getMimeType(), $allowedTypes, true);
    }
}
