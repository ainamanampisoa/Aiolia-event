<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'portefeuilles', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class Wallet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', nullable: false, unique: true)]
    private ?User $user = null;

    #[ORM\Column(name: 'devise', type: Types::STRING, length: 3, options: ['default' => 'MGA', 'fixed' => true])]
    private string $devise = 'MGA';

    #[ORM\Column(name: 'solde', type: Types::DECIMAL, precision: 14, scale: 2, options: ['default' => 0])]
    private string $solde = '0.00';

    #[ORM\Column(name: 'solde_points', type: Types::INTEGER, options: ['default' => 0])]
    private int $soldePoints = 0;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->creeLe = $now;
        $this->modifieLe = $now;
    }

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->creeLe ??= $now;
        $this->modifieLe = $now;
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->modifieLe = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getDevise(): string
    {
        return $this->devise;
    }

    public function setDevise(string $devise): static
    {
        $this->devise = $devise;
        return $this;
    }

    public function getSolde(): string
    {
        return $this->solde;
    }

    public function setSolde(string $solde): static
    {
        $this->solde = $solde;
        return $this;
    }

    public function getSoldePoints(): int
    {
        return $this->soldePoints;
    }

    public function setSoldePoints(int $soldePoints): static
    {
        $this->soldePoints = $soldePoints;
        return $this;
    }

    public function getModifieLe(): ?\DateTimeInterface
    {
        return $this->modifieLe;
    }

    public function setModifieLe(?\DateTimeInterface $modifieLe): static
    {
        $this->modifieLe = $modifieLe;
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

