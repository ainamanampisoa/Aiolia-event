<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Repository\Organisateur\BilletRepository;
use App\Repository\Organisateur\CommandeRepository;

class EventExportService
{
    public function __construct(
        private BilletRepository $billetRepository,
        private CommandeRepository $commandeRepository
    ) {
    }

    
    public function generateParticipantsCsv(Event $event, callable $writeCallback): void
    {
        
        $commandes = $this->commandeRepository->createQueryBuilder('c')
            ->innerJoin('c.elements', 'ec')
            ->innerJoin('ec.typeBillet', 'tb')
            ->where('tb.evenement = :event')
            ->setParameter('event', $event)
            ->orderBy('c.creeLe', 'DESC')
            ->getQuery()
            ->getResult();

        
        $writeCallback([
            'ID Commande',
            'Date Commande',
            'Statut Commande',
            'Montant Total',
            'ID Élément Commande',
            'Type Billet',
            'Quantité',
            'Prix Unitaire',
            'Prix Total',
            'ID Billet',
            'Code QR',
            'Nom Participant',
            'Email Participant',
            'Téléphone Participant',
            'Date Émission Billet',
            'Statut Billet'
        ]);

        
        foreach ($commandes as $commande) {
            foreach ($commande->getElements() as $elementCommande) {
                $typeBillet = $elementCommande->getTypeBillet();

                
                if ($typeBillet->getEvenement()->getId() !== $event->getId()) {
                    continue;
                }

                
                $billets = $this->billetRepository->findByElementCommande($elementCommande);

                if (count($billets) > 0) {
                    
                    foreach ($billets as $billet) {
                        $utilisateur = $billet->getUtilisateurProprietaire();
                        $nomParticipant = $utilisateur ? ($utilisateur->getNomComplet() ?: $utilisateur->getEmail()) : '';
                        $emailParticipant = $utilisateur?->getEmail() ?? '';
                        $telephoneParticipant = $utilisateur?->getTelephone() ?? '';

                        $writeCallback([
                            $commande->getId(),
                            $commande->getCreeLe() ? $commande->getCreeLe()->format('d/m/Y H:i:s') : '',
                            $commande->getStatut() ?? '',
                            number_format($commande->getMontantTotal() ?? 0, 2, ',', ' ') . ' MGA',
                            $elementCommande->getId(),
                            $typeBillet->getNom() ?? '',
                            $elementCommande->getQuantite() ?? 0,
                            number_format($elementCommande->getPrixUnitaire() ?? 0, 2, ',', ' ') . ' MGA',
                            number_format($elementCommande->getMontantTotal() ?? 0, 2, ',', ' ') . ' MGA',
                            $billet->getId(),
                            $billet->getCodeQr() ?? '',
                            $nomParticipant,
                            $emailParticipant,
                            $telephoneParticipant,
                            $billet->getEmisLe() ? $billet->getEmisLe()->format('d/m/Y H:i:s') : '',
                            $billet->getStatut() ?? ''
                        ]);
                    }
                } else {
                    
                    $writeCallback([
                        $commande->getId(),
                        $commande->getCreeLe() ? $commande->getCreeLe()->format('d/m/Y H:i:s') : '',
                        $commande->getStatut() ?? '',
                        number_format($commande->getMontantTotal() ?? 0, 2, ',', ' ') . ' MGA',
                        $elementCommande->getId(),
                        $typeBillet->getNom() ?? '',
                        $elementCommande->getQuantite() ?? 0,
                        number_format($elementCommande->getPrixUnitaire() ?? 0, 2, ',', ' ') . ' MGA',
                        number_format($elementCommande->getMontantTotal() ?? 0, 2, ',', ' ') . ' MGA',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        ''
                    ]);
                }
            }
        }
    }

    
    public function generateCsvFilename(Event $event): string
    {
        $slug = $event->getSlug() ?: 'evenement-' . $event->getId();
        $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($slug));
        return 'participants-ventes-' . $slug . '-' . date('Y-m-d') . '.csv';
    }
}


