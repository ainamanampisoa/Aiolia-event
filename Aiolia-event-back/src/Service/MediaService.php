<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\EventMedia;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CloudinaryService $cloudinaryService
    ) {
    }

    /**
     * Upload un fichier média pour un événement
     */
    public function uploadEventMedia(
        Event $event,
        UploadedFile $file,
        string $type,
        User $uploadedBy,
        bool $isPrimary = false
    ): EventMedia {
        // Déterminer le dossier Cloudinary basé sur le type
        $folder = match($type) {
            'image' => 'aiolia-event/events/' . $event->getId() . '/images',
            'video' => 'aiolia-event/events/' . $event->getId() . '/videos',
            default => 'aiolia-event/events/' . $event->getId() . '/documents',
        };

        // Upload sur Cloudinary selon le type
        $uploadResult = match($type) {
            'image' => $this->cloudinaryService->uploadImage($file, $folder),
            'video' => $this->cloudinaryService->uploadVideo($file, $folder),
            default => $this->cloudinaryService->uploadFile($file, $folder),
        };

        if (!$uploadResult['success']) {
            throw new \Exception('Erreur lors de l\'upload sur Cloudinary: ' . $uploadResult['error']);
        }

        // Créer l'entité EventMedia
        $media = new EventMedia();
        $media->setEvent($event);
        $media->setMediaType($type);
        $media->setFileUrl($uploadResult['url']);
        $media->setFileName($file->getClientOriginalName());
        $media->setFileSize($uploadResult['size']);
        $media->setMimeType($file->getMimeType());
        $media->setIsPrimary($isPrimary);
        $media->setUploadedBy($uploadedBy);

        // Si c'est l'image principale, désactiver les autres
        if ($isPrimary) {
            $this->setPrimaryImage($event, $media);
        }

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    /**
     * Supprime un média
     */
    public function deleteMedia(EventMedia $media): void
    {
        // Extraire le public_id de l'URL Cloudinary
        $publicId = $this->extractPublicIdFromUrl($media->getFileUrl());
        
        if ($publicId) {
            // Déterminer le type de ressource
            $resourceType = match($media->getMediaType()) {
                'video' => 'video',
                'document' => 'raw',
                default => 'image',
            };
            
            // Supprimer de Cloudinary
            $this->cloudinaryService->deleteFile($publicId, $resourceType);
        }

        $this->entityManager->remove($media);
        $this->entityManager->flush();
    }

    /**
     * Extrait le public_id depuis une URL Cloudinary
     */
    private function extractPublicIdFromUrl(string $url): ?string
    {
        // Format URL Cloudinary: https://res.cloudinary.com/{cloud_name}/{resource_type}/upload/{version}/{public_id}.{format}
        if (preg_match('#/upload/(?:v\d+/)?(.+)\.\w+$#', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Définit une image comme principale
     */
    public function setPrimaryImage(Event $event, EventMedia $primaryMedia): void
    {
        // Désactiver toutes les autres images principales
        $mediaRepository = $this->entityManager->getRepository(EventMedia::class);
        $existingPrimary = $mediaRepository->findBy([
            'event' => $event,
            'isPrimary' => true,
        ]);

        foreach ($existingPrimary as $media) {
            if ($media->getId() !== $primaryMedia->getId()) {
                $media->setIsPrimary(false);
            }
        }

        $primaryMedia->setIsPrimary(true);
        $this->entityManager->flush();
    }

    /**
     * Génère une URL optimisée depuis Cloudinary
     */
    public function getOptimizedImageUrl(EventMedia $media, int $width = 0, int $height = 0): string
    {
        $publicId = $this->extractPublicIdFromUrl($media->getFileUrl());
        
        if ($publicId && $media->getMediaType() === 'image') {
            return $this->cloudinaryService->getOptimizedUrl($publicId, $width, $height);
        }
        
        return $media->getFileUrl();
    }

    /**
     * Génère une URL de thumbnail
     */
    public function getThumbnailUrl(EventMedia $media, int $size = 200): string
    {
        $publicId = $this->extractPublicIdFromUrl($media->getFileUrl());
        
        if ($publicId && $media->getMediaType() === 'image') {
            return $this->cloudinaryService->getThumbnailUrl($publicId, $size);
        }
        
        return $media->getFileUrl();
    }

    /**
     * Récupère tous les médias d'un événement
     */
    public function getEventMedias(Event $event, string $type = ''): array
    {
        $mediaRepository = $this->entityManager->getRepository(EventMedia::class);
        
        if ($type) {
            return $mediaRepository->findBy(
                ['event' => $event, 'mediaType' => $type],
                ['displayOrder' => 'ASC']
            );
        }

        return $mediaRepository->findBy(
            ['event' => $event],
            ['displayOrder' => 'ASC']
        );
    }

    /**
     * Récupère l'image principale d'un événement
     */
    public function getPrimaryImage(Event $event): ?EventMedia
    {
        $mediaRepository = $this->entityManager->getRepository(EventMedia::class);
        return $mediaRepository->findOneBy([
            'event' => $event,
            'mediaType' => 'image',
            'isPrimary' => true,
        ]);
    }
}

