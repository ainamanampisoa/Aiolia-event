<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {
    }

    /**
     * Enregistre une action dans l'audit log
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $details = null,
        ?User $performedBy = null
    ): void {
        $auditLog = new AuditLog();
        $auditLog->setScope(explode('.', $action, 2)[0] ?? 'general');
        $auditLog->setAction($action);
        $auditLog->setEntityType($entityType);
        $auditLog->setEntityId($entityId);
        $auditLog->setChanges($details);
        $auditLog->setActor($performedBy);

        // Récupérer les informations de la requête
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $auditLog->setIpAddress($request->getClientIp());
            $auditLog->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();
    }

    /**
     * Actions prédéfinies
     */
    public const ACTION_USER_CREATED = 'user_created';
    public const ACTION_USER_UPDATED = 'user_updated';
    public const ACTION_USER_DELETED = 'user_deleted';
    public const ACTION_USER_VALIDATED = 'user_validated';
    public const ACTION_USER_REJECTED = 'user_rejected';
    public const ACTION_ROLE_CHANGED = 'role_changed';
    public const ACTION_EVENT_CREATED = 'event_created';
    public const ACTION_EVENT_UPDATED = 'event_updated';
    public const ACTION_EVENT_DELETED = 'event_deleted';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_PASSWORD_RESET = 'password_reset';
}

