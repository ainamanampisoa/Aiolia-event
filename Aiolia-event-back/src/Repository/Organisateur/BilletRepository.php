<?php

namespace App\Repository\Organisateur;

use App\Entity\Billet;
use App\Entity\Event;
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

    /**
     * Évolution des ventes (nombre de billets et revenu) par jour pour un événement
     */
    public function getSalesEvolutionByEvent(Event $event): array
    {
        // Utilisation d'une requête SQL native car Doctrine ORM ne supporte pas DATE() en DQL
        $conn = $this->getEntityManager()->getConnection();
        
        $statuses = [Billet::STATUT_VALID, Billet::STATUT_USED];
        
        // Échapper les valeurs pour la clause IN
        $quotedStatuses = array_map(function ($status) use ($conn) {
            return $conn->quote($status);
        }, $statuses);
        
        $sql = "
            SELECT
                DATE(b.emis_le) AS jour,
                COUNT(b.id) AS tickets_sold,
                COALESCE(SUM(tb.prix_de_base::numeric), 0) AS revenue
            FROM aiolia.billets b
            INNER JOIN aiolia.types_billets tb ON b.id_type_billet = tb.id
            INNER JOIN aiolia.evenements e ON tb.id_evenement = e.id
            WHERE e.id = :event_id
                AND b.statut IN (" . implode(', ', $quotedStatuses) . ")
            GROUP BY DATE(b.emis_le)
            ORDER BY DATE(b.emis_le) ASC
        ";

        $result = $conn->executeQuery($sql, [
            'event_id' => $event->getId(),
        ]);

        $results = $result->fetchAllAssociative();

        return array_map(static function (array $row): array {
            return [
                'date' => $row['jour'],
                'ticketsSold' => (int) $row['tickets_sold'],
                'revenue' => (float) $row['revenue'],
            ];
        }, $results);
    }
}

