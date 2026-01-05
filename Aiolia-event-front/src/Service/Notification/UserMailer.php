<?php

namespace App\Service\Notification;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class UserMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $router,
        #[Autowire('%env(MAIL_FROM_ADDRESS)%')]
        private readonly string $fromEmail,
        #[Autowire('%env(default::MAIL_FROM_NAME)%')]
        private readonly ?string $fromName = null,
    ) {
    }

    public function sendRegistrationSuccess(User $user): void
    {
        try {
            $loginUrl = $this->router->generate('login', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $htmlBody = $this->twig->render('emails/registration_success.html.twig', [
                'user' => $user,
                'login_url' => $loginUrl,
            ]);

            $fromName = $this->fromName ?: 'Aiolia Event';
            $from = new Address($this->fromEmail, $fromName);

            $email = (new Email())
                ->from($from)
                ->to($user->getEmail())
                ->subject('Bienvenue sur Aiolia Event')
                ->html($htmlBody);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            // Log l'erreur mais ne bloque pas l'inscription
            error_log(sprintf('Erreur envoi email inscription: %s', $e->getMessage()));
            throw $e; // Relancer pour que l'app soit au courant
        }
    }

    /**
     * Envoie un email de rappel d'événement
     *
     * @param array<string, mixed> $user Données de l'utilisateur
     * @param array<string, mixed> $event Données de l'événement
     * @param int $hoursBefore Nombre d'heures avant l'événement
     * @param string $eventUrl URL de l'événement
     */
    public function sendEventReminder(
        array $user,
        array $event,
        int $hoursBefore,
        string $eventUrl
    ): void {
        try {
            $eventDate = new \DateTimeImmutable($event['starts_at']);
            $userEmail = $user['email'];
            $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Cher utilisateur';

            $subject = $hoursBefore === 24
                ? "Rappel : {$event['title']} demain"
                : "Rappel : {$event['title']} dans {$hoursBefore} heure(s)";

            $htmlBody = $this->twig->render('emails/event_reminder.html.twig', [
                'user_name' => $userName,
                'event' => $event,
                'event_date' => $eventDate,
                'hours_before' => $hoursBefore,
                'event_url' => $eventUrl,
            ]);

            $fromName = $this->fromName ?: 'Aiolia Event';
            $from = new Address($this->fromEmail, $fromName);

            $email = (new Email())
                ->from($from)
                ->to($userEmail)
                ->subject($subject)
                ->html($htmlBody);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            error_log(sprintf('Erreur envoi email rappel événement: %s', $e->getMessage()));
            throw $e;
        }
    }

    public function sendPaymentConfirmation(array $order): void
    {
        try {
            $orderNotes = json_decode($order['notes'] ?? '{}', true);
            $userEmail = $orderNotes['payment_email'] ?? null;
            $userName = $orderNotes['payment_name'] ?? 'Cher client';

            if (!$userEmail) {
                // Si pas d'email dans les notes, on ne peut pas envoyer le mail
                return;
            }

            $subject = "Confirmation de votre commande #" . $order['id'];

            $htmlBody = $this->twig->render('emails/payment_confirmation.html.twig', [
                'user_name' => $userName,
                'order' => $order,
                'order_notes' => $orderNotes,
            ]);

            $fromName = $this->fromName ?: 'Aiolia Event';
            $from = new Address($this->fromEmail, $fromName);

            $email = (new Email())
                ->from($from)
                ->to($userEmail)
                ->subject($subject)
                ->html($htmlBody);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            error_log(sprintf('Erreur envoi email confirmation paiement: %s', $e->getMessage()));
            // On ne relance pas pour ne pas bloquer le flux de paiement si l'email échoue
        }
    }
}

