<?php

namespace App\Enum;

final class BilletSegmentEnum
{
    public const ADULTE = 'adulte';
    public const ENFANT = 'enfant';
    public const TOUS = 'tous';

    /**
     * Empêche l'instanciation de la classe (classe utilitaire)
     */
    private function __construct()
    {
        // Cette classe ne doit pas être instanciée
    }

    public static function normalize(string $segment): string
    {
        return strtolower(trim($segment));
    }

    public static function isValid(string $segment): bool
    {
        return in_array($segment, self::all(), true);
    }

    public static function assertValid(string $segment): void
    {
        if (!self::isValid($segment)) {
            throw new \InvalidArgumentException(sprintf('Segment de billet invalide : %s', $segment));
        }
    }

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::ADULTE,
            self::ENFANT,
            self::TOUS,
        ];
    }
}

