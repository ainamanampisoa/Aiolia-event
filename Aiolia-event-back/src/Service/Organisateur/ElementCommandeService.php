<?php

namespace App\Service\Organisateur;

use App\Entity\Commande;
use App\Entity\ElementCommande;
use App\Entity\TypeBillet;
use App\Repository\Organisateur\ElementCommandeRepository;

class ElementCommandeService
{
    public function __construct(
        private ElementCommandeRepository $repository
    ) {
    }

    /**
     * Récupère tous les éléments de commandes
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère un élément de commande par son ID
     */
    public function getById(string $id): ?ElementCommande
    {
        return $this->repository->getById($id);
    }

    /**
     * Récupère tous les éléments d'une commande
     */
    public function getByCommande(Commande $commande): array
    {
        return $this->repository->findByCommande($commande);
    }

    /**
     * Crée un nouvel élément de commande
     */
    public function create(array $data, Commande $commande, TypeBillet $typeBillet): ElementCommande
    {
        $element = new ElementCommande();
        $element->setCommande($commande);
        $element->setTypeBillet($typeBillet);

        if (isset($data['quantite'])) {
            $element->setQuantite($data['quantite']);
        }

        if (isset($data['prixUnitaire'])) {
            $element->setPrixUnitaire((string) $data['prixUnitaire']);
        }

        if (isset($data['fraisService'])) {
            $element->setFraisService((string) $data['fraisService']);
        }

        if (isset($data['montantTva'])) {
            $element->setMontantTva((string) $data['montantTva']);
        }

        if (isset($data['montantTotal'])) {
            $element->setMontantTotal((string) $data['montantTotal']);
        }

        return $this->repository->create($element);
    }

    /**
     * Met à jour un élément de commande
     */
    public function update(ElementCommande $element, array $data): ElementCommande
    {
        if (isset($data['quantite'])) {
            $element->setQuantite($data['quantite']);
        }

        if (isset($data['prixUnitaire'])) {
            $element->setPrixUnitaire((string) $data['prixUnitaire']);
        }

        if (isset($data['fraisService'])) {
            $element->setFraisService((string) $data['fraisService']);
        }

        if (isset($data['montantTva'])) {
            $element->setMontantTva((string) $data['montantTva']);
        }

        if (isset($data['montantTotal'])) {
            $element->setMontantTotal((string) $data['montantTotal']);
        }

        return $this->repository->update($element);
    }

    /**
     * Supprime un élément de commande
     */
    public function delete(ElementCommande $element): void
    {
        $this->repository->delete($element);
    }
}

