<?php

namespace App\Service;

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
     * Upload une image
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
            $defaultOptions = [
                'folder' => $folder,
                'resource_type' => 'image',
                'transformation' => [
                    'quality' => 'auto',
                    'fetch_format' => 'auto',
                ],
            ];

            $uploadOptions = array_replace_recursive($defaultOptions, $options);

            $result = $this->getCloudinary()->uploadApi()->upload(
                $file->getPathname(),
                $uploadOptions
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
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($file->getMimeType(), $allowedTypes, true);
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
