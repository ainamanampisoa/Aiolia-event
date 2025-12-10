<?php

namespace App\Service\Organisateur;

use App\Entity\RegleTarification;
use App\Entity\TypeBillet;
use App\Repository\Organisateur\RegleTarificationRepository;

class RegleTarificationService
{
    public function __construct(
        private RegleTarificationRepository $repository
    ) {
    }

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getById(string $id): ?RegleTarification
    {
        return $this->repository->getById($id);
    }

    
    public function getByTypeBillet(TypeBillet $typeBillet): array
    {
        return $this->repository->findByTypeBillet($typeBillet);
    }

    
    public function getActiveByTypeBillet(TypeBillet $typeBillet): array
    {
        return $this->repository->findActiveByTypeBillet($typeBillet);
    }

    
    public function getActive(): array
    {
        return $this->repository->findActive();
    }

    
    public function create(array $data, TypeBillet $typeBillet): RegleTarification
    {
        $regle = new RegleTarification();
        $regle->setTypeBillet($typeBillet);

        if (isset($data['typeRegle'])) {
            $regle->setTypeRegle($data['typeRegle']);
        }

        if (isset($data['valeurSeuil'])) {
            $regle->setValeurSeuil((string) $data['valeurSeuil']);
        }

        if (isset($data['valeur'])) {
            $regle->setValeur((string) $data['valeur']);
        }

        if (isset($data['commenceLe'])) {
            $regle->setCommenceLe($data['commenceLe']);
        }

        if (isset($data['seTermineLe'])) {
            $regle->setSeTermineLe($data['seTermineLe']);
        }

        if (isset($data['metadonnees'])) {
            $regle->setMetadonnees($data['metadonnees']);
        }

        return $this->repository->create($regle);
    }

    
    public function update(RegleTarification $regle, array $data): RegleTarification
    {
        if (isset($data['typeRegle'])) {
            $regle->setTypeRegle($data['typeRegle']);
        }

        if (isset($data['valeurSeuil'])) {
            $regle->setValeurSeuil((string) $data['valeurSeuil']);
        }

        if (isset($data['valeur'])) {
            $regle->setValeur((string) $data['valeur']);
        }

        if (isset($data['commenceLe'])) {
            $regle->setCommenceLe($data['commenceLe']);
        }

        if (isset($data['seTermineLe'])) {
            $regle->setSeTermineLe($data['seTermineLe']);
        }

        if (isset($data['metadonnees'])) {
            $regle->setMetadonnees($data['metadonnees']);
        }

        return $this->repository->update($regle);
    }

    
    public function delete(RegleTarification $regle): void
    {
        $this->repository->delete($regle);
    }
}

