<?php

namespace App\Repository\Organisateur;

use App\Entity\Billet;
use App\Entity\Event;
use App\Entity\ElementCommande;
use App\Entity\TypeBillet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;


class BilletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Billet::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.emisLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?Billet
    {
        return $this->find($id);
    }

    
    public function findByCodeQr(string $codeQr): ?Billet
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.codeQr = :codeQr')
            ->setParameter('codeQr', $codeQr)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.utilisateurProprietaire = :user')
            ->setParameter('user', $user)
            ->orderBy('b.emisLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function findByTypeBillet(TypeBillet $typeBillet): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.typeBillet = :typeBillet')
            ->setParameter('typeBillet', $typeBillet)
            ->orderBy('b.emisLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function findByElementCommande(ElementCommande $elementCommande): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.elementCommande = :elementCommande')
            ->setParameter('elementCommande', $elementCommande)
            ->orderBy('b.emisLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function create(Billet $billet): Billet
    {
        $this->getEntityManager()->persist($billet);
        $this->getEntityManager()->flush();

        return $billet;
    }

    
    public function update(Billet $billet): Billet
    {
        $this->getEntityManager()->flush();

        return $billet;
    }

    
    public function delete(Billet $billet): void
    {
        $this->getEntityManager()->remove($billet);
        $this->getEntityManager()->flush();
    }

    
    public function findByOrganizer(User $organizer): array
    {
        return $this->createQueryBuilder('b')
            ->innerJoin('b.typeBillet', 'tb')
            ->innerJoin('tb.evenement', 'e')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('App\Entity\OrganisateurEvenement', 'oe', 'WITH', 'oe.evenement = e')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->where('op.utilisateur = :organizer OR op2.utilisateur = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('b.emisLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function findByOrganizerPaginated(User $organizer, int $page = 1, int $limit = 10, ?Event $event = null): Paginator
    {
        $query = $this->createQueryBuilder('b')
            ->innerJoin('b.typeBillet', 'tb')
            ->innerJoin('tb.evenement', 'e')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('App\Entity\OrganisateurEvenement', 'oe', 'WITH', 'oe.evenement = e')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->where('op.utilisateur = :organizer OR op2.utilisateur = :organizer')
            ->setParameter('organizer', $organizer);
            
        if ($event !== null) {
            $query->andWhere('e.id = :eventId')
                ->setParameter('eventId', $event->getId());
        }
        
        $query->orderBy('b.emisLe', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($query->getQuery(), true);
    }

    
    public function getStatsByOrganizer(User $organizer, ?Event $event = null): array
    {
        $baseConditions = function($qb) use ($organizer, $event) {
            $qb->innerJoin('b.typeBillet', 'tb')
                ->innerJoin('tb.evenement', 'e')
                ->leftJoin('e.profilOrganisateur', 'op')
                ->leftJoin('App\Entity\OrganisateurEvenement', 'oe', 'WITH', 'oe.evenement = e')
                ->leftJoin('oe.profilOrganisateur', 'op2')
                ->where('op.utilisateur = :organizer OR op2.utilisateur = :organizer')
                ->setParameter('organizer', $organizer);
            
            if ($event !== null) {
                $qb->andWhere('e.id = :eventId')
                    ->setParameter('eventId', $event->getId());
            }
            
            return $qb;
        };

        $countSelect = 'COUNT(b.id)';
        $statusCondition = 'b.statut = :status';

        $total = (int) $baseConditions($this->createQueryBuilder('b'))
            ->select($countSelect)
            ->getQuery()
            ->getSingleScalarResult();

        $vendus = (int) $baseConditions($this->createQueryBuilder('b'))
            ->select($countSelect)
            ->andWhere('b.statut IN (:statuses)')
            ->setParameter('statuses', [Billet::STATUT_VALID, Billet::STATUT_USED])
            ->getQuery()
            ->getSingleScalarResult();

        $utilises = (int) $baseConditions($this->createQueryBuilder('b'))
            ->select($countSelect)
            ->andWhere($statusCondition)
            ->setParameter('status', Billet::STATUT_USED)
            ->getQuery()
            ->getSingleScalarResult();

        
        $enAttente = (int) $baseConditions($this->createQueryBuilder('b'))
            ->select($countSelect)
            ->andWhere($statusCondition)
            ->setParameter('status', Billet::STATUT_VALID)
            ->getQuery()
            ->getSingleScalarResult();

        $annules = (int) $baseConditions($this->createQueryBuilder('b'))
            ->select($countSelect)
            ->andWhere($statusCondition)
            ->setParameter('status', Billet::STATUT_CANCELLED)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'vendus' => $vendus,
            'utilises' => $utilises,
            'enAttente' => $enAttente,
            'annules' => $annules,
        ];
    }

    
    public function getSalesEvolutionByEvent(Event $event): array
    {
        
        $conn = $this->getEntityManager()->getConnection();
        
        $statuses = [Billet::STATUT_VALID, Billet::STATUT_USED];
        
        
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

