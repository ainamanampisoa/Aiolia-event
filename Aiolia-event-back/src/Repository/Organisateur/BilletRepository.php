<?php

namespace App\Repository\Organisateur;

use App\Entity\Billet;
use App\Entity\ElementCommande;
use App\Entity\TypeBillet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Billet>
 */
class BilletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Billet::class);
    }

    /**
     * Récupère tous les billets
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.emisLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un billet par son ID
     */
    public function getById(string $id): ?Billet
    {
        return $this->find($id);
    }

    /**
     * Récupère un billet par son code QR
     */
    public function findByCodeQr(string $codeQr): ?Billet
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.codeQr = :codeQr')
            ->setParameter('codeQr', $codeQr)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère tous les billets d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.utilisateurProprietaire = :user')
            ->setParameter('user', $user)
            ->orderBy('b.emisLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les billets d'un type de billet
     */
    public function findByTypeBillet(TypeBillet $typeBillet): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.typeBillet = :typeBillet')
            ->setParameter('typeBillet', $typeBillet)
            ->orderBy('b.emisLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les billets d'un élément de commande
     */
    public function findByElementCommande(ElementCommande $elementCommande): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.elementCommande = :elementCommande')
            ->setParameter('elementCommande', $elementCommande)
            ->orderBy('b.emisLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Crée un nouveau billet
     */
    public function create(Billet $billet): Billet
    {
        $this->getEntityManager()->persist($billet);
        $this->getEntityManager()->flush();

        return $billet;
    }

    /**
     * Met à jour un billet
     */
    public function update(Billet $billet): Billet
    {
        $this->getEntityManager()->flush();

        return $billet;
    }

    /**
     * Supprime un billet
     */
    public function delete(Billet $billet): void
    {
        $this->getEntityManager()->remove($billet);
        $this->getEntityManager()->flush();
    }
}

