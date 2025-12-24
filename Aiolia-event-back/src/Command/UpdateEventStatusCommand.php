<?php

namespace App\Command;

use App\Entity\Event;
use App\Repository\Organisateur\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-event-status',
    description: 'Vérifie les événements à venir et met à jour leur statut s\'ils sont en cours (live)',
)]
class UpdateEventStatusCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Mise à jour du statut des événements');

        // Vérifier si la commande a déjà été exécutée aujourd'hui
        $lockFile = sys_get_temp_dir() . '/update_event_status_' . date('Y-m-d') . '.lock';
        
        if (file_exists($lockFile)) {
            $io->warning('Cette commande a déjà été exécutée aujourd\'hui.');
            $io->info('Pour forcer l\'exécution, supprimez le fichier : ' . $lockFile);
            return Command::SUCCESS;
        }

        // Créer le fichier de verrouillage
        file_put_contents($lockFile, date('Y-m-d H:i:s'));

        // Date actuelle avec le fuseau horaire par défaut
        $now = new \DateTime('now', new \DateTimeZone('Indian/Antananarivo'));

        try {
            // Récupérer tous les événements publiés qui sont maintenant en cours (live)
            // Un événement est "live" si : commence_le <= maintenant ET (se_termine_le IS NULL OR se_termine_le >= maintenant)
            $qb = $this->eventRepository->createQueryBuilder('e')
                ->where('e.statut = :status')
                ->setParameter('status', Event::STATUS_PUBLISHED)
                ->andWhere('e.commenceLe IS NOT NULL')
                ->andWhere('e.commenceLe <= :now')
                ->andWhere('(e.seTermineLe IS NULL OR e.seTermineLe >= :now)')
                ->setParameter('now', $now);

            $eventsInProgress = $qb->getQuery()->getResult();

            $updatedCount = 0;
            $alreadyPublishedCount = 0;

            foreach ($eventsInProgress as $event) {
                // Vérifier si l'événement est effectivement en cours (live)
                $commenceLe = $event->getCommenceLe();
                $seTermineLe = $event->getSeTermineLe();

                // Un événement est "live" s'il a commencé et n'est pas encore terminé
                $isLive = false;
                if ($commenceLe && $commenceLe <= $now) {
                    if ($seTermineLe === null || $seTermineLe >= $now) {
                        $isLive = true;
                    }
                }

                if ($isLive) {
                    // S'assurer que l'événement est bien en statut "published"
                    if ($event->getStatut() !== Event::STATUS_PUBLISHED) {
                        $event->setStatut(Event::STATUS_PUBLISHED);
                        $this->entityManager->persist($event);
                        $updatedCount++;
                        $io->info(sprintf(
                            'Événement "%s" (ID: %s) mis à jour en statut "published" (en cours/live)',
                            $event->getTitre(),
                            $event->getId()
                        ));
                    } else {
                        $alreadyPublishedCount++;
                    }
                }
            }

            // Sauvegarder les modifications
            if ($updatedCount > 0) {
                $this->entityManager->flush();
            }

            $io->newLine();
            $io->section('Résumé');
            $io->table(
                ['Statut', 'Nombre'],
                [
                    ['Événements en cours trouvés', count($eventsInProgress)],
                    ['Événements mis à jour', $updatedCount],
                    ['Événements déjà en statut "published"', $alreadyPublishedCount],
                ]
            );

            if ($updatedCount > 0) {
                $io->success(sprintf(
                    '%d événement(s) mis à jour avec succès',
                    $updatedCount
                ));
            } else {
                $io->info('Aucun événement à mettre à jour. Tous les événements en cours sont déjà en statut "published".');
            }

            // Le fichier de verrouillage reste en place jusqu'au lendemain
            // pour empêcher plusieurs exécutions le même jour

            return Command::SUCCESS;
        } catch (\Exception $e) {
            // Supprimer le fichier de verrouillage en cas d'erreur pour permettre une nouvelle tentative
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }

            $io->error(sprintf(
                'Erreur lors de la mise à jour des statuts d\'événements : %s',
                $e->getMessage()
            ));
            
            return Command::FAILURE;
        }
    }
}

