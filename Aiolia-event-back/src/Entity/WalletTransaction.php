<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'transactions_portefeuilles', schema: 'aiolia')]
class WalletTransaction
{
    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';
    public const TYPE_POINTS_CREDIT = 'points_credit';
    public const TYPE_POINTS_DEBIT = 'points_debit';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Wallet::class)]
    #[ORM\JoinColumn(name: 'id_portefeuille', referencedColumnName: 'id', nullable: false)]
    private ?Wallet $wallet = null;

    #[ORM\Column(name: 'type_transaction', type: Types::STRING, length: 20, columnDefinition: "wallet_transaction_type_enum NOT NULL")]
    private string $typeTransaction;

    #[ORM\Column(name: 'statut', type: Types::STRING, length: 20, options: ['default' => self::STATUS_PENDING], columnDefinition: "wallet_transaction_status_enum NOT NULL DEFAULT 'pending'")]
    private string $statut = self::STATUS_PENDING;

    #[ORM\Column(name: 'montant', type: Types::DECIMAL, precision: 14, scale: 2, options: ['default' => 0])]
    private string $montant = '0.00';

    #[ORM\Column(name: 'variation_points', type: Types::INTEGER, options: ['default' => 0])]
    private int $variationPoints = 0;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'entite_liee', type: Types::TEXT, nullable: true)]
    private ?string $entiteLiee = null;

    #[ORM\Column(name: 'id_lie', type: Types::BIGINT, nullable: true)]
    private ?string $idLie = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    public function __construct()
    {
        $this->creeLe = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function setWallet(?Wallet $wallet): static
    {
        $this->wallet = $wallet;
        return $this;
    }

    public function getTypeTransaction(): string
    {
        return $this->typeTransaction;
    }

    public function setTypeTransaction(string $typeTransaction): static
    {
        $this->typeTransaction = $typeTransaction;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getMontant(): string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getVariationPoints(): int
    {
        return $this->variationPoints;
    }

    public function setVariationPoints(int $variationPoints): static
    {
        $this->variationPoints = $variationPoints;
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

    public function getEntiteLiee(): ?string
    {
        return $this->entiteLiee;
    }

    public function setEntiteLiee(?string $entiteLiee): static
    {
        $this->entiteLiee = $entiteLiee;
        return $this;
    }

    public function getIdLie(): ?string
    {
        return $this->idLie;
    }

    public function setIdLie(?string $idLie): static
    {
        $this->idLie = $idLie;
        return $this;
    }

    public function getCreeLe(): ?\DateTimeInterface
    {
        return $this->creeLe;
    }

    public function setCreeLe(?\DateTimeInterface $creeLe): static
    {
        $this->creeLe = $creeLe;
        return $this;
    }
}

