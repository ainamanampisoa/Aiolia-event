<?php

namespace App\Command;

use App\Service\EventReminderService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-event-reminders',
    description: 'Envoie les rappels d\'événements (24h et 2h avant)',
)]
class SendEventRemindersCommand extends Command
{
    public function __construct(
        private readonly EventReminderService $reminderService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Envoi des rappels d\'événements');

        try {
            $stats = $this->reminderService->sendReminders([24, 2]);

            $io->success([
                'Rappels envoyés avec succès !',
                sprintf('Événements traités : %d', $stats['events_processed']),
                sprintf('Utilisateurs notifiés : %d', $stats['users_notified']),
                sprintf('Emails envoyés : %d', $stats['emails_sent']),
                sprintf('Notifications push envoyées : %d', $stats['push_notifications_sent']),
            ]);

            if ($stats['errors'] > 0) {
                $io->warning(sprintf('%d erreur(s) rencontrée(s)', $stats['errors']));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'envoi des rappels : ' . $e->getMessage());
            $this->logger->error('Erreur dans la commande send-event-reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}

