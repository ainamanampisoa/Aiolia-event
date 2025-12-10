<?php

namespace App\Entity;

use App\Repository\Organisateur\TypeAccessibiliteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeAccessibiliteRepository::class)]
#[ORM\Table(name: 'types_accessibilite', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class TypeAccessibilite
{
    public const CODE_GENERAL = 'general';
    public const CODE_HEARING = 'hearing';
    public const CODE_VISUAL = 'visual';
    public const CODE_PARKING = 'parking';
    public const CODE_TOILET = 'toilet';
    public const CODE_PETS = 'pets';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 50, unique: true)]
    private string $code;

    #[ORM\Column(name: 'libelle', type: Types::STRING, length: 255)]
    private string $libelle;

    #[ORM\Column(name: 'url_image', type: Types::STRING, length: 255, nullable: true)]
    private ?string $urlImage = null;

    #[ORM\Column(name: 'ordre_affichage', type: Types::INTEGER, options: ['default' => 0])]
    private int $ordreAffichage = 0;

    #[ORM\Column(name: 'est_actif', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $estActif = true;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\OneToMany(targetEntity: LienAccessibiliteEvenement::class, mappedBy: 'typeAccessibilite', cascade: ['persist', 'remove'])]
    private Collection $liensEvenements;

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $this->creeLe ??= new \DateTimeImmutable();
    }

    public function __construct()
    {
        $this->liensEvenements = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getUrlImage(): ?string
    {
        return $this->urlImage;
    }

    public function setUrlImage(?string $urlImage): static
    {
        $this->urlImage = $urlImage;

        return $this;
    }

    public function getOrdreAffichage(): int
    {
        return $this->ordreAffichage;
    }

    public function setOrdreAffichage(int $ordreAffichage): static
    {
        $this->ordreAffichage = $ordreAffichage;

        return $this;
    }

    public function isEstActif(): bool
    {
        return $this->estActif;
    }

    public function setEstActif(bool $estActif): static
    {
        $this->estActif = $estActif;

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

    /**
     * @return Collection<int, LienAccessibiliteEvenement>
     */
    public function getLiensEvenements(): Collection
    {
        return $this->liensEvenements;
    }

    public function addLienEvenement(LienAccessibiliteEvenement $lienEvenement): static
    {
        if (!$this->liensEvenements->contains($lienEvenement)) {
            $this->liensEvenements->add($lienEvenement);
            $lienEvenement->setTypeAccessibilite($this);
        }

        return $this;
    }

    public function removeLienEvenement(LienAccessibiliteEvenement $lienEvenement): static
    {
        if ($this->liensEvenements->removeElement($lienEvenement)) {
            if ($lienEvenement->getTypeAccessibilite() === $this) {
                $lienEvenement->setTypeAccessibilite(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }
}

