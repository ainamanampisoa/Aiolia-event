<?php

namespace App\Entity;

use App\Repository\Organisateur\InventaireBilletRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventaireBilletRepository::class)]
#[ORM\Table(name: 'inventaire_billets', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class InventaireBillet
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: TypeBillet::class, inversedBy: 'inventaire')]
    #[ORM\JoinColumn(name: 'id_type_billet', referencedColumnName: 'id')]
    private ?TypeBillet $typeBillet = null;

    #[ORM\Column(name: 'quantite_totale', type: Types::INTEGER)]
    private int $quantiteTotale = 0;

    #[ORM\Column(name: 'quantite_reservee', type: Types::INTEGER, options: ['default' => 0])]
    private int $quantiteReservee = 0;

    #[ORM\Column(name: 'quantite_vendue', type: Types::INTEGER, options: ['default' => 0])]
    private int $quantiteVendue = 0;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateModifiedAt(): void
    {
        $this->modifieLe = new \DateTimeImmutable();
    }

    public function getTypeBillet(): ?TypeBillet
    {
        return $this->typeBillet;
    }

    public function setTypeBillet(?TypeBillet $typeBillet): static
    {
        $this->typeBillet = $typeBillet;

        return $this;
    }

    public function getQuantiteTotale(): int
    {
        return $this->quantiteTotale;
    }

    public function setQuantiteTotale(int $quantiteTotale): static
    {
        $this->quantiteTotale = $quantiteTotale;

        return $this;
    }

    public function getQuantiteReservee(): int
    {
        return $this->quantiteReservee;
    }

    public function setQuantiteReservee(int $quantiteReservee): static
    {
        $this->quantiteReservee = $quantiteReservee;

        return $this;
    }

    public function getQuantiteVendue(): int
    {
        return $this->quantiteVendue;
    }

    public function setQuantiteVendue(int $quantiteVendue): static
    {
        $this->quantiteVendue = $quantiteVendue;

        return $this;
    }

    public function getQuantiteDisponible(): int
    {
        return $this->quantiteTotale - $this->quantiteReservee - $this->quantiteVendue;
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
}


