<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Psr\Log\LoggerInterface;

class CloudinaryService
{
    private ?Cloudinary $cloudinary = null;
    private bool $attemptedInitialization = false;
    private ?string $lastInitializationError = null;

    public function __construct(
        private readonly ?LoggerInterface $logger = null
    ) {
        $this->initializeCloudinary();
    }

    private function initializeCloudinary(): void
    {
        try {
            $this->attemptedInitialization = true;
            $this->lastInitializationError = null;

            // 1) Si CLOUDINARY_URL est défini, utiliser directement cette URL (format officiel Cloudinary)
            $cloudinaryUrl = $_ENV['CLOUDINARY_URL']
                ?? $_SERVER['CLOUDINARY_URL']
                ?? getenv('CLOUDINARY_URL')
                ?: null;

            if ($cloudinaryUrl && is_string($cloudinaryUrl) && trim($cloudinaryUrl) !== '') {
                $this->logger?->info('Initializing Cloudinary from CLOUDINARY_URL (service initialization)');
                Configuration::instance($cloudinaryUrl);
            } else {
                // 2) Sinon, fallback sur les 3 variables séparées
                $cloudName = $_ENV['CLOUDINARY_CLOUD_NAME']
                    ?? $_SERVER['CLOUDINARY_CLOUD_NAME']
                    ?? getenv('CLOUDINARY_CLOUD_NAME')
                    ?: null;

                $apiKey = $_ENV['CLOUDINARY_API_KEY']
                    ?? $_SERVER['CLOUDINARY_API_KEY']
                    ?? getenv('CLOUDINARY_API_KEY')
                    ?: null;

                $apiSecret = $_ENV['CLOUDINARY_API_SECRET']
                    ?? $_SERVER['CLOUDINARY_API_SECRET']
                    ?? getenv('CLOUDINARY_API_SECRET')
                    ?: null;

                if (!$cloudName || !$apiKey || !$apiSecret) {
                    $this->logger?->error('Cloudinary credentials not found (service initialization)', [
                        'has_cloud_name' => !empty($cloudName),
                        'has_api_key' => !empty($apiKey),
                        'has_api_secret' => !empty($apiSecret),
                        'has_url' => !empty($cloudinaryUrl),
                    ]);
                    return;
                }

                $this->logger?->info('Cloudinary credentials found (service initialization, separate vars)', [
                    'cloud_name' => $cloudName,
                    'has_api_key' => !empty($apiKey),
                    'has_api_secret' => !empty($apiSecret),
                ]);

                // Initialiser Cloudinary avec les credentials trouvés
                Configuration::instance([
                    'cloud' => [
                        'cloud_name' => $cloudName,
                        'api_key' => $apiKey,
                        'api_secret' => $apiSecret,
                    ],
                    'url' => [
                        'secure' => true,
                    ],
                ]);
            }

            $this->cloudinary = new Cloudinary();
            $this->logger?->info('Cloudinary initialized successfully (service)', [
                'from_url' => !empty($cloudinaryUrl),
            ]);
        } catch (\Exception $e) {
            $this->lastInitializationError = $e->getMessage();
            $this->logger?->error('Failed to initialize Cloudinary (service): ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }

    /**
     * Upload une image vers Cloudinary
     *
     * @param string $filePath Chemin du fichier à uploader
     * @param string $folder Dossier dans Cloudinary (optionnel)
     * @param array $options Options supplémentaires (transformation, tags, etc.)
     * @return array|null Retourne les données de l'upload ou null en cas d'erreur
     */
    public function uploadImage(string $filePath, string $folder = 'avatars', array $options = []): ?array
    {
        if ($this->cloudinary === null) {
            $this->logger?->error('Cloudinary not initialized');
            return null;
        }

        try {
            $defaultOptions = [
                'folder' => $folder,
                'resource_type' => 'image',
            ];

            // Ajouter les transformations seulement si elles ne sont pas déjà définies
            if (!isset($options['transformation'])) {
                $defaultOptions['transformation'] = [
                    ['width' => 400, 'height' => 400, 'crop' => 'fill', 'gravity' => 'auto'],
                    ['quality' => 'auto', 'fetch_format' => 'auto']
                ];
            }

            $uploadOptions = array_merge($defaultOptions, $options);
            
            $this->logger?->debug('Uploading to Cloudinary', [
                'file' => $filePath,
                'folder' => $folder,
                'options' => $uploadOptions
            ]);

            $result = $this->cloudinary->uploadApi()->upload($filePath, $uploadOptions);

            $this->logger?->debug('Cloudinary upload successful', [
                'public_id' => $result['public_id'] ?? null,
                'url' => $result['secure_url'] ?? null
            ]);

            return [
                'public_id' => $result['public_id'] ?? null,
                'secure_url' => $result['secure_url'] ?? $result['url'] ?? null,
                'url' => $result['url'] ?? null,
                'format' => $result['format'] ?? null,
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'bytes' => $result['bytes'] ?? null,
            ];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->logger?->error('Cloudinary upload failed: ' . $errorMessage);
            $this->logger?->error('Stack trace: ' . $e->getTraceAsString());
            
            // Si c'est une erreur de configuration, logger plus d'infos
            if (str_contains($errorMessage, 'cloud_name') || str_contains($errorMessage, 'api_key')) {
                $this->logger?->error('Cloudinary configuration issue detected');
            }
            
            return null;
        }
    }

    /**
     * Upload depuis un fichier uploadé (UploadedFile Symfony)
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array|null
     */
    public function uploadUploadedFile($file, string $folder = 'avatars', array $options = []): ?array
    {
        if ($this->cloudinary === null) {
            $cloudName = $_ENV['CLOUDINARY_CLOUD_NAME'] ?? null;
            $apiKey = $_ENV['CLOUDINARY_API_KEY'] ?? null;
            $apiSecret = $_ENV['CLOUDINARY_API_SECRET'] ?? null;
            
            $this->logger?->error('Cloudinary not initialized', [
                'has_cloud_name' => !empty($cloudName),
                'has_api_key' => !empty($apiKey),
                'has_api_secret' => !empty($apiSecret),
            ]);
            return null;
        }

        try {
            // Vérifier que le fichier existe et est valide
            $filePath = $file->getPathname();
            if (!is_file($filePath) || !is_readable($filePath)) {
                $this->logger?->error('File is not readable', [
                    'path' => $filePath,
                    'exists' => file_exists($filePath),
                    'readable' => is_readable($filePath),
                ]);
                return null;
            }

            $this->logger?->debug('Uploading file from: ' . $filePath);
            
            $result = $this->uploadImage($filePath, $folder, $options);
            
            if ($result) {
                $this->logger?->debug('Upload successful', [
                    'url' => $result['secure_url'] ?? 'no URL',
                    'public_id' => $result['public_id'] ?? 'no public_id',
                ]);
            } else {
                $this->logger?->error('Upload returned null - check CloudinaryService::uploadImage for details');
            }
            
            return $result;
        } catch (\Throwable $e) {
            $this->logger?->error('Cloudinary upload from UploadedFile failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Supprime une image de Cloudinary
     *
     * @param string $publicId Public ID de l'image à supprimer
     * @return bool
     */
    public function deleteImage(string $publicId): bool
    {
        if ($this->cloudinary === null) {
            return false;
        }

        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);
            return $result['result'] === 'ok';
        } catch (\Exception $e) {
            $this->logger?->error('Cloudinary delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Génère une URL optimisée
     *
     * @param string $publicId
     * @param int|null $width
     * @param int|null $height
     * @return string
     */
    public function getOptimizedUrl(string $publicId, ?int $width = null, ?int $height = null): string
    {
        if ($this->cloudinary === null) {
            return '';
        }

        try {
            $transformation = [];
            if ($width) {
                $transformation['width'] = $width;
            }
            if ($height) {
                $transformation['height'] = $height;
            }
            if (!empty($transformation)) {
                $transformation['crop'] = 'fill';
                $transformation['quality'] = 'auto';
                $transformation['fetch_format'] = 'auto';
            }

            return $this->cloudinary->image($publicId)->resize($transformation)->toUrl();
        } catch (\Exception $e) {
            $this->logger?->error('Cloudinary URL generation failed: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Extrait le public_id depuis une URL Cloudinary
     *
     * @param string $url
     * @return string|null
     */
    public function extractPublicIdFromUrl(string $url): ?string
    {
        // Format: https://res.cloudinary.com/{cloud_name}/image/upload/{version}/{public_id}.{format}
        if (preg_match('/\/image\/upload\/(?:v\d+\/)?([^\.]+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Vérifie si Cloudinary est initialisé
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->cloudinary !== null;
    }

    /**
     * Informations de debug sur l'initialisation de Cloudinary
     */
    public function getInitializationDebugInfo(): array
    {
        $cloudName = $_ENV['CLOUDINARY_CLOUD_NAME']
            ?? $_SERVER['CLOUDINARY_CLOUD_NAME']
            ?? getenv('CLOUDINARY_CLOUD_NAME')
            ?: null;

        $apiKey = $_ENV['CLOUDINARY_API_KEY']
            ?? $_SERVER['CLOUDINARY_API_KEY']
            ?? getenv('CLOUDINARY_API_KEY')
            ?: null;

        $apiSecret = $_ENV['CLOUDINARY_API_SECRET']
            ?? $_SERVER['CLOUDINARY_API_SECRET']
            ?? getenv('CLOUDINARY_API_SECRET')
            ?: null;

        return [
            'attemptedInitialization' => $this->attemptedInitialization,
            'isInitialized' => $this->isInitialized(),
            'lastInitializationError' => $this->lastInitializationError,
            'env' => [
                'CLOUDINARY_CLOUD_NAME_set' => !empty($cloudName),
                'CLOUDINARY_API_KEY_set' => !empty($apiKey),
                'CLOUDINARY_API_SECRET_set' => !empty($apiSecret),
            ],
            'classes' => [
                'Cloudinary_Cloudinary_exists' => class_exists(\Cloudinary\Cloudinary::class),
                'Cloudinary_Configuration_exists' => class_exists(\Cloudinary\Configuration\Configuration::class),
            ],
        ];
    }
}

