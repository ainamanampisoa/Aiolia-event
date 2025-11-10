<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Envoie une notification par email lors d'un changement de statut
     */
    public function sendStatusChangeNotification(User $user, string $oldStatus, string $newStatus, ?string $comment = null): void
    {
        $statusLabels = [
            'active' => 'actif',
            'pending_validation' => 'en attente de validation',
            'rejected' => 'rejeté',
            'suspended' => 'suspendu',
        ];

        $oldStatusLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newStatusLabel = $statusLabels[$newStatus] ?? $newStatus;

        $email = (new TemplatedEmail())
            ->from('noreply@aiolia-event.com')
            ->to($user->getEmail())
            ->subject('Changement de statut de votre compte - Aiolia Event')
            ->htmlTemplate('emails/user_status_change.html.twig')
            ->context([
                'user' => $user,
                'oldStatus' => $oldStatusLabel,
                'newStatus' => $newStatusLabel,
                'comment' => $comment,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas le processus
            error_log('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
        }
    }

    /**
     * Envoie une notification lors de l'approbation d'une demande de validation
     */
    public function sendValidationApprovedNotification(User $user, string $newRole, ?string $comment = null): void
    {
        $roleLabels = [
            'organizer' => 'Organisateur',
            'admin' => 'Administrateur',
            'user' => 'Utilisateur',
        ];

        $roleLabel = $roleLabels[$newRole] ?? $newRole;

        $email = (new TemplatedEmail())
            ->from('noreply@aiolia-event.com')
            ->to($user->getEmail())
            ->subject('Votre demande de validation a été approuvée - Aiolia Event')
            ->htmlTemplate('emails/validation_approved.html.twig')
            ->context([
                'user' => $user,
                'newRole' => $roleLabel,
                'comment' => $comment,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
        }
    }

    /**
     * Envoie une notification lors du rejet d'une demande de validation
     */
    public function sendValidationRejectedNotification(User $user, string $requestedRole, string $reason): void
    {
        $roleLabels = [
            'organizer' => 'Organisateur',
            'admin' => 'Administrateur',
            'user' => 'Utilisateur',
        ];

        $roleLabel = $roleLabels[$requestedRole] ?? $requestedRole;

        $email = (new TemplatedEmail())
            ->from('noreply@aiolia-event.com')
            ->to($user->getEmail())
            ->subject('Votre demande de validation a été rejetée - Aiolia Event')
            ->htmlTemplate('emails/validation_rejected.html.twig')
            ->context([
                'user' => $user,
                'requestedRole' => $roleLabel,
                'reason' => $reason,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
        }
    }

    /**
     * Envoie une notification lors d'un changement de rôle
     */
    public function sendRoleChangeNotification(User $user, string $oldRole, string $newRole): void
    {
        $roleLabels = [
            'organizer' => 'Organisateur',
            'admin' => 'Administrateur',
            'user' => 'Utilisateur',
        ];

        $oldRoleLabel = $roleLabels[$oldRole] ?? $oldRole;
        $newRoleLabel = $roleLabels[$newRole] ?? $newRole;

        $email = (new TemplatedEmail())
            ->from('noreply@aiolia-event.com')
            ->to($user->getEmail())
            ->subject('Changement de rôle de votre compte - Aiolia Event')
            ->htmlTemplate('emails/role_change.html.twig')
            ->context([
                'user' => $user,
                'oldRole' => $oldRoleLabel,
                'newRole' => $newRoleLabel,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
        }
    }
}

