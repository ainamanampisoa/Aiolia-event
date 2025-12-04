<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'langues_evenements', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class EventLanguage
{
    public const LANG_MG = 'mg';
    public const LANG_FR = 'fr';
    public const LANG_EN = 'en';

    public const LANGUAGES = [
        self::LANG_MG => 'Malagasy',
        self::LANG_FR => 'Français',
        self::LANG_EN => 'Anglais',
    ];

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'languages')]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\Id]
    #[ORM\Column(name: 'code_langue', type: Types::STRING, length: 10)]
    private string $languageCode;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\PrePersist]
    public function initializeCreatedAt(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): static
    {
        if (!in_array($languageCode, array_keys(self::LANGUAGES))) {
            throw new \InvalidArgumentException("Code langue invalide: {$languageCode}");
        }
        $this->languageCode = $languageCode;
        return $this;
    }

    public function getLanguageLabel(): string
    {
        return self::LANGUAGES[$this->languageCode] ?? $this->languageCode;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}

