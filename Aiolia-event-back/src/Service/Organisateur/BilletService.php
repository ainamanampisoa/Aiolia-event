<?php

namespace App\Service\Organisateur;

use App\Entity\Billet;
use App\Entity\ElementCommande;
use App\Entity\TypeBillet;
use App\Entity\User;
use App\Repository\Organisateur\BilletRepository;
use App\Service\TicketStatusService;
use Doctrine\ORM\Tools\Pagination\Paginator;

class BilletService
{
    public function __construct(
        private BilletRepository $repository,
        private TicketStatusService $ticketStatusService
    ) {
    }

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getById(string $id): ?Billet
    {
        return $this->repository->getById($id);
    }

    
    public function getByCodeQr(string $codeQr): ?Billet
    {
        return $this->repository->findByCodeQr($codeQr);
    }

    
    public function getByUser(User $user): array
    {
        return $this->repository->findByUser($user);
    }

    
    public function getByTypeBillet(TypeBillet $typeBillet): array
    {
        return $this->repository->findByTypeBillet($typeBillet);
    }

    
    public function getByElementCommande(ElementCommande $elementCommande): array
    {
        return $this->repository->findByElementCommande($elementCommande);
    }

    
    public function create(array $data, TypeBillet $typeBillet, ?ElementCommande $elementCommande = null, ?User $utilisateurProprietaire = null): Billet
    {
        $billet = new Billet();
        $billet->setTypeBillet($typeBillet);

        if ($elementCommande !== null) {
            $billet->setElementCommande($elementCommande);
        }

        if ($utilisateurProprietaire !== null) {
            $billet->setUtilisateurProprietaire($utilisateurProprietaire);
        }

        if (isset($data['statut'])) {
            $billet->setStatut($data['statut']);
        }

        if (isset($data['codeQr'])) {
            $billet->setCodeQr($data['codeQr']);
        }

        if (isset($data['checksumQr'])) {
            $billet->setChecksumQr($data['checksumQr']);
        }

        if (isset($data['metadonnees'])) {
            $billet->setMetadonnees($data['metadonnees']);
        }

        return $this->repository->create($billet);
    }

    
    public function update(Billet $billet, array $data): Billet
    {
        if (isset($data['statut'])) {
            $billet->setStatut($data['statut']);
        }

        if (isset($data['codeQr'])) {
            $billet->setCodeQr($data['codeQr']);
        }

        if (isset($data['checksumQr'])) {
            $billet->setChecksumQr($data['checksumQr']);
        }

        if (isset($data['metadonnees'])) {
            $billet->setMetadonnees($data['metadonnees']);
        }

        if (isset($data['utilisateurProprietaire']) && $data['utilisateurProprietaire'] instanceof User) {
            $billet->setUtilisateurProprietaire($data['utilisateurProprietaire']);
        }

        return $this->repository->update($billet);
    }

    
    public function delete(Billet $billet): void
    {
        $this->repository->delete($billet);
    }

    
    public function getByOrganizer(User $organizer): array
    {
        return $this->repository->findByOrganizer($organizer);
    }

    
    public function getByOrganizerPaginated(User $organizer, int $page = 1, int $limit = 10, ?\App\Entity\Event $event = null, array $filters = []): Paginator
    {
        return $this->repository->findByOrganizerPaginated($organizer, $page, $limit, $event, $filters);
    }

    
    public function getStatsByOrganizer(User $organizer, ?\App\Entity\Event $event = null, array $filters = []): array
    {
        return $this->repository->getStatsByOrganizer($organizer, $event, $filters);
    }

    
    public function getFilterOptionsByOrganizer(User $organizer, ?\App\Entity\Event $event = null): array
    {
        $options = $this->repository->getFilterOptionsByOrganizer($organizer, $event);
        $options['statuts'] = $this->ticketStatusService->getStatuses();

        return $options;
    }

    
    public function getSalesStatsByTypeBillet(TypeBillet $typeBillet): array
    {
        // Utiliser les billets réels au lieu de l'inventaire pour être cohérent avec l'historique
        // Utiliser la même logique que getStatsByOrganizer
        $billets = $this->repository->findByTypeBillet($typeBillet);
        
        // Total : tous les billets de ce type (comme dans getStatsByOrganizer)
        $stockTotal = count($billets);
        
        // Vendus : billets avec elementCommande et statut valid ou used (même logique que getStatsByOrganizer)
        // Exclure les billets remboursés (refunded) et transférés (transferred)
        $vendus = 0;
        $revenus = 0;
        $prixUnitaire = (float) $typeBillet->getPrixDeBase();
        
        foreach ($billets as $billet) {
            if ($billet->getElementCommande() !== null) {
                $statut = $billet->getStatut();
                // Même condition que dans getStatsByOrganizer : seulement valid et used
                if (in_array($statut, [Billet::STATUT_VALID, Billet::STATUT_USED], true)) {
                    $vendus++;
                    $revenus += $prixUnitaire;
                }
            }
        }
        
        // Disponibles : billets sans elementCommande (même logique que getStatsByOrganizer pour nonUtilises)
        // Cela inclut les billets "dispo" qui n'ont pas de elementCommande
        $disponibles = 0;
        foreach ($billets as $billet) {
            if ($billet->getElementCommande() === null) {
                $disponibles++;
            }
        }
        
        // Vérification de cohérence : total devrait être égal à vendus + disponibles + autres (refunded, transferred)
        // Mais pour l'affichage, on montre seulement vendus et disponibles
        $tauxVente = $stockTotal > 0 ? ($vendus / $stockTotal) * 100 : 0;
        
        return [
            'stockTotal' => $stockTotal,
            'vendus' => $vendus,
            'disponibles' => $disponibles,
            'revenus' => $revenus,
            'tauxVente' => round($tauxVente, 1),
        ];
    }
}

