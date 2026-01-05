<?php

namespace App\Repository\Organisateur;

use App\Entity\Event;
use App\Entity\TypeBillet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;


class TypeBilletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeBillet::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?TypeBillet
    {
        return $this->find($id);
    }

    
    public function findByEvenement(Event $evenement): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.evenement = :evenement')
            ->setParameter('evenement', $evenement)
            ->orderBy('t.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function create(TypeBillet $typeBillet): TypeBillet
    {
        $this->getEntityManager()->persist($typeBillet);
        $this->getEntityManager()->flush();

        return $typeBillet;
    }

    
    public function update(TypeBillet $typeBillet): TypeBillet
    {
        $this->getEntityManager()->flush();

        return $typeBillet;
    }

    
    public function delete(TypeBillet $typeBillet): void
    {
        $this->getEntityManager()->remove($typeBillet);
        $this->getEntityManager()->flush();
    }

    
    public function findByOrganizer(User $organizer): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.evenement', 'e')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('App\Entity\OrganisateurEvenement', 'oe', 'WITH', 'oe.evenement = e')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->where('op.utilisateur = :organizer OR op2.utilisateur = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('t.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function findByOrganizerPaginated(User $organizer, int $page = 1, int $limit = 6, ?string $categorieFilter = null, ?string $segmentFilter = null, ?Event $event = null): Paginator
    {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.evenement', 'e')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('App\Entity\OrganisateurEvenement', 'oe', 'WITH', 'oe.evenement = e')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->where('op.utilisateur = :organizer OR op2.utilisateur = :organizer')
            ->setParameter('organizer', $organizer);

        if ($event !== null) {
            $qb->andWhere('e.id = :eventId')
                ->setParameter('eventId', $event->getId());
        }

        if ($categorieFilter) {
            $qb->andWhere('t.configurationCategorie = :categorie')
               ->setParameter('categorie', $categorieFilter);
        }

        if ($segmentFilter) {
            $qb->andWhere('t.configurationSegment = :segment')
               ->setParameter('segment', $segmentFilter);
        }

        $qb->orderBy('t.creeLe', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return new Paginator($qb, true);
    }


    public function findStockAlertsForOrganizer(User $organizer): array
    {
        $now = new \DateTime();
        
        $qb = $this->createQueryBuilder('tb')
            ->select([
                'tb.id as type_billet_id',
                'tb.nom as type_billet_nom',
                'tb.prixDeBase',
                'e.id as evenement_id',
                'e.titre as evenement_titre',
                'e.commenceLe',
                'e.seTermineLe as se_termine_le',
                'e.statut',
                'cc.nom as categorie_nom',
                'cs.nom as segment_nom',
                'ib.quantiteTotale as quantite_totale',
                'ib.quantiteVendue as quantite_vendue',
                'ib.quantiteReservee as quantite_reservee'
            ])
            ->join('tb.evenement', 'e')
            ->join('tb.inventaire', 'ib')
            ->leftJoin('tb.configurationCategorie', 'cc')
            ->leftJoin('tb.configurationSegment', 'cs')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('App\Entity\OrganisateurEvenement', 'oe', 'WITH', 'oe.evenement = e')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->where('(op.utilisateur = :organizer OR op2.utilisateur = :organizer)')
            ->andWhere('e.statut = :statut')
            ->andWhere('ib.quantiteTotale > 0')
            ->andWhere('(e.seTermineLe >= :now OR (e.seTermineLe IS NULL AND e.commenceLe >= :now))')
            // IMPORTANT: Supprimez ce filtre car vous allez faire le calcul en PHP
            // ->andWhere('(ib.quantiteTotale - ib.quantiteVendue - ib.quantiteReservee) <= 5')
            ->setParameter('organizer', $organizer)
            ->setParameter('statut', 'published')
            ->setParameter('now', $now)
            ->addOrderBy('e.titre', 'ASC')
            ->addOrderBy('tb.nom', 'ASC');
        
        $results = $qb->getQuery()->getArrayResult();
        
        // Filtrer en PHP pour avoir plus de contrôle
        $alerts = [];
        foreach ($results as $result) {
            $disponible = $result['quantite_totale'] - $result['quantite_vendue'] - $result['quantite_reservee'];
            
            // Calculer le pourcentage
            $pourcentage = $result['quantite_totale'] > 0 
                ? round(($disponible / $result['quantite_totale']) * 100, 1)
                : 0;
            
            // Seulement inclure si le stock est bas (≤ 10%)
            if ($pourcentage <= 10) {
                $result['quantite_disponible'] = $disponible;
                $result['pourcentage_restant'] = $pourcentage;
                
                if ($disponible == 0 || $pourcentage <= 5) {
                    $result['niveau_alerte'] = 'critique';
                } elseif ($pourcentage <= 10) {
                    $result['niveau_alerte'] = 'attention';
                } else {
                    continue; // Ignorer si > 10%
                }
                
                $result['liste_attente_count'] = $result['liste_attente_count'] ?? 0;
                $alerts[] = $result;
            }
        }
        
        // Trier par pourcentage (les plus critiques d'abord)
        usort($alerts, function($a, $b) {
            // D'abord par niveau (critique > attention)
            $levelOrder = ['critique' => 1, 'attention' => 2];
            $levelA = $levelOrder[$a['niveau_alerte']] ?? 3;
            $levelB = $levelOrder[$b['niveau_alerte']] ?? 3;
            
            if ($levelA !== $levelB) {
                return $levelA <=> $levelB;
            }
            
            // Ensuite par pourcentage (plus bas d'abord)
            return ($a['pourcentage_restant'] ?? 100) <=> ($b['pourcentage_restant'] ?? 100);
        });
        
        return $alerts;
    }
    
    public function findCategoriesForOrganizer(User $organizer): array
    {
        $qb = $this->createQueryBuilder('tb')
            ->select('DISTINCT cc.nom')
            ->join('tb.evenement', 'e')
            ->join('tb.inventaire', 'ib')
            ->leftJoin('tb.configurationCategorie', 'cc')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('App\Entity\OrganisateurEvenement', 'oe', 'WITH', 'oe.evenement = e')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->where('(op.utilisateur = :organizer OR op2.utilisateur = :organizer)')
            ->andWhere('e.statut = :statut')
            ->andWhere('(e.commenceLe >= :now OR (e.commenceLe < :now AND e.seTermineLe >= :now))')
            ->andWhere('cc.nom IS NOT NULL')
            ->setParameter('organizer', $organizer)
            ->setParameter('statut', 'published')
            ->setParameter('now', new \DateTime())
            ->orderBy('cc.nom', 'ASC');
        
        return $qb->getQuery()->getScalarResult();
    }
    
    public function findSegmentsForOrganizer(User $organizer): array
    {
        $qb = $this->createQueryBuilder('tb')
            ->select('DISTINCT cs.nom')
            ->join('tb.evenement', 'e')
            ->join('tb.inventaire', 'ib')
            ->leftJoin('tb.configurationSegment', 'cs')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('App\Entity\OrganisateurEvenement', 'oe', 'WITH', 'oe.evenement = e')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->where('(op.utilisateur = :organizer OR op2.utilisateur = :organizer)')
            ->andWhere('e.statut = :statut')
            ->andWhere('(e.commenceLe >= :now OR (e.commenceLe < :now AND e.seTermineLe >= :now))')
            ->andWhere('cs.nom IS NOT NULL')
            ->setParameter('organizer', $organizer)
            ->setParameter('statut', 'published')
            ->setParameter('now', new \DateTime())
            ->orderBy('cs.nom', 'ASC');
        
        $results = $qb->getQuery()->getScalarResult();
        
        return array_filter($results, function($result) {
            return $result['nom'] !== 'tous';
        });
    }
}