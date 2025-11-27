<?php

namespace App\Entity;

use App\Repository\UserProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserProfileRepository::class)]
#[ORM\Table(name: 'profils_utilisateurs', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class UserProfile
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', nullable: false)]
    private ?User $utilisateur = null;

    #[ORM\Column(name: 'opt_in_marketing', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $optInMarketing = false;

    #[ORM\Column(name: 'categories_preferees', type: Types::SIMPLE_ARRAY, nullable: true)]
    private ?array $categoriesPreferees = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    public function __construct()
    {
        $this->modifieLe = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->modifieLe = new \DateTimeImmutable();
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function isOptInMarketing(): bool
    {
        return $this->optInMarketing;
    }

    public function setOptInMarketing(bool $optInMarketing): static
    {
        $this->optInMarketing = $optInMarketing;

        return $this;
    }

    public function getCategoriesPreferees(): ?array
    {
        return $this->categoriesPreferees;
    }

    public function setCategoriesPreferees(?array $categoriesPreferees): static
    {
        $this->categoriesPreferees = $categoriesPreferees;

        return $this;
    }

    public function getModifieLe(): ?\DateTimeInterface
    {
        return $this->modifieLe;
    }

    public function setModifieLe(\DateTimeInterface $modifieLe): static
    {
        $this->modifieLe = $modifieLe;

        return $this;
    }
}

