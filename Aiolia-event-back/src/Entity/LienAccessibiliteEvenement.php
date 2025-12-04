<?php

namespace App\Entity;

use App\Repository\Organisateur\LienAccessibiliteEvenementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LienAccessibiliteEvenementRepository::class)]
#[ORM\Table(name: 'liens_accessibilite_evenements', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class LienAccessibiliteEvenement
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'liensAccessibilites')]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $evenement = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: TypeAccessibilite::class, inversedBy: 'liensEvenements')]
    #[ORM\JoinColumn(name: 'id_type_accessibilite', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?TypeAccessibilite $typeAccessibilite = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $this->creeLe ??= new \DateTimeImmutable();
    }

    public function getEvenement(): ?Event
    {
        return $this->evenement;
    }

    public function setEvenement(?Event $evenement): static
    {
        $this->evenement = $evenement;

        return $this;
    }

    public function getTypeAccessibilite(): ?TypeAccessibilite
    {
        return $this->typeAccessibilite;
    }

    public function setTypeAccessibilite(?TypeAccessibilite $typeAccessibilite): static
    {
        $this->typeAccessibilite = $typeAccessibilite;

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

