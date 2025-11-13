<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        #[Autowire(env: 'MAIL_FROM_ADDRESS')]
        private string $fromAddress,
        #[Autowire(env: 'MAIL_FROM_NAME')]
        private ?string $fromName = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Envoie une notification par email lors d'un changement de statut
     */
    public function sendStatusChangeNotification(User $user, string $oldStatus, string $newStatus, ?string $comment = null): bool
    {
        $statusLabels = [
            'active' => 'actif',
            'pending_validation' => 'en attente de validation',
            'rejected' => 'rejeté',
        ];

        $oldStatusLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newStatusLabel = $statusLabels[$newStatus] ?? $newStatus;

        $email = $this->createBaseEmail()
            ->to($user->getEmail())
            ->subject('Changement de statut de votre compte - Aiolia Event')
            ->htmlTemplate('emails/user_status_change.html.twig')
            ->context([
                'user' => $user,
                'oldStatus' => $oldStatusLabel,
                'newStatus' => $newStatusLabel,
                'comment' => $comment,
            ]);

        return $this->sendEmail($email);
    }

    /**
     * Envoie une notification lors de l'approbation d'une demande de validation
     */
    public function sendValidationApprovedNotification(User $user, string $newRole, ?string $comment = null): bool
    {
        $roleLabels = [
            'organizer' => 'Organisateur',
            'admin' => 'Administrateur',
            'user' => 'Utilisateur',
        ];

        $roleLabel = $roleLabels[$newRole] ?? $newRole;

        $email = $this->createBaseEmail()
            ->to($user->getEmail())
            ->subject('Votre demande de validation a été approuvée - Aiolia Event')
            ->htmlTemplate('emails/validation_approved.html.twig')
            ->context([
                'user' => $user,
                'newRole' => $roleLabel,
                'comment' => $comment,
            ]);

        return $this->sendEmail($email);
    }

    /**
     * Envoie une notification lors du rejet d'une demande de validation
     */
    public function sendValidationRejectedNotification(User $user, string $requestedRole, ?string $reason = null): bool
    {
        $roleLabels = [
            'organizer' => 'Organisateur',
            'admin' => 'Administrateur',
            'user' => 'Utilisateur',
        ];

        $roleLabel = $roleLabels[$requestedRole] ?? $requestedRole;

        $email = $this->createBaseEmail()
            ->to($user->getEmail())
            ->subject('Votre demande de validation a été rejetée - Aiolia Event')
            ->htmlTemplate('emails/validation_rejected.html.twig')
            ->context([
                'user' => $user,
                'requestedRole' => $roleLabel,
                'reason' => $reason,
            ]);

        return $this->sendEmail($email);
    }

    /**
     * Envoie une notification lors d'un changement de rôle
     */
    public function sendRoleChangeNotification(User $user, string $oldRole, string $newRole): bool
    {
        $roleLabels = [
            'organizer' => 'Organisateur',
            'admin' => 'Administrateur',
            'user' => 'Utilisateur',
        ];

        $oldRoleLabel = $roleLabels[$oldRole] ?? $oldRole;
        $newRoleLabel = $roleLabels[$newRole] ?? $newRole;

        $email = $this->createBaseEmail()
            ->to($user->getEmail())
            ->subject('Changement de rôle de votre compte - Aiolia Event')
            ->htmlTemplate('emails/role_change.html.twig')
            ->context([
                'user' => $user,
                'oldRole' => $oldRoleLabel,
                'newRole' => $newRoleLabel,
            ]);

        return $this->sendEmail($email);
    }

    private function createBaseEmail(): TemplatedEmail
    {
        $email = new TemplatedEmail();

        if (!empty($this->fromAddress)) {
            $email->from(new Address($this->fromAddress, $this->fromName ?: null));
        }

        return $email;
    }

    private function sendEmail(TemplatedEmail $email): bool
    {
        try {
            $this->mailer->send($email);
            return true;
        } catch (\Throwable $e) {
            $toRecipients = array_map(
                static fn(Address $address) => $address->toString(),
                $email->getTo() ?? []
            );

            $this->logger?->error(sprintf(
                'Erreur lors de l\'envoi de l\'email "%s" vers "%s": %s',
                $email->getSubject(),
                implode(', ', $toRecipients),
                $e->getMessage()
            ));
            return false;
        }
    }
}

