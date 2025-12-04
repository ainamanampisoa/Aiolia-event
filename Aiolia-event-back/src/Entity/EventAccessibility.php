<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'accessibilite_evenements', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class EventAccessibility
{
    public const TYPE_WHEELCHAIR = 'wheelchair';
    public const TYPE_HEARING = 'hearing';
    public const TYPE_VISUAL = 'visual';
    public const TYPE_MOBILITY = 'mobility';
    public const TYPE_COGNITIVE = 'cognitive';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_WHEELCHAIR => 'Accès fauteuil roulant',
        self::TYPE_HEARING => 'Accessible aux malentendants',
        self::TYPE_VISUAL => 'Accessible aux malvoyants',
        self::TYPE_MOBILITY => 'Accessible mobilité réduite',
        self::TYPE_COGNITIVE => 'Accessible troubles cognitifs',
        self::TYPE_OTHER => 'Autre',
    ];

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'accessibilities')]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\Id]
    #[ORM\Column(name: 'type_accessibilite', type: Types::STRING, length: 50)]
    private string $accessibilityType;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

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

    public function getAccessibilityType(): string
    {
        return $this->accessibilityType;
    }

    public function setAccessibilityType(string $accessibilityType): static
    {
        if (!in_array($accessibilityType, array_keys(self::TYPES))) {
            throw new \InvalidArgumentException("Type d'accessibilité invalide: {$accessibilityType}");
        }
        $this->accessibilityType = $accessibilityType;
        return $this;
    }

    public function getAccessibilityTypeLabel(): string
    {
        return self::TYPES[$this->accessibilityType] ?? $this->accessibilityType;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}

