<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Entity\EventMedia;
use App\Entity\User;
use App\Service\Admin\CloudinaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CloudinaryService $cloudinaryService
    ) {
    }

    
    public function uploadEventMedia(
        Event $event,
        UploadedFile $file,
        string $type = 'image',
        bool $isPrimary = false,
        ?int $displayOrder = null
    ): EventMedia {
        
        // Si l'événement n'a pas encore d'ID, on utilise un dossier temporaire
        $eventId = $event->getId() ?? 'temp';
        $folder = match($type) {
            'image' => 'aiolia-event/events/' . $eventId . '/images',
            'video' => 'aiolia-event/events/' . $eventId . '/videos',
            default => 'aiolia-event/events/' . $eventId . '/documents',
        };

        $uploadResult = match($type) {
            'image' => $this->cloudinaryService->uploadImage($file, $folder),
            'video' => $this->cloudinaryService->uploadVideo($file, $folder),
            default => $this->cloudinaryService->uploadFile($file, $folder),
        };

        if (!$uploadResult['success']) {
            throw new \Exception('Erreur lors de l\'upload sur Cloudinary: ' . $uploadResult['error']);
        }

        $media = new EventMedia();
        $media->setEvent($event);
        $media->setMediaType($type);
        $media->setUrl($uploadResult['url']);
        $media->setIsMainPoster($isPrimary);
        
        // Définir le texte alternatif avec le nom du fichier
        $media->setAltText($file->getClientOriginalName());
        
        // Définir l'ordre d'affichage si fourni
        if ($displayOrder !== null) {
            $media->setDisplayOrder($displayOrder);
        }

        if ($isPrimary) {
            $this->setPrimaryImage($event, $media);
            // Mettre à jour urlImageCouverture dans l'événement
            $event->setUrlImageCouverture($uploadResult['url']);
            
            // IMPORTANT: Persister la modification sur l'Event
            $this->entityManager->persist($event);
        }

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    
    public function deleteMedia(EventMedia $media): void
    {
        
        $publicId = $this->extractPublicIdFromUrl($media->getUrl());
        
        if ($publicId) {
            
            $resourceType = match($media->getMediaType()) {
                'video' => 'video',
                'document' => 'raw',
                default => 'image',
            };
            
            
            $this->cloudinaryService->deleteFile($publicId, $resourceType);
        }

        $this->entityManager->remove($media);
        $this->entityManager->flush();
    }

    
    private function extractPublicIdFromUrl(string $url): ?string
    {
        
        if (preg_match('#/upload/(?:v\d+/)?(.+)\.\w+$#', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    
    public function setPrimaryImage(Event $event, EventMedia $primaryMedia): void
    {
        
        $mediaRepository = $this->entityManager->getRepository(EventMedia::class);
        $existingPrimary = $mediaRepository->findBy([
            'event' => $event,
            'isMainPoster' => true,
        ]);

        foreach ($existingPrimary as $media) {
            if ($media->getId() !== $primaryMedia->getId()) {
                $media->setIsMainPoster(false);
            }
        }

        $primaryMedia->setIsMainPoster(true);
        $this->entityManager->flush();
    }

    
    public function getOptimizedImageUrl(EventMedia $media, int $width = 0, int $height = 0): string
    {
        $url = $media->getUrl();
        
        if ($media->getMediaType() !== 'image') {
            return $url;
        }
        
        // Si c'est une URL Cloudinary, on peut ajouter des transformations
        // Pour l'instant, on retourne l'URL de base
        // Les transformations Cloudinary peuvent être ajoutées directement dans l'URL si nécessaire
        return $url;
    }

    
    public function getThumbnailUrl(EventMedia $media, int $size = 200): string
    {
        $url = $media->getUrl();
        
        if ($media->getMediaType() !== 'image') {
            return $url;
        }
        
        // Si c'est une URL Cloudinary, on peut ajouter des transformations de thumbnail
        // Pour l'instant, on retourne l'URL de base
        // Les transformations Cloudinary peuvent être ajoutées directement dans l'URL si nécessaire
        return $url;
    }

    
    public function getEventMedias(Event $event, string $type = ''): array
    {
        $mediaRepository = $this->entityManager->getRepository(EventMedia::class);
        
        $criteria = ['event' => $event];
        if ($type) {
            $criteria['mediaType'] = $type;
        }
        
        // Utiliser createQueryBuilder pour mieux contrôler l'ordre
        $queryBuilder = $mediaRepository->createQueryBuilder('m')
            ->where('m.event = :event')
            ->setParameter('event', $event);
        
        if ($type) {
            $queryBuilder->andWhere('m.mediaType = :type')
                ->setParameter('type', $type);
        }
        
        // Trier par isMainPoster DESC (les principaux d'abord) puis par displayOrder
        return $queryBuilder->orderBy('m.isMainPoster', 'DESC')
            ->addOrderBy('m.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getPrimaryImage(Event $event): ?EventMedia
    {
        $mediaRepository = $this->entityManager->getRepository(EventMedia::class);
        return $mediaRepository->findOneBy([
            'event' => $event,
            'mediaType' => 'image',
            'isMainPoster' => true,
        ]);
    }
}

