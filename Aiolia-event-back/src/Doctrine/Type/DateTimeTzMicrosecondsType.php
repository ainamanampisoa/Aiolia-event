<?php

namespace App\Doctrine\Type;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeTzType;

class DateTimeTzMicrosecondsType extends DateTimeTzType
{
    private const SUPPORTED_FORMATS = [
        'Y-m-d H:i:s.uO',
        'Y-m-d H:i:s.uP',
        'Y-m-d H:i:sO',
        'Y-m-d H:i:sP',
    ];

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof DateTimeInterface) {
            $normalized = DateTime::createFromFormat(
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

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateTime
    {
        if ($value === null || $value instanceof DateTime) {
            return $value;
        }

        foreach (self::SUPPORTED_FORMATS as $format) {
            $dateTime = DateTime::createFromFormat($format, $value);
            if ($dateTime instanceof DateTimeInterface) {
                return $dateTime instanceof DateTime ? $dateTime : DateTime::createFromFormat(
                    'Y-m-d H:i:sO',
                    $dateTime->format('Y-m-d H:i:sO'),
                    $dateTime->getTimezone()
                );
            }
        }

        return parent::convertToPHPValue($value, $platform);
    }
}

