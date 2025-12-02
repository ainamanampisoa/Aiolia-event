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

    /**
     * Récupère une variable d'environnement avec plusieurs méthodes de fallback
     */
    private function getEnvVar(string $key): ?string
    {
        // Essayer $_ENV d'abord
        if (isset($_ENV[$key]) && !empty($_ENV[$key])) {
            return trim((string) $_ENV[$key]);
        }
        
        // Essayer $_SERVER
        if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
            return trim((string) $_SERVER[$key]);
        }
        
        // Essayer getenv()
        $value = getenv($key);
        if ($value !== false && !empty($value)) {
            return trim((string) $value);
        }
        
        // Dernier recours : lire directement depuis le fichier .env
        static $envCache = null;
        if ($envCache === null) {
            $envFile = dirname(__DIR__, 2) . '/.env';
            if (file_exists($envFile) && is_readable($envFile)) {
                $envCache = [];
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    // Ignorer les commentaires
                    if (strpos(trim($line), '#') === 0) {
                        continue;
                    }
                    // Parser les lignes KEY=VALUE
                    if (preg_match('/^([^=]+)=(.*)$/', $line, $matches)) {
                        $envCache[trim($matches[1])] = trim($matches[2], '"\'');
                    }
                }
            }
        }
        
        if ($envCache !== null && isset($envCache[$key]) && !empty($envCache[$key])) {
            return trim((string) $envCache[$key]);
        }
        
        return null;
    }

    private function initializeCloudinary(): void
    {
        try {
            $this->attemptedInitialization = true;
            $this->lastInitializationError = null;

            // 1) Si CLOUDINARY_URL est défini, utiliser directement cette URL (format officiel Cloudinary)
            $cloudinaryUrl = $this->getEnvVar('CLOUDINARY_URL');

            if ($cloudinaryUrl && is_string($cloudinaryUrl) && trim($cloudinaryUrl) !== '') {
                // Si on a une URL, créer Cloudinary avec l'URL
                try {
                    $this->logger?->info('Initializing Cloudinary from CLOUDINARY_URL (service initialization)');
                    $this->cloudinary = new Cloudinary($cloudinaryUrl);
                    $this->logger?->info('Cloudinary initialized successfully with URL');
                } catch (\Exception $configException) {
                    $this->lastInitializationError = 'Failed to configure Cloudinary from URL: ' . $configException->getMessage();
                    $this->logger?->error('Cloudinary configuration from URL failed', [
                        'error' => $configException->getMessage(),
                    ]);
                    throw $configException;
                }
            } else {
                // 2) Sinon, fallback sur les 3 variables séparées
                $cloudName = $this->getEnvVar('CLOUDINARY_CLOUD_NAME');
                $apiKey = $this->getEnvVar('CLOUDINARY_API_KEY');
                $apiSecret = $this->getEnvVar('CLOUDINARY_API_SECRET');

                // Valider que les valeurs ne sont pas vides après trim
                $cloudName = trim((string) ($cloudName ?? ''));
                $apiKey = trim((string) ($apiKey ?? ''));
                $apiSecret = trim((string) ($apiSecret ?? ''));
                
                if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
                    $this->logger?->error('Cloudinary credentials not found or empty (service initialization)', [
                        'has_cloud_name' => !empty($cloudName),
                        'has_api_key' => !empty($apiKey),
                        'has_api_secret' => !empty($apiSecret),
                        'has_url' => !empty($cloudinaryUrl),
                        'cloud_name_length' => strlen($cloudName),
                        'api_key_length' => strlen($apiKey),
                        'api_secret_length' => strlen($apiSecret),
                    ]);
                    $this->lastInitializationError = 'Cloudinary credentials are missing or empty';
                    return;
                }

                $this->logger?->info('Cloudinary credentials found (service initialization, separate vars)', [
                    'cloud_name' => $cloudName,
                    'has_api_key' => !empty($apiKey),
                    'has_api_secret' => !empty($apiSecret),
                ]);

                // Initialiser Cloudinary avec les credentials trouvés
                try {
                    // Créer l'instance Cloudinary directement avec la configuration
                    $this->cloudinary = new Cloudinary([
                        'cloud' => [
                            'cloud_name' => $cloudName,
                            'api_key' => $apiKey,
                            'api_secret' => $apiSecret,
                        ],
                        'url' => [
                            'secure' => true,
                        ],
                    ]);
                    
                    $this->logger?->info('Cloudinary initialized successfully with separate vars', [
                        'cloud_name' => $cloudName,
                    ]);
                } catch (\Exception $configException) {
                    $this->lastInitializationError = 'Failed to configure Cloudinary: ' . $configException->getMessage();
                    $this->logger?->error('Cloudinary configuration failed', [
                        'error' => $configException->getMessage(),
                        'cloud_name' => $cloudName,
                        'trace' => $configException->getTraceAsString(),
                    ]);
                    throw $configException;
                }
            }
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
        // Si Cloudinary n'est pas initialisé, essayer de le réinitialiser
        if ($this->cloudinary === null) {
            // Réessayer l'initialisation avec les variables disponibles
            $this->attemptReinitialization();
            
            // Si toujours pas initialisé après réessai, retourner null
            if ($this->cloudinary === null) {
                $cloudName = $this->getEnvVar('CLOUDINARY_CLOUD_NAME');
                $apiKey = $this->getEnvVar('CLOUDINARY_API_KEY');
                $apiSecret = $this->getEnvVar('CLOUDINARY_API_SECRET');
                
                $this->logger?->error('Cloudinary not initialized after reinitialization attempt', [
                    'has_cloud_name' => !empty($cloudName),
                    'has_api_key' => !empty($apiKey),
                    'has_api_secret' => !empty($apiSecret),
                ]);
                return null;
            }
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
     * Tente de réinitialiser Cloudinary
     */
    private function attemptReinitialization(): void
    {
        if ($this->cloudinary !== null) {
            return; // Déjà initialisé
        }
        
        $this->initializeCloudinary();
    }

    /**
     * Informations de debug sur l'initialisation de Cloudinary
     */
    public function getInitializationDebugInfo(): array
    {
        $cloudName = $this->getEnvVar('CLOUDINARY_CLOUD_NAME');
        $apiKey = $this->getEnvVar('CLOUDINARY_API_KEY');
        $apiSecret = $this->getEnvVar('CLOUDINARY_API_SECRET');

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

