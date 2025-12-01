<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Psr\Log\LoggerInterface;

class CloudinaryService
{
    private ?Cloudinary $cloudinary = null;

    public function __construct(
        private readonly ?LoggerInterface $logger = null
    ) {
        $this->initializeCloudinary();
    }

    private function initializeCloudinary(): void
    {
        try {
            $cloudName = $_ENV['CLOUDINARY_CLOUD_NAME'] ?? null;
            $apiKey = $_ENV['CLOUDINARY_API_KEY'] ?? null;
            $apiSecret = $_ENV['CLOUDINARY_API_SECRET'] ?? null;

            if (!$cloudName || !$apiKey || !$apiSecret) {
                $this->logger?->warning('Cloudinary credentials not found in environment variables');
                return;
            }

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
        } catch (\Exception $e) {
            $this->logger?->error('Failed to initialize Cloudinary: ' . $e->getMessage());
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
                'transformation' => [
                    ['width' => 400, 'height' => 400, 'crop' => 'fill', 'gravity' => 'face'],
                    ['quality' => 'auto', 'fetch_format' => 'auto']
                ]
            ];

            $uploadOptions = array_merge($defaultOptions, $options);

            $result = $this->cloudinary->uploadApi()->upload($filePath, $uploadOptions);

            return [
                'public_id' => $result['public_id'],
                'secure_url' => $result['secure_url'],
                'url' => $result['url'],
                'format' => $result['format'],
                'width' => $result['width'],
                'height' => $result['height'],
                'bytes' => $result['bytes'],
            ];
        } catch (\Exception $e) {
            $this->logger?->error('Cloudinary upload failed: ' . $e->getMessage());
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
            $this->logger?->error('Cloudinary not initialized');
            return null;
        }

        try {
            $tempPath = $file->getPathname();
            return $this->uploadImage($tempPath, $folder, $options);
        } catch (\Exception $e) {
            $this->logger?->error('Cloudinary upload from UploadedFile failed: ' . $e->getMessage());
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
}

