<?php

namespace App\Command;

use App\Entity\User;
use App\Service\UserNotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-test-email',
    description: 'Envoie un email de test via le service de notification des utilisateurs',
)]
class SendTestEmailCommand extends Command
{
    private const SUPPORTED_TYPES = [
        'validation-approved',
        'validation-rejected',
        'status-change',
        'role-change',
    ];

    public function __construct(
        private readonly UserNotificationService $notificationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email du destinataire')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Type de notification à tester', 'validation-approved')
            ->addOption('first-name', null, InputOption::VALUE_REQUIRED, 'Prénom à afficher dans le message', 'Test')
            ->addOption('last-name', null, InputOption::VALUE_REQUIRED, 'Nom à afficher dans le message', 'Utilisateur')
            ->addOption('comment', 'c', InputOption::VALUE_OPTIONAL, 'Commentaire ou note à inclure', null)
            ->addOption('old-status', null, InputOption::VALUE_OPTIONAL, 'Statut précédent (pour status-change)', 'pending_validation')
            ->addOption('new-status', null, InputOption::VALUE_OPTIONAL, 'Nouveau statut (pour status-change)', 'active')
            ->addOption('old-role', null, InputOption::VALUE_OPTIONAL, 'Ancien rôle (pour role-change)', 'user')
            ->addOption('new-role', null, InputOption::VALUE_OPTIONAL, 'Nouveau rôle (pour validation-approved / role-change)', 'organizer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $recipient = (string) $input->getArgument('email');
        $type = (string) $input->getOption('type');

        if (!in_array($type, self::SUPPORTED_TYPES, true)) {
            $io->error(sprintf(
                'Type de notification "%s" non pris en charge. Types acceptés: %s',
                $type,
                implode(', ', self::SUPPORTED_TYPES)
            ));
            return Command::INVALID;
        }

        $user = $this->createVirtualUser(
            $recipient,
            (string) $input->getOption('first-name'),
            (string) $input->getOption('last-name')
        );

        $io->title('Test d\'envoi d\'email');
        $io->text(sprintf('Destinataire: %s', $recipient));
        $io->text(sprintf('Type de notification: %s', $type));

        try {
            match ($type) {
                'validation-approved' => $this->notificationService->sendValidationApprovedNotification(
                    $user,
                    (string) $input->getOption('new-role'),
                    $input->getOption('comment')
                ),
                'validation-rejected' => $this->notificationService->sendValidationRejectedNotification(
                    $user,
                    (string) $input->getOption('new-role'),
                    $input->getOption('comment')
                ),
                'status-change' => $this->notificationService->sendStatusChangeNotification(
                    $user,
                    (string) $input->getOption('old-status'),
                    (string) $input->getOption('new-status'),
                    $input->getOption('comment')
                ),
                'role-change' => $this->notificationService->sendRoleChangeNotification(
                    $user,
                    (string) $input->getOption('old-role'),
                    (string) $input->getOption('new-role')
                ),
                default => throw new \LogicException(sprintf('Type "%s" non géré.', $type)),
            };
        } catch (\Throwable $exception) {
            $io->error('Échec de l\'envoi: ' . $exception->getMessage());
            return Command::FAILURE;
        }

        $io->success('Email de test envoyé. Vérifiez votre boîte de réception (pensez à regarder le dossier spam).');

        return Command::SUCCESS;
    }

    private function createVirtualUser(string $email, string $firstName, string $lastName): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setLoginIdentifier($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPasswordHash('dummy');
        $user->setRole('user');
        $user->setAccountStatus('active');

        return $user;
    }
}


