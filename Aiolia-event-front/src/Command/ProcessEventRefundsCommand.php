<?php

namespace App\Command;

use App\EventListener\EventCancellationListener;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process-event-refunds',
    description: 'Traite les remboursements automatiques pour les événements annulés'
)]
class ProcessEventRefundsCommand extends Command
{
    public function __construct(
        private readonly EventCancellationListener $eventCancellationListener
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Traitement des remboursements pour événements annulés');

        try {
            $this->eventCancellationListener->processCancelledEvents();
            
            $io->success('Traitement terminé avec succès');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors du traitement : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

