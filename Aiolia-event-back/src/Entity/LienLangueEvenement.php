<?php

namespace App\Entity;

use App\Repository\Organisateur\LienLangueEvenementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LienLangueEvenementRepository::class)]
#[ORM\Table(name: 'liens_langues_evenements', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class LienLangueEvenement
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'liensLangues')]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $evenement = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Langue::class, inversedBy: 'liensEvenements')]
    #[ORM\JoinColumn(name: 'id_langue', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Langue $langue = null;

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

    public function getLangue(): ?Langue
    {
        return $this->langue;
    }

    public function setLangue(?Langue $langue): static
    {
        $this->langue = $langue;

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

