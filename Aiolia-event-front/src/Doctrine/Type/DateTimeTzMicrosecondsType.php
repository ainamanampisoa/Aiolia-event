<?php

namespace App\Doctrine\Type;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeTzImmutableType;

class DateTimeTzMicrosecondsType extends DateTimeTzImmutableType
{
    private const SUPPORTED_FORMATS = [
        'Y-m-d H:i:s.uO',
        'Y-m-d H:i:s.uP',
        'Y-m-d H:i:sO',
        'Y-m-d H:i:sP',
        'Y-m-d\TH:i:s.uO',
        'Y-m-d\TH:i:s.uP',
        'Y-m-d\TH:i:sO',
        'Y-m-d\TH:i:sP',
    ];

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof DateTimeInterface) {
            $normalized = DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:sO',
                $value->format('Y-m-d H:i:sO'),
                $value->getTimezone()
            );

            if ($normalized !== false) {
                $value = $normalized;
            }
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateTimeImmutable
    {
        if ($value === null || $value instanceof DateTimeImmutable) {
            return $value;
        }

        if (is_string($value)) {
            // Format PostgreSQL avec microsecondes: "2025-12-02 14:14:39.460484+03"
            // DateTimeImmutable peut généralement parser ce format directement
            try {
                return new DateTimeImmutable($value);
            } catch (\Exception $e) {
                // Si ça échoue, essayer de normaliser en enlevant les microsecondes
                try {
                    // Enlever les microsecondes (6 chiffres après le point)
                    $normalizedValue = preg_replace('/\.\d{6}/', '', $value);
                    return new DateTimeImmutable($normalizedValue);
                } catch (\Exception $e2) {
                    // Si ça échoue encore, utiliser la conversion par défaut
                }
            }
        }

        return parent::convertToPHPValue($value, $platform);
    }

    public function getName(): string
    {
        return 'datetimetz_immutable';
    }
}

