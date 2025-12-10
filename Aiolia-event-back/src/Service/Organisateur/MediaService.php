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
        string $type,
        User $uploadedBy,
        bool $isPrimary = false
    ): EventMedia {
        
        $folder = match($type) {
            'image' => 'aiolia-event/events/' . $event->getId() . '/images',
            'video' => 'aiolia-event/events/' . $event->getId() . '/videos',
            default => 'aiolia-event/events/' . $event->getId() . '/documents',
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
        $media->setFileUrl($uploadResult['url']);
        $media->setFileName($file->getClientOriginalName());
        $media->setFileSize($uploadResult['size']);
        $media->setMimeType($file->getMimeType());
        $media->setIsPrimary($isPrimary);
        $media->setUploadedBy($uploadedBy);

        
        if ($isPrimary) {
            $this->setPrimaryImage($event, $media);
        }

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    
    public function deleteMedia(EventMedia $media): void
    {
        
        $publicId = $this->extractPublicIdFromUrl($media->getFileUrl());
        
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

    
    public function getOptimizedImageUrl(EventMedia $media, int $width = 0, int $height = 0): string
    {
        $publicId = $this->extractPublicIdFromUrl($media->getFileUrl());
        
        if ($publicId && $media->getMediaType() === 'image') {
            return $this->cloudinaryService->getOptimizedUrl($publicId, $width, $height);
        }
        
        return $media->getFileUrl();
    }

    
    public function getThumbnailUrl(EventMedia $media, int $size = 200): string
    {
        $publicId = $this->extractPublicIdFromUrl($media->getFileUrl());
        
        if ($publicId && $media->getMediaType() === 'image') {
            return $this->cloudinaryService->getThumbnailUrl($publicId, $size);
        }
        
        return $media->getFileUrl();
    }

    
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

