<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\EventMedia;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:sync:event-images',
    description: 'Synchronise les images principales et de couverture des événements'
)]
class SyncEventImagesCommand extends Command
{

    private EntityManagerInterface $entityManager;

    // URLs Cloudinary
    // private const MAIN_POSTER_URL = 'https://res.cloudinary.com/dylxwfq0f/image/upload/v1767598655/img1_ilm4dz.png';
    private const MAIN_POSTER_URL = 'https://res.cloudinary.com/dylxwfq0f/image/upload/v1767598695/banner_ioyptp.jpg';
    private const COVER_IMAGE_URL = 'https://res.cloudinary.com/dylxwfq0f/image/upload/v1767598695/banner_ioyptp.jpg';

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Synchronise les images principales et de couverture des événements');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('SYNCHRONISATION DES IMAGES DES ÉVÉNEMENTS');

        $events = $this->entityManager->getRepository(Event::class)->findAll();
        $total = count($events);

        if ($total === 0) {
            $io->warning('Aucun événement trouvé');
            return Command::SUCCESS;
        }

        $io->writeln("📊 {$total} événements trouvés");
        $io->progressStart($total);

        $eventUpdated = 0;
        $mediaCreated = 0;
        $mediaUpdated = 0;
        $coverImagesUpdated = 0;

        foreach ($events as $event) {
            try {
                // ============================
                // 1. Récupération des médias
                // ============================
                $images = $this->entityManager
                    ->getRepository(EventMedia::class)
                    ->createQueryBuilder('m')
                    ->where('m.event = :event')
                    ->andWhere('m.mediaType = :type')
                    ->setParameter('event', $event)
                    ->setParameter('type', 'image')
                    ->orderBy('m.displayOrder', 'ASC')
                    ->getQuery()
                    ->getResult();

                // ============================
                // 2. Gestion de l'image principale
                // ============================
                if (count($images) === 0) {
                    // Aucun média → création d'une image principale
                    $media = new EventMedia();
                    $media->setEvent($event);
                    $media->setMediaType('image');
                    $media->setUrl(self::MAIN_POSTER_URL);
                    $media->setAltText('Image principale - ' . $event->getTitre());
                    $media->setDisplayOrder(0);
                    $media->setIsPublic(true);
                    $media->setIsMainPoster(true);

                    $this->entityManager->persist($media);
                    $mediaCreated++;

                    // Définir l'image de couverture avec l'image principale
                    if (!$event->getUrlImageCouverture()) {
                        $event->setUrlImageCouverture(self::MAIN_POSTER_URL);
                        $eventUpdated++;
                        $coverImagesUpdated++;
                    }

                } else {
                    // Normalisation des médias existants
                    foreach ($images as $index => $media) {
                        if ($index === 0) {
                            // Premier média = image principale
                            $media->setIsMainPoster(true);
                            $media->setDisplayOrder(0);
                            $media->setAltText('Image principale - ' . $event->getTitre());
                            
                            // Si l'URL est différente de MAIN_POSTER_URL, on la met à jour
                            if ($media->getUrl() !== self::MAIN_POSTER_URL) {
                                $media->setUrl(self::MAIN_POSTER_URL);
                                $mediaUpdated++;
                            }
                            
                            // Mettre à jour l'image de couverture avec l'image principale
                            $primaryMediaUrl = $media->getUrl();
                            if (!$event->getUrlImageCouverture()) {
                                $event->setUrlImageCouverture($primaryMediaUrl);
                                $eventUpdated++;
                                $coverImagesUpdated++;
                            }
                        } else {
                            // Autres médias
                            $media->setIsMainPoster(false);
                            $media->setDisplayOrder($index);
                        }

                        $this->entityManager->persist($media);
                    }
                }

                // ============================
                // 3. Gestion de l'image de couverture pour tous les événements
                // ============================
                // Vérifier si l'événement a déjà une image de couverture
                // Si non, on définit avec l'image principale ou avec COVER_IMAGE_URL
                if (!$event->getUrlImageCouverture()) {
                    // Chercher l'image principale
                    $primaryMedia = null;
                    foreach ($images as $media) {
                        if ($media->getIsMainPoster()) {
                            $primaryMedia = $media;
                            break;
                        }
                    }
                    
                    if ($primaryMedia) {
                        // Utiliser l'URL de l'image principale (comme dans le contrôleur)
                        $event->setUrlImageCouverture($primaryMedia->getUrl());
                    } else {
                        // Fallback : utiliser COVER_IMAGE_URL (bannière)
                        $event->setUrlImageCouverture(self::COVER_IMAGE_URL);
                    }
                    
                    $eventUpdated++;
                    $coverImagesUpdated++;
                }

                $this->entityManager->persist($event);

            } catch (\Throwable $e) {
                $io->error(sprintf(
                    'Erreur événement #%d : %s',
                    $event->getId(),
                    $e->getMessage()
                ));
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        // ============================
        // FLUSH FINAL
        // ============================
        $this->entityManager->flush();

        // ============================
        // VÉRIFICATION (Version PostgreSQL avec schéma et nom de table correct)
        // ============================
        $io->writeln("\n🔍 <comment>Vérification après synchronisation...</comment>");
        
        $connection = $this->entityManager->getConnection();
        
        // Pour PostgreSQL avec le schéma aiolia et la table evenements
        $result = $connection->executeQuery('
            SELECT COUNT(*) as count 
            FROM aiolia.evenements 
            WHERE url_image_couverture IS NULL 
            OR TRIM(url_image_couverture) = \'\'
        ');
        $nullCount = $result->fetchOne();
        
        // Vérifier les URLs utilisées
        $result = $connection->executeQuery('
            SELECT url_image_couverture, COUNT(*) as count 
            FROM aiolia.evenements 
            WHERE url_image_couverture IS NOT NULL 
            AND TRIM(url_image_couverture) != \'\'
            GROUP BY url_image_couverture
            ORDER BY count DESC
        ');
        $urlStats = $result->fetchAllAssociative();

        // Afficher un échantillon d'événements
        $result = $connection->executeQuery('
            SELECT id, titre, url_image_couverture 
            FROM aiolia.evenements 
            WHERE url_image_couverture IS NOT NULL 
            AND TRIM(url_image_couverture) != \'\'
            ORDER BY id ASC
            LIMIT 5
        ');
        $sampleEvents = $result->fetchAllAssociative();

        // ============================
        // RÉSUMÉ
        // ============================
        $io->section('RÉSUMÉ');
        $io->definitionList(
            ['Événements traités' => $total],
            ['Événements mis à jour' => $eventUpdated],
            ['Médias créés' => $mediaCreated],
            ['Médias mis à jour' => $mediaUpdated],
            ['Images de couverture définies' => $coverImagesUpdated],
            ['Événements sans couverture' => $nullCount],
            ['Image principale par défaut' => self::MAIN_POSTER_URL],
            ['Bannière de couverture' => self::COVER_IMAGE_URL],
        );

        if (count($sampleEvents) > 0) {
            $io->section('ÉCHANTILLON DES ÉVÉNEMENTS (5 premiers)');
            $io->table(
                ['ID', 'Titre', 'URL de couverture'],
                $sampleEvents
            );
        }

        if (count($urlStats) > 0) {
            $io->section('DISTRIBUTION DES URLS DE COUVERTURE');
            $io->table(
                ['URL de couverture', 'Nombre d\'événements'],
                $urlStats
            );
        }

        if ($nullCount > 0) {
            $io->warning(sprintf('%d événements n\'ont toujours pas d\'image de couverture', $nullCount));
            
            // Afficher les événements sans couverture
            $result = $connection->executeQuery('
                SELECT id, titre 
                FROM aiolia.evenements 
                WHERE url_image_couverture IS NULL 
                OR TRIM(url_image_couverture) = \'\'
                ORDER BY id ASC
            ');
            $problematicEvents = $result->fetchAllAssociative();
            
            if (count($problematicEvents) > 0) {
                $io->writeln("<comment>Événements sans image de couverture:</comment>");
                foreach ($problematicEvents as $event) {
                    $io->writeln(sprintf("  #%d: %s", $event['id'], $event['titre']));
                }
            }
        }

        // Vérification finale via Doctrine
        $io->section('VÉRIFICATION FINALE VIA DOCTRINE');
        $eventsWithoutCoverage = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.urlImageCouverture IS NULL')
            ->orWhere('TRIM(e.urlImageCouverture) = \'\'')
            ->getQuery()
            ->getResult();
        
        $io->writeln(sprintf(
            'Événements sans couverture (Doctrine): %d',
            count($eventsWithoutCoverage)
        ));

        if (count($eventsWithoutCoverage) === 0) {
            $io->success('✅ TOUS les événements ont maintenant une image de couverture !');
        } else {
            $io->warning(sprintf(
                '⚠️  Il reste %d événements sans image de couverture',
                count($eventsWithoutCoverage)
            ));
            
            // Afficher les IDs des événements problématiques
            $problematicIds = [];
            foreach ($eventsWithoutCoverage as $event) {
                $problematicIds[] = $event->getId();
            }
            $io->writeln(sprintf(
                'IDs des événements sans couverture: %s',
                implode(', ', $problematicIds)
            ));
        }

        $io->success('Synchronisation terminée avec succès');

        return Command::SUCCESS;
    }
}