<?php

namespace App\Controller\Organisateur;

use App\Entity\Event;
use App\Entity\EventMedia;
use App\Service\Organisateur\MediaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/media', requirements: ['eventId' => '\d+'])]
class EventMediaController extends AbstractController
{
    
    #[Route('', name: 'app_event_media_index', methods: ['GET'])]
    public function index(
        int $eventId,
        EntityManagerInterface $entityManager,
        MediaService $mediaService
    ): Response {
        $event = $entityManager->getRepository(Event::class)->find($eventId);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        $medias = $mediaService->getEventMedias($event);

        return $this->render('event/media/index.html.twig', [
            'event' => $event,
            'medias' => $medias,
        ]);
    }

    
    #[Route('/upload', name: 'app_event_media_upload', methods: ['POST'])]
    public function upload(
        int $eventId,
        Request $request,
        EntityManagerInterface $entityManager,
        MediaService $mediaService
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $event = $entityManager->getRepository(Event::class)->find($eventId);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        
        if ($event->getOrganizer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        
        $file = $request->files->get('file');
        $type = $request->request->get('type', 'image');
        $isPrimary = $request->request->getBoolean('isPrimary', false);

        if ($file) {
            try {
                $media = $mediaService->uploadEventMedia(
                    $event,
                    $file,
                    $type,
                    $this->getUser(),
                    $isPrimary
                );

                $this->addFlash('success', 'Média uploadé avec succès');

                return $this->redirectToRoute('app_event_media_index', ['eventId' => $eventId]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Aucun fichier sélectionné');
        }

        return $this->redirectToRoute('app_event_media_index', ['eventId' => $eventId]);
    }

    
    #[Route('/{id}/delete', name: 'app_event_media_delete', methods: ['POST'])]
    public function delete(
        int $eventId,
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        MediaService $mediaService
    ): Response {
        $media = $entityManager->getRepository(EventMedia::class)->find($id);
        
        if (!$media || $media->getEvent()->getId() != $eventId) {
            throw $this->createNotFoundException('Média non trouvé');
        }

        $event = $media->getEvent();

        
        if ($event->getOrganizer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        
        if ($this->isCsrfTokenValid('delete'.$media->getId(), $request->request->get('_token'))) {
            $mediaService->deleteMedia($media);
            $this->addFlash('success', 'Média supprimé avec succès');
        }

        return $this->redirectToRoute('app_event_media_index', ['eventId' => $eventId]);
    }

    
    #[Route('/{id}/set-primary', name: 'app_event_media_set_primary', methods: ['POST'])]
    public function setPrimary(
        int $eventId,
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        MediaService $mediaService
    ): Response {
        $media = $entityManager->getRepository(EventMedia::class)->find($id);
        
        if (!$media || $media->getEvent()->getId() != $eventId) {
            throw $this->createNotFoundException('Média non trouvé');
        }

        $event = $media->getEvent();

        
        if ($event->getOrganizer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        
        if ($this->isCsrfTokenValid('set-primary'.$media->getId(), $request->request->get('_token'))) {
            $mediaService->setPrimaryImage($event, $media);
            $this->addFlash('success', 'Image principale définie avec succès');
        }

        return $this->redirectToRoute('app_event_media_index', ['eventId' => $eventId]);
    }

    
    #[Route('/reorder', name: 'app_event_media_reorder', methods: ['POST'])]
    public function reorder(
        int $eventId,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $event = $entityManager->getRepository(Event::class)->find($eventId);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        
        if ($event->getOrganizer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $order = $request->request->all('order');
        $mediaRepository = $entityManager->getRepository(EventMedia::class);

        foreach ($order as $position => $mediaId) {
            $media = $mediaRepository->find($mediaId);
            if ($media && $media->getEvent() === $event) {
                $media->setDisplayOrder($position);
            }
        }

        $entityManager->flush();

        return $this->json(['success' => true]);
    }
}

