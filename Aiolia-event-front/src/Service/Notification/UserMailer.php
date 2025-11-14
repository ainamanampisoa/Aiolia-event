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
}

