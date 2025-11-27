<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'historique_recherches_utilisateurs', schema: 'aiolia')]
class UserSearchHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(name: 'mots_cles', type: Types::TEXT)]
    private string $motsCles;

    #[ORM\Column(name: 'filtres', type: Types::JSON, nullable: true)]
    private ?array $filtres = null;

    #[ORM\Column(name: 'recherche_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $rechercheLe = null;

    public function __construct()
    {
        $this->rechercheLe = new \DateTimeImmutable();
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

    public function getMotsCles(): string
    {
        return $this->motsCles;
    }

    public function setMotsCles(string $motsCles): static
    {
        $this->motsCles = $motsCles;
        return $this;
    }

    public function getFiltres(): ?array
    {
        return $this->filtres;
    }

    public function setFiltres(?array $filtres): static
    {
        $this->filtres = $filtres;
        return $this;
    }

    public function getRechercheLe(): ?\DateTimeInterface
    {
        return $this->rechercheLe;
    }

    public function setRechercheLe(?\DateTimeInterface $rechercheLe): static
    {
        $this->rechercheLe = $rechercheLe;
        return $this;
    }
}

