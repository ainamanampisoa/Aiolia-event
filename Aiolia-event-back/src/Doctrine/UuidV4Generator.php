<?php

namespace App\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;

class UuidV4Generator extends AbstractIdGenerator
{
    public function generateId(EntityManagerInterface $em, $entity): string
    {
        return self::v4();
    }

    private static function v4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

