<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Entity\TypeBillet;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Address;

class WaitlistEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire(env: 'MAIL_FROM_ADDRESS')]
        private string $fromAddress,
        #[Autowire(env: 'MAIL_FROM_NAME')]
        private ?string $fromName = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Envoie un email de confirmation d'acceptation de liste d'attente
     */
    public function sendAcceptanceEmail(User $user, Event $event, TypeBillet $typeBillet, int $quantite): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromAddress, $this->fromName ?? 'Aiolia Event'))
                ->to($user->getEmail())
                ->subject('Votre demande de liste d\'attente a été acceptée - ' . $event->getTitre())
                ->htmlTemplate('emails/waitlist_accepted.html.twig')
                ->context([
                    'user' => $user,
                    'event' => $event,
                    'typeBillet' => $typeBillet,
                    'quantite' => $quantite,
                ]);

            $this->mailer->send($email);

            if ($this->logger) {
                $this->logger->info('Email d\'acceptation de liste d\'attente envoyé', [
                    'user_email' => $user->getEmail(),
                    'event_id' => $event->getId(),
                    'type_billet_id' => $typeBillet->getId(),
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Erreur lors de l\'envoi de l\'email d\'acceptation de liste d\'attente', [
                    'user_email' => $user->getEmail(),
                    'event_id' => $event->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
            return false;
        }
    }

    /**
     * Envoie un email de rejet de liste d'attente
     */
    public function sendRejectionEmail(User $user, Event $event, TypeBillet $typeBillet): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromAddress, $this->fromName ?? 'Aiolia Event'))
                ->to($user->getEmail())
                ->subject('Votre demande de liste d\'attente a été rejetée - ' . $event->getTitre())
                ->htmlTemplate('emails/waitlist_rejected.html.twig')
                ->context([
                    'user' => $user,
                    'event' => $event,
                    'typeBillet' => $typeBillet,
                ]);

            $this->mailer->send($email);

            if ($this->logger) {
                $this->logger->info('Email de rejet de liste d\'attente envoyé', [
                    'user_email' => $user->getEmail(),
                    'event_id' => $event->getId(),
                    'type_billet_id' => $typeBillet->getId(),
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Erreur lors de l\'envoi de l\'email de rejet de liste d\'attente', [
                    'user_email' => $user->getEmail(),
                    'event_id' => $event->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
            return false;
        }
    }
}

