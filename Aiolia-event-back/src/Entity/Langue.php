<?php

namespace App\Entity;

use App\Repository\Organisateur\LangueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LangueRepository::class)]
#[ORM\Table(name: 'langues', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class Langue
{
    public const CODE_MG = 'mg';
    public const CODE_FR = 'fr';
    public const CODE_EN = 'en';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 10, unique: true)]
    private string $code;

    #[ORM\Column(name: 'libelle', type: Types::STRING, length: 255)]
    private string $libelle;

    #[ORM\Column(name: 'est_actif', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $estActif = true;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\OneToMany(targetEntity: LienLangueEvenement::class, mappedBy: 'langue', cascade: ['persist', 'remove'])]
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
     * @return Collection<int, LienLangueEvenement>
     */
    public function getLiensEvenements(): Collection
    {
        return $this->liensEvenements;
    }

    public function addLienEvenement(LienLangueEvenement $lienEvenement): static
    {
        if (!$this->liensEvenements->contains($lienEvenement)) {
            $this->liensEvenements->add($lienEvenement);
            $lienEvenement->setLangue($this);
        }

        return $this;
    }

    public function removeLienEvenement(LienLangueEvenement $lienEvenement): static
    {
        if ($this->liensEvenements->removeElement($lienEvenement)) {
            if ($lienEvenement->getLangue() === $this) {
                $lienEvenement->setLangue(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }
}

