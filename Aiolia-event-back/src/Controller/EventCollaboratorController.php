<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventCollaborator;
use App\Entity\User;
use App\Repository\EventCollaboratorRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/collaborators', requirements: ['eventId' => '\d+'])]
class EventCollaboratorController extends AbstractController
{
    /**
     * Liste les collaborateurs d'un événement
     */
    #[Route('', name: 'app_event_collaborator_index', methods: ['GET'])]
    public function index(
        int $eventId,
        EventCollaboratorRepository $collaboratorRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $event = $entityManager->getRepository(Event::class)->find($eventId);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        // Vérifier les permissions
        if ($event->getOrganizer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $collaborators = $collaboratorRepository->findByEvent($event);

        return $this->render('event/collaborators/index.html.twig', [
            'event' => $event,
            'collaborators' => $collaborators,
        ]);
    }

    /**
     * Ajoute un collaborateur à un événement
     */
    #[Route('/add', name: 'app_event_collaborator_add', methods: ['POST'])]
    public function add(
        int $eventId,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {
        $event = $entityManager->getRepository(Event::class)->find($eventId);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        // Vérifier les permissions
        if ($event->getOrganizer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $userId = $request->request->get('userId');
        $role = $request->request->get('role', 'editor');

        $user = $userRepository->find($userId);
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('app_event_collaborator_index', ['eventId' => $eventId]);
        }

        // Vérifier si l'utilisateur n'est pas déjà collaborateur
        $collaboratorRepository = $entityManager->getRepository(EventCollaborator::class);
        $existing = $collaboratorRepository->findCollaborator($event, $user);

        if ($existing) {
            $this->addFlash('error', 'Cet utilisateur est déjà collaborateur');
            return $this->redirectToRoute('app_event_collaborator_index', ['eventId' => $eventId]);
        }

        $collaborator = new EventCollaborator();
        $collaborator->setEvent($event);
        $collaborator->setUser($user);
        $collaborator->setRole($role);
        $collaborator->setInvitedBy($this->getUser());

        // Définir les permissions selon le rôle
        $this->setPermissionsByRole($collaborator, $role);

        $entityManager->persist($collaborator);
        $entityManager->flush();

        $this->addFlash('success', 'Collaborateur ajouté avec succès');

        return $this->redirectToRoute('app_event_collaborator_index', ['eventId' => $eventId]);
    }

    /**
     * Modifie les permissions d'un collaborateur
     */
    #[Route('/{id}/edit', name: 'app_event_collaborator_edit', methods: ['POST'])]
    public function edit(
        int $eventId,
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $collaborator = $entityManager->getRepository(EventCollaborator::class)->find($id);
        
        if (!$collaborator || $collaborator->getEvent()->getId() != $eventId) {
            throw $this->createNotFoundException('Collaborateur non trouvé');
        }

        $event = $collaborator->getEvent();

        // Vérifier les permissions
        if ($event->getOrganizer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $role = $request->request->get('role');
        if ($role) {
            $collaborator->setRole($role);
            $this->setPermissionsByRole($collaborator, $role);
        }

        // Permissions personnalisées
        if ($request->request->has('canEditEvent')) {
            $collaborator->setCanEditEvent((bool) $request->request->get('canEditEvent'));
        }
        if ($request->request->has('canManageTickets')) {
            $collaborator->setCanManageTickets((bool) $request->request->get('canManageTickets'));
        }
        if ($request->request->has('canViewSales')) {
            $collaborator->setCanViewSales((bool) $request->request->get('canViewSales'));
        }
        if ($request->request->has('canManageTeam')) {
            $collaborator->setCanManageTeam((bool) $request->request->get('canManageTeam'));
        }
        if ($request->request->has('canSendNotifications')) {
            $collaborator->setCanSendNotifications((bool) $request->request->get('canSendNotifications'));
        }

        $entityManager->flush();

        $this->addFlash('success', 'Permissions modifiées avec succès');

        return $this->redirectToRoute('app_event_collaborator_index', ['eventId' => $eventId]);
    }

    /**
     * Supprime un collaborateur
     */
    #[Route('/{id}/delete', name: 'app_event_collaborator_delete', methods: ['POST'])]
    public function delete(
        int $eventId,
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $collaborator = $entityManager->getRepository(EventCollaborator::class)->find($id);
        
        if (!$collaborator || $collaborator->getEvent()->getId() != $eventId) {
            throw $this->createNotFoundException('Collaborateur non trouvé');
        }

        $event = $collaborator->getEvent();

        // Vérifier les permissions
        if ($event->getOrganizer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete'.$collaborator->getId(), $request->request->get('_token'))) {
            $entityManager->remove($collaborator);
            $entityManager->flush();

            $this->addFlash('success', 'Collaborateur supprimé avec succès');
        }

        return $this->redirectToRoute('app_event_collaborator_index', ['eventId' => $eventId]);
    }

    /**
     * Active/Désactive un collaborateur
     */
    #[Route('/{id}/toggle', name: 'app_event_collaborator_toggle', methods: ['POST'])]
    public function toggle(
        int $eventId,
        int $id,
        EntityManagerInterface $entityManager
    ): Response {
        $collaborator = $entityManager->getRepository(EventCollaborator::class)->find($id);
        
        if (!$collaborator || $collaborator->getEvent()->getId() != $eventId) {
            throw $this->createNotFoundException('Collaborateur non trouvé');
        }

        $event = $collaborator->getEvent();

        // Vérifier les permissions
        if ($event->getOrganizer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $collaborator->setIsActive(!$collaborator->isActive());
        $entityManager->flush();

        $status = $collaborator->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Collaborateur {$status} avec succès");

        return $this->redirectToRoute('app_event_collaborator_index', ['eventId' => $eventId]);
    }

    /**
     * Définit les permissions selon le rôle
     */
    private function setPermissionsByRole(EventCollaborator $collaborator, string $role): void
    {
        switch ($role) {
            case 'owner':
                $collaborator->setCanEditEvent(true);
                $collaborator->setCanManageTickets(true);
                $collaborator->setCanViewSales(true);
                $collaborator->setCanManageTeam(true);
                $collaborator->setCanSendNotifications(true);
                break;
            
            case 'admin':
                $collaborator->setCanEditEvent(true);
                $collaborator->setCanManageTickets(true);
                $collaborator->setCanViewSales(true);
                $collaborator->setCanManageTeam(true);
                $collaborator->setCanSendNotifications(true);
                break;
            
            case 'editor':
                $collaborator->setCanEditEvent(true);
                $collaborator->setCanManageTickets(true);
                $collaborator->setCanViewSales(true);
                $collaborator->setCanManageTeam(false);
                $collaborator->setCanSendNotifications(true);
                break;
            
            case 'viewer':
                $collaborator->setCanEditEvent(false);
                $collaborator->setCanManageTickets(false);
                $collaborator->setCanViewSales(true);
                $collaborator->setCanManageTeam(false);
                $collaborator->setCanSendNotifications(false);
                break;
        }
    }
}

