<?php

namespace App\Enum;

final class BilletCategorieEnum
{
    public const TOUS = 'tous';
    public const STANDARD = 'standard';
    public const VIP = 'vip';
    public const PREVENTE = 'prevente';
    public const ACCES_COULISSES = 'acces_coulisses';

    /**
     * Empêche l'instanciation de la classe (classe utilitaire)
     */
    private function __construct()
    {
        // Cette classe ne doit pas être instanciée
    }

    public static function normalize(string $categorie): string
    {
        return strtolower(trim($categorie));
    }

    public static function isValid(string $categorie): bool
    {
        return in_array($categorie, self::all(), true);
    }

    public static function assertValid(string $categorie): void
    {
        if (!self::isValid($categorie)) {
            throw new \InvalidArgumentException(sprintf('Catégorie de billet invalide : %s', $categorie));
        }
    }

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::TOUS,
            self::STANDARD,
            self::VIP,
            self::PREVENTE,
            self::ACCES_COULISSES,
        ];
    }
}

