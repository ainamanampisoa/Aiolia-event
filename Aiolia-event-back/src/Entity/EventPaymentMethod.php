<?php

namespace App\Entity;

use App\Repository\Organisateur\EventPaymentMethodRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventPaymentMethodRepository::class)]
#[ORM\Table(name: 'modes_paiement_evenements', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class EventPaymentMethod
{
    public const METHOD_ESPACE = 'espace';
    public const METHOD_MVOLA = 'mvola';
    public const METHOD_ORANGE = 'orange';
    public const METHOD_AIRTEL = 'airtel';
    public const METHOD_TELMA = 'telma';
    public const METHOD_MASTERCARD = 'mastercard';
    public const METHOD_VISA = 'visa';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHODS = [
        self::METHOD_ESPACE => 'Espace',
        self::METHOD_MVOLA => 'MVola',
        self::METHOD_ORANGE => 'Orange Money',
        self::METHOD_AIRTEL => 'Airtel Money',
        self::METHOD_TELMA => 'Telma',
        self::METHOD_MASTERCARD => 'Mastercard',
        self::METHOD_VISA => 'Visa',
        self::METHOD_BANK_TRANSFER => 'Virement bancaire',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'paymentMethods')]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\Column(name: 'mode_paiement', type: Types::STRING, length: 50)]
    private string $paymentMethod;

    #[ORM\Column(name: 'est_actif', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\PrePersist]
    public function initializeCreatedAt(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
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

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        if (!in_array($paymentMethod, array_keys(self::METHODS))) {
            throw new \InvalidArgumentException("Mode de paiement invalide: {$paymentMethod}");
        }
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPaymentMethodLabel(): string
    {
        return self::METHODS[$this->paymentMethod] ?? $this->paymentMethod;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}

