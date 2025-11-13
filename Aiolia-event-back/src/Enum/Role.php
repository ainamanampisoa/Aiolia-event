<?php

namespace App\Enum;

final class Role
{
    public const USER = 'user';
    public const ORGANIZER = 'organizer';
    public const ADMIN = 'admin';

    private const SECURITY_MAP = [
        self::USER => 'ROLE_USER',
        self::ORGANIZER => 'ROLE_ORGANIZER',
        self::ADMIN => 'ROLE_ADMIN',
    ];

    private function __construct()
    {
    }

    public static function normalize(string $role): string
    {
        return strtolower(trim($role));
    }

    public static function isValid(string $role): bool
    {
        return array_key_exists($role, self::SECURITY_MAP);
    }

    public static function assertValid(string $role): void
    {
        if (!self::isValid($role)) {
            throw new \InvalidArgumentException(sprintf('Rôle invalide : %s', $role));
        }
    }

    public static function toSecurityRoles(string $role): array
    {
        self::assertValid($role);

        $securityRoles = ['ROLE_USER'];
        $mapped = self::SECURITY_MAP[$role];

        if ($mapped !== 'ROLE_USER') {
            $securityRoles[] = $mapped;
        }

        return $securityRoles;
    }

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return array_keys(self::SECURITY_MAP);
    }
}

