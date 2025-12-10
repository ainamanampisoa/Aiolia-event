<?php

namespace App\Service\Organisateur;

use App\Entity\ElementPanier;
use App\Entity\Panier;
use App\Entity\TypeBillet;
use App\Repository\Organisateur\ElementPanierRepository;

class ElementPanierService
{
    public function __construct(
        private ElementPanierRepository $repository
    ) {
    }

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getById(string $id): ?ElementPanier
    {
        return $this->repository->getById($id);
    }

    
    public function getByPanier(Panier $panier): array
    {
        return $this->repository->findByPanier($panier);
    }

    
    public function create(array $data, Panier $panier, TypeBillet $typeBillet): ElementPanier
    {
        $element = new ElementPanier();
        $element->setPanier($panier);
        $element->setTypeBillet($typeBillet);

        if (isset($data['quantite'])) {
            $element->setQuantite($data['quantite']);
        }

        if (isset($data['prixUnitaire'])) {
            $element->setPrixUnitaire((string) $data['prixUnitaire']);
        }

        if (isset($data['prixTotal'])) {
            $element->setPrixTotal((string) $data['prixTotal']);
        }

        return $this->repository->create($element);
    }

    
    public function update(ElementPanier $element, array $data): ElementPanier
    {
        if (isset($data['quantite'])) {
            $element->setQuantite($data['quantite']);
        }

        if (isset($data['prixUnitaire'])) {
            $element->setPrixUnitaire((string) $data['prixUnitaire']);
        }

        if (isset($data['prixTotal'])) {
            $element->setPrixTotal((string) $data['prixTotal']);
        }

        return $this->repository->update($element);
    }

    
    public function delete(ElementPanier $element): void
    {
        $this->repository->delete($element);
    }
}

