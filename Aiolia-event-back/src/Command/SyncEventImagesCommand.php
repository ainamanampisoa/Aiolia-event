<?php
namespace App\Command;

use App\Entity\Event;
use App\Entity\EventMedia;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncEventImagesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName('app:sync:event-images')
            ->setDescription('Synchronise les images principales avec url_image_couverture et force la mise à jour');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln('=== SYNCHRONISATION FORCÉE DES IMAGES D\'ÉVÉNEMENTS ===');
            
            // ============================================
            // ÉTAPE 1: Diagnostic
            // ============================================
            $output->writeln('1. Diagnostic initial...');
            
            // Récupérer tous les événements
            $allEvents = $this->entityManager->getRepository(Event::class)->findAll();
            $output->writeln(sprintf('  - Événements trouvés: %d', count($allEvents)));
            
            $updatesNeeded = 0;
            $mediaUpdates = 0;
            
            // ============================================
            // ÉTAPE 2: Vérifier chaque événement
            // ============================================
            $output->writeln('2. Vérification détaillée par événement...');
            
            foreach ($allEvents as $event) {
                $eventId = $event->getId();
                $currentCoverUrl = $event->getUrlImageCouverture();
                
                $output->writeln(sprintf('  - Événement #%d: %s', $eventId, $event->getTitre()));
                $output->writeln(sprintf('    URL couverture actuelle: %s', 
                    $currentCoverUrl ?: '(vide)'));
                
                // Récupérer tous les médias images pour cet événement
                $eventMedias = $this->entityManager->getRepository(EventMedia::class)
                    ->createQueryBuilder('m')
                    ->where('m.event = :event')
                    ->andWhere("m.mediaType = 'image'")
                    ->setParameter('event', $event)
                    ->orderBy('m.isMainPoster', 'DESC') // Les principaux d'abord
                    ->addOrderBy('m.displayOrder', 'ASC')
                    ->getQuery()
                    ->getResult();
                
                if (count($eventMedias) === 0) {
                    $output->writeln('    ❌ Aucun média image trouvé');
                    continue;
                }
                
                // Trouver le média principal actuel
                $currentMainMedia = null;
                foreach ($eventMedias as $media) {
                    if ($media->isMainPoster()) {
                        $currentMainMedia = $media;
                        break;
                    }
                }
                
                // Si aucun média n'est marqué comme principal, prendre le premier
                if (!$currentMainMedia && count($eventMedias) > 0) {
                    $currentMainMedia = $eventMedias[0];
                    $output->writeln('    ⚠ Aucun média marqué comme principal, on prend le premier');
                }
                
                if ($currentMainMedia) {
                    $mainMediaUrl = $currentMainMedia->getUrl();
                    $output->writeln(sprintf('    Média principal: %s', $mainMediaUrl));
                    
                    // Vérification 1: L'URL de couverture correspond-elle au média principal?
                    if ($currentCoverUrl !== $mainMediaUrl) {
                        $output->writeln(sprintf('    ⚠ Incohérence détectée: URL couverture ≠ média principal'));
                        $output->writeln(sprintf('      → Mise à jour de l\'URL de couverture'));
                        
                        $event->setUrlImageCouverture($mainMediaUrl);
                        $this->entityManager->persist($event);
                        $updatesNeeded++;
                    }
                    
                    // Vérification 2: Le média principal est-il bien marqué comme tel?
                    if (!$currentMainMedia->isMainPoster()) {
                        $output->writeln('    ⚠ Le média n\'est pas marqué comme principal');
                        $output->writeln('      → Marquage comme média principal');
                        
                        // D'abord désactiver tous les autres médias principaux
                        $this->entityManager->createQueryBuilder()
                            ->update(EventMedia::class, 'm')
                            ->set('m.isMainPoster', 'false')
                            ->where('m.event = :event')
                            ->andWhere("m.mediaType = 'image'")
                            ->andWhere('m.isMainPoster = true')
                            ->setParameter('event', $event)
                            ->getQuery()
                            ->execute();
                        
                        // Puis marquer celui-ci comme principal
                        $currentMainMedia->setIsMainPoster(true);
                        $this->entityManager->persist($currentMainMedia);
                        $mediaUpdates++;
                    }
                } else {
                    $output->writeln('    ❌ Impossible de déterminer un média principal');
                }
                
                // Vérification 3: Y a-t-il plusieurs médias marqués comme principaux?
                $mainMediaCount = 0;
                foreach ($eventMedias as $media) {
                    if ($media->isMainPoster()) {
                        $mainMediaCount++;
                    }
                }
                
                if ($mainMediaCount > 1) {
                    $output->writeln(sprintf('    ⚠ ATTENTION: %d médias marqués comme principaux!', $mainMediaCount));
                    $output->writeln('      → Correction: garder seulement le premier comme principal');
                    
                    // Garder seulement le premier média comme principal
                    $firstMainFound = false;
                    foreach ($eventMedias as $media) {
                        if ($media->isMainPoster()) {
                            if (!$firstMainFound) {
                                $firstMainFound = true;
                                // Celui-ci reste principal
                                continue;
                            }
                            // Les autres deviennent non principaux
                            $media->setIsMainPoster(false);
                            $this->entityManager->persist($media);
                            $mediaUpdates++;
                        }
                    }
                }
            }
            
            // ============================================
            // ÉTAPE 3: Appliquer les changements
            // ============================================
            if ($updatesNeeded > 0 || $mediaUpdates > 0) {
                $output->writeln('3. Application des modifications...');
                $this->entityManager->flush();
                $output->writeln(sprintf('  - %d événements mis à jour', $updatesNeeded));
                $output->writeln(sprintf('  - %d médias modifiés', $mediaUpdates));
                $output->writeln('<info>✓ Synchronisation terminée avec modifications</info>');
            } else {
                $output->writeln('3. Aucune modification nécessaire');
                $output->writeln('<info>✓ Toutes les images sont déjà synchronisées</info>');
            }
            
            // ============================================
            // ÉTAPE 4: Vérification finale
            // ============================================
            $output->writeln('4. Vérification finale...');
            
            $finalInconsistencies = 0;
            $eventsWithoutCover = 0;
            
            foreach ($allEvents as $event) {
                $eventId = $event->getId();
                $coverUrl = $event->getUrlImageCouverture();
                
                // Récupérer le média principal
                $mainMedia = $this->entityManager->getRepository(EventMedia::class)
                    ->createQueryBuilder('m')
                    ->where('m.event = :event')
                    ->andWhere("m.mediaType = 'image'")
                    ->andWhere('m.isMainPoster = true')
                    ->setParameter('event', $event)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
                
                if (!$coverUrl || $coverUrl === '') {
                    $eventsWithoutCover++;
                    $output->writeln(sprintf('  ⚠ Événement #%d: Pas d\'URL de couverture', $eventId));
                } elseif ($mainMedia && $coverUrl !== $mainMedia->getUrl()) {
                    $finalInconsistencies++;
                    $output->writeln(sprintf('  ❌ Événement #%d: Incohérence persistante', $eventId));
                }
            }
            
            if ($finalInconsistencies === 0 && $eventsWithoutCover === 0) {
                $output->writeln('<info>✓ Vérification OK: toutes les données sont cohérentes</info>');
            } else {
                $output->writeln(sprintf('<comment>⚠ Attention: %d incohérences, %d événements sans couverture</comment>', 
                    $finalInconsistencies, $eventsWithoutCover));
            }
            
            // ============================================
            // RÉSUMÉ
            // ============================================
            $output->writeln('');
            $output->writeln('=== RÉSUMÉ ===');
            $output->writeln(sprintf('Événements traités: %d', count($allEvents)));
            $output->writeln(sprintf('Événements mis à jour: %d', $updatesNeeded));
            $output->writeln(sprintf('Médias modifiés: %d', $mediaUpdates));
            $output->writeln(sprintf('Incohérences finales: %d', $finalInconsistencies));
            $output->writeln(sprintf('Événements sans couverture: %d', $eventsWithoutCover));
            
            if ($finalInconsistencies > 0 || $eventsWithoutCover > 0) {
                $output->writeln('<error>✗ Des problèmes persistent. Vérifiez manuellement.</error>');
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Erreur: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}