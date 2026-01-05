<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    private Connection $connection;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
        $this->connection = $this->getEntityManager()->getConnection();
    }

    /**
     * Récupère un utilisateur par son email (Doctrine ORM).
     */
    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les informations d'un utilisateur.
     */
    public function findUserInfo(int $userId): array
    {
        $sql = <<<SQL
            SELECT 
                id,
                email,
                first_name,
                last_name,
                phone,
                language_code,
                timezone,
                avatar_url,
                password_hash,
                created_at
            FROM aiolia.users
            WHERE id = :userId
        SQL;

        $row = $this->connection->executeQuery($sql, ['userId' => $userId])->fetchAssociative();

        if (false === $row) {
            return [];
        }

        // Formater le nom complet
        $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

        // Formater la date de création
        $createdAt = isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null;
        $createdAtFormatted = $createdAt ? $createdAt->format('d M Y') : '';

        // Récupérer la date de dernière modification du mot de passe (si disponible)
        $passwordLastModified = $createdAtFormatted;

        return [
            'id' => (int) $row['id'],
            'email' => $row['email'],
            'first_name' => $row['first_name'] ?? '',
            'last_name' => $row['last_name'] ?? '',
            'full_name' => $fullName,
            'phone' => $row['phone'] ?? '',
            'language_code' => $row['language_code'] ?? 'fr-FR',
            'timezone' => $row['timezone'] ?? 'Indian/Antananarivo',
            'avatar_url' => $row['avatar_url'],
            'password_last_modified' => $passwordLastModified,
            'created_at' => $createdAt,
        ];
    }

    /**
     * Met à jour les informations d'un utilisateur.
     */
    public function updateUser(int $userId, array $updateFields, array $params): void
    {
        if (empty($updateFields)) {
            return;
        }

        $sql = 'UPDATE aiolia.users SET ' . implode(', ', $updateFields) . ' WHERE id = :userId';
        $params['userId'] = $userId;
        $this->connection->executeStatement($sql, $params);
    }

    /**
     * Met à jour une préférence utilisateur.
     */
    public function updateUserPreference(int $userId, string $key, mixed $value): void
    {
        $this->connection->executeStatement(
            'INSERT INTO aiolia.user_preferences (user_id, preference_key, preference_value, updated_at)
             VALUES (:userId, :key, :value::jsonb, NOW())
             ON CONFLICT (user_id, preference_key)
             DO UPDATE SET preference_value = :value::jsonb, updated_at = NOW()',
            [
                'userId' => $userId,
                'key' => $key,
                'value' => json_encode($value, JSON_THROW_ON_ERROR),
            ]
        );
    }

    /**
     * Récupère les préférences utilisateur.
     */
    public function findUserPreferences(int $userId): array
    {
        $sql = <<<SQL
            SELECT preference_key, preference_value
            FROM aiolia.user_preferences
            WHERE user_id = :userId
        SQL;

        $rows = $this->connection->executeQuery($sql, ['userId' => $userId])->fetchAllAssociative();

        $preferences = [
            'notifications' => [
                'ticket_alerts' => true,
                'event_reminders' => true,
                'newsletters' => false,
            ],
            'security' => [
                'two_factor_enabled' => false,
            ],
            'appearance' => [
                'theme' => 'light',
            ],
        ];

        foreach ($rows as $row) {
            $key = $row['preference_key'];
            $value = json_decode($row['preference_value'], true);

            if ($key === 'notifications') {
                $preferences['notifications'] = array_merge($preferences['notifications'], $value ?? []);
            } elseif ($key === 'security') {
                $preferences['security'] = array_merge($preferences['security'], $value ?? []);
            } elseif ($key === 'appearance') {
                $preferences['appearance'] = array_merge($preferences['appearance'], $value ?? []);
            } else {
                $preferences[$key] = $value;
            }
        }

        return $preferences;
    }

    /**
     * Met à jour l'URL de l'avatar de l'utilisateur.
     */
    public function updateAvatarUrl(int $userId, string $avatarUrl): void
    {
        $this->connection->executeStatement(
            'UPDATE aiolia.users SET avatar_url = :avatar_url, updated_at = NOW() WHERE id = :userId',
            [
                'userId' => $userId,
                'avatar_url' => $avatarUrl,
            ]
        );
    }

    /**
     * Vérifie si l'utilisateur a activé les rappels d'événements
     */
    public function hasEventRemindersEnabled(int $userId): bool
    {
        $sql = <<<SQL
            SELECT preference_value
            FROM aiolia.user_preferences
            WHERE user_id = :user_id
              AND preference_key = 'notifications'
        SQL;

        $preferences = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchOne();
        
        if ($preferences) {
            $prefs = json_decode($preferences, true);
            return $prefs['event_reminders'] ?? true; // Par défaut activé
        }

        // Par défaut, les rappels sont activés
        return true;
    }

    /**
     * Récupère le budget mensuel de l'utilisateur
     */
    public function findUserBudget(int $userId): ?array
    {
        $sql = <<<SQL
            SELECT preference_value as monthly_budget
            FROM aiolia.user_preferences
            WHERE user_id = :user_id
              AND preference_key = 'monthly_budget'
            LIMIT 1
        SQL;

        $result = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchAssociative();
        
        if ($result && !empty($result['monthly_budget'])) {
            return [
                'monthly_budget' => (float) $result['monthly_budget'],
            ];
        }

        return null;
    }

    /**
     * Met à jour ou crée le budget mensuel de l'utilisateur
     */
    public function updateUserBudget(int $userId, float $monthlyBudget): bool
    {
        // Vérifier si la préférence existe déjà
        $checkSql = <<<SQL
            SELECT id FROM aiolia.user_preferences
            WHERE user_id = :user_id AND preference_key = 'monthly_budget'
        SQL;

        $exists = $this->connection->executeQuery($checkSql, ['user_id' => $userId])->fetchOne();

        if ($exists) {
            // Mise à jour
            $sql = <<<SQL
                UPDATE aiolia.user_preferences
                SET preference_value = :budget, updated_at = NOW()
                WHERE user_id = :user_id AND preference_key = 'monthly_budget'
            SQL;
        } else {
            // Insertion
            $sql = <<<SQL
                INSERT INTO aiolia.user_preferences (user_id, preference_key, preference_value, created_at, updated_at)
                VALUES (:user_id, 'monthly_budget', :budget, NOW(), NOW())
            SQL;
        }

        $this->connection->executeStatement($sql, [
            'user_id' => $userId,
            'budget' => (string) $monthlyBudget
        ]);

        return true;
    }
}
