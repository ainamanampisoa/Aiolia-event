<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    private Connection $connection;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
        $this->connection = $this->getEntityManager()->getConnection();
    }

    /**
     * @return Ticket[] Returns an array of Ticket objects for a user
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $userId)
            ->orderBy('t.purchaseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ticket[] Returns an array of upcoming Ticket objects for a user
     */
    public function findUpcomingTicketsByUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.event', 'e')
            ->andWhere('t.user = :user')
            ->andWhere('e.startDate > :now')
            ->andWhere('t.status = :status')
            ->setParameter('user', $userId)
            ->setParameter('now', new \DateTime())
            ->setParameter('status', 'purchased')
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ticket[] Returns an array of past Ticket objects for a user
     */
    public function findPastTicketsByUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.event', 'e')
            ->andWhere('t.user = :user')
            ->andWhere('e.endDate < :now')
            ->setParameter('user', $userId)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.endDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ticket[] Returns an array of Ticket objects for an event
     */
    public function findByEvent(int $eventId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.event = :event')
            ->setParameter('event', $eventId)
            ->orderBy('t.purchaseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ticket[] Returns an array of Ticket objects by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('status', $status)
            ->orderBy('t.purchaseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByQrCode(string $qrCode): ?Ticket
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.qrCode = :qrCode')
            ->setParameter('qrCode', $qrCode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count tickets sold for an event
     */
    public function countTicketsSoldForEvent(int $eventId): int
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.event = :event')
            ->andWhere('t.status != :cancelled')
            ->setParameter('event', $eventId)
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get total revenue for an event
     */
    public function getTotalRevenueForEvent(int $eventId): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.price)')
            ->andWhere('t.event = :event')
            ->andWhere('t.status != :cancelled')
            ->setParameter('event', $eventId)
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Récupère les billets de l'utilisateur avec leur événement associé (DBAL).
     */
    public function findUserTickets(int $userId, string $filter = 'upcoming'): array
    {
        $sql = <<<SQL
            SELECT
                t.id AS ticket_id,
                t.status AS ticket_status,
                e.id AS event_id,
                e.title AS event_title,
                COALESCE(e.location_override->>'venue_name', v.name) AS venue_name,
                COALESCE(e.location_override->>'address', NULLIF(CONCAT_WS(', ', v.address_line1, v.address_line2), '')) AS venue_address,
                COALESCE(e.location_override->>'city', v.city) AS city,
                COALESCE(e.location_override->>'region', v.region) AS region,
                COALESCE(e.location_override->>'country', v.country_code) AS country_code,
                COALESCE(primary_cat.label, cat.label) AS category_label,
                COALESCE(media.url, e.cover_image_url) AS image_url,
                e.starts_at,
                e.ends_at,
                tt.name AS ticket_type,
                tt.age_category,
                tt.base_price,
                o.id AS order_id,
                o.created_at AS order_created_at
            FROM aiolia.tickets t
            INNER JOIN aiolia.order_items oi ON oi.id = t.order_item_id
            INNER JOIN aiolia.orders o ON o.id = oi.order_id
            INNER JOIN aiolia.ticket_types tt ON tt.id = t.ticket_type_id
            INNER JOIN aiolia.events e ON e.id = tt.event_id
            LEFT JOIN aiolia.venues v ON v.id = e.venue_id
            LEFT JOIN aiolia.event_categories primary_cat ON primary_cat.id = e.primary_category_id
            LEFT JOIN LATERAL (
                SELECT c.label
                FROM aiolia.event_category_links cl
                JOIN aiolia.event_categories c ON c.id = cl.category_id
                WHERE cl.event_id = e.id
                ORDER BY c.display_order ASC, c.label ASC
                LIMIT 1
            ) AS cat ON TRUE
            LEFT JOIN LATERAL (
                SELECT m.url
                FROM aiolia.event_media m
                WHERE m.event_id = e.id
                  AND m.is_public IS TRUE
                ORDER BY m.display_order ASC, m.id ASC
                LIMIT 1
            ) AS media ON TRUE
            WHERE o.user_id = :user_id
              AND o.status = 'paid'
        SQL;

        $params = ['user_id' => $userId];

        // Filtrage par statut "fonctionnel" (à venir / passé / annulé)
        if ($filter === 'upcoming') {
            $sql .= " AND t.status = 'valid' AND e.starts_at >= NOW()";
        } elseif ($filter === 'past') {
            $sql .= " AND e.starts_at < NOW()";
        } elseif ($filter === 'cancelled') {
            $sql .= " AND t.status IN ('cancelled', 'refunded')";
        }

        $sql .= ' ORDER BY e.starts_at DESC, t.id DESC';

        $rows = $this->connection->executeQuery($sql, $params)->fetchAllAssociative();

        return array_map(static function (array $row): array {
            $eventDate = isset($row['starts_at']) ? new \DateTimeImmutable($row['starts_at']) : null;
            $orderDate = isset($row['order_created_at']) ? new \DateTimeImmutable($row['order_created_at']) : null;

            // Générer un code de commande lisible à partir de l'ID
            $orderCode = null;
            if (isset($row['order_id']) && null !== $row['order_id']) {
                $orderCode = 'CMD-' . str_pad((string) $row['order_id'], 6, '0', STR_PAD_LEFT);
            }

            // Déterminer le statut UX
            $statusKey = 'upcoming';
            if (in_array($row['ticket_status'], ['cancelled', 'refunded'], true)) {
                $statusKey = 'cancelled';
            } elseif ($eventDate && $eventDate < new \DateTimeImmutable()) {
                $statusKey = 'past';
            }

            $statusLabel = match ($statusKey) {
                'upcoming' => 'À venir',
                'past' => 'Passé',
                'cancelled' => 'Annulé',
                default => ucfirst((string) $row['ticket_status']),
            };

            // Construire la localisation lisible
            $locationParts = [];
            if (!empty($row['venue_name'])) {
                $locationParts[] = $row['venue_name'];
            }
            if (!empty($row['city'])) {
                $locationParts[] = $row['city'];
            }
            if (!empty($row['region'])) {
                $locationParts[] = $row['region'];
            }
            $location = !empty($locationParts) ? implode(', ', $locationParts) : 'Lieu à confirmer';

            return [
                'id' => (int) $row['ticket_id'],
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
                'ticket_type' => $row['ticket_type'],
                'age_category' => $row['age_category'],
                'price' => isset($row['base_price']) ? (float) $row['base_price'] : null,
                'order_number' => $orderCode,
                'order_date' => $orderDate,
                'event' => [
                    'id' => (int) $row['event_id'],
                    'title' => $row['event_title'],
                    'category' => $row['category_label'] ?? 'Évènement',
                    'image' => $row['image_url'] ?: 'vente-ticket/images/img1.png',
                    'location' => $location,
                    'date' => $eventDate,
                ],
            ];
        }, $rows);
    }

    /**
     * Récupère le prix d'un type de billet.
     */
    public function findTicketTypePrice(int $ticketTypeId): ?float
    {
        try {
            $sql = 'SELECT base_price FROM aiolia.ticket_types WHERE id = :ticket_type_id LIMIT 1';
            $result = $this->connection->executeQuery($sql, ['ticket_type_id' => $ticketTypeId])->fetchAssociative();
            
            if ($result && isset($result['base_price'])) {
                return (float) $result['base_price'];
            }
            
            return null;
        } catch (\Exception $e) {
            error_log('Erreur lors de la récupération du prix du type de billet: ' . $e->getMessage());
            return null;
        }
    }
}

