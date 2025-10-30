<?php

namespace App\Twig;

use App\Entity\EventMedia;
use App\Service\CloudinaryService;
use App\Service\MediaService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CloudinaryExtension extends AbstractExtension
{
    public function __construct(
        private CloudinaryService $cloudinaryService,
        private MediaService $mediaService
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('cloudinary_url', [$this, 'getCloudinaryUrl']),
            new TwigFilter('cloudinary_thumb', [$this, 'getCloudinaryThumbnail']),
            new TwigFilter('cloudinary_optimized', [$this, 'getOptimizedUrl']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cloudinary_image', [$this, 'getCloudinaryUrl']),
            new TwigFunction('event_primary_image', [$this, 'getEventPrimaryImage']),
        ];
    }

    /**
     * Génère une URL Cloudinary optimisée
     */
    public function getCloudinaryUrl(string $publicId, ?int $width = null, ?int $height = null): string
    {
        return $this->cloudinaryService->getOptimizedUrl($publicId, $width, $height);
    }

    /**
     * Génère une URL de thumbnail Cloudinary
     */
    public function getCloudinaryThumbnail(string $publicId, int $size = 200): string
    {
        return $this->cloudinaryService->getThumbnailUrl($publicId, $size);
    }

    /**
     * Génère une URL optimisée depuis un EventMedia
     */
    public function getOptimizedUrl(EventMedia $media, ?int $width = null, ?int $height = null): string
    {
        return $this->mediaService->getOptimizedImageUrl($media, $width, $height);
    }

    /**
     * Récupère l'image principale d'un événement
     */
    public function getEventPrimaryImage($event): ?EventMedia
    {
        return $this->mediaService->getPrimaryImage($event);
    }
}

