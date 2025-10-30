<?php

namespace App\Entity;

use App\Repository\PromotionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PromotionRepository::class)]
#[ORM\Table(name: 'promotions')]
class Promotion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0, max: 100)]
    private ?string $discountPercentage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $maxDiscountAmount = null;

    #[ORM\Column]
    private int $usageLimit = 0;

    #[ORM\Column]
    private int $usedCount = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $validFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    #[Assert\GreaterThan(propertyPath: 'validFrom')]
    private ?\DateTimeInterface $validUntil = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'promotions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtoupper($code);
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
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

    public function getDiscountPercentage(): ?string
    {
        return $this->discountPercentage;
    }

    public function setDiscountPercentage(string $discountPercentage): static
    {
        $this->discountPercentage = $discountPercentage;
        return $this;
    }

    public function getMaxDiscountAmount(): ?string
    {
        return $this->maxDiscountAmount;
    }

    public function setMaxDiscountAmount(?string $maxDiscountAmount): static
    {
        $this->maxDiscountAmount = $maxDiscountAmount;
        return $this;
    }

    public function getUsageLimit(): int
    {
        return $this->usageLimit;
    }

    public function setUsageLimit(int $usageLimit): static
    {
        $this->usageLimit = $usageLimit;
        return $this;
    }

    public function getUsedCount(): int
    {
        return $this->usedCount;
    }

    public function setUsedCount(int $usedCount): static
    {
        $this->usedCount = $usedCount;
        return $this;
    }

    public function getValidFrom(): ?\DateTimeInterface
    {
        return $this->validFrom;
    }

    public function setValidFrom(\DateTimeInterface $validFrom): static
    {
        $this->validFrom = $validFrom;
        return $this;
    }

    public function getValidUntil(): ?\DateTimeInterface
    {
        return $this->validUntil;
    }

    public function setValidUntil(\DateTimeInterface $validUntil): static
    {
        $this->validUntil = $validUntil;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
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

    public function isValid(): bool
    {
        $now = new \DateTime();
        return $this->isActive && 
               $this->validFrom <= $now && 
               $this->validUntil >= $now &&
               ($this->usageLimit === 0 || $this->usedCount < $this->usageLimit);
    }

    public function canBeUsed(): bool
    {
        return $this->isValid() && 
               ($this->usageLimit === 0 || $this->usedCount < $this->usageLimit);
    }

    public function calculateDiscount(float $originalPrice): float
    {
        if (!$this->isValid()) {
            return 0;
        }

        $discountAmount = $originalPrice * ((float) $this->discountPercentage / 100);
        
        if ($this->maxDiscountAmount && $discountAmount > (float) $this->maxDiscountAmount) {
            $discountAmount = (float) $this->maxDiscountAmount;
        }

        return $discountAmount;
    }

    public function usePromotion(): static
    {
        if ($this->canBeUsed()) {
            $this->usedCount++;
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }
}

