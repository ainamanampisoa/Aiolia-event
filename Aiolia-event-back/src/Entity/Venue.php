<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'lieux', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class Venue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: OrganizerProfile::class)]
    #[ORM\JoinColumn(name: 'id_profil_organisateur', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?OrganizerProfile $organizerProfile = null;

    #[ORM\Column(name: 'nom', type: Types::TEXT)]
    private string $nom;

    #[ORM\Column(name: 'slug', type: Types::STRING, length: 255, unique: true, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'ligne_adresse_1', type: Types::TEXT, nullable: true)]
    private ?string $ligneAdresse1 = null;

    #[ORM\Column(name: 'ligne_adresse_2', type: Types::TEXT, nullable: true)]
    private ?string $ligneAdresse2 = null;

    #[ORM\Column(name: 'ville', type: Types::TEXT, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(name: 'region', type: Types::TEXT, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(name: 'code_postal', type: Types::TEXT, nullable: true)]
    private ?string $codePostal = null;

    #[ORM\Column(name: 'code_pays', type: Types::STRING, length: 2, options: ['default' => 'MG', 'fixed' => true])]
    private string $codePays = 'MG';

    #[ORM\Column(name: 'latitude', type: Types::DECIMAL, precision: 9, scale: 6, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(name: 'longitude', type: Types::DECIMAL, precision: 9, scale: 6, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(name: 'fuseau_horaire', type: Types::STRING, length: 64, options: ['default' => 'Indian/Antananarivo'])]
    private string $fuseauHoraire = 'Indian/Antananarivo';

    #[ORM\Column(name: 'email_contact', type: Types::STRING, length: 255, nullable: true, columnDefinition: 'CITEXT')]
    private ?string $emailContact = null;

    #[ORM\Column(name: 'telephone_contact', type: Types::STRING, length: 20, nullable: true)]
    private ?string $telephoneContact = null;

    #[ORM\Column(name: 'url_site_web', type: Types::TEXT, nullable: true)]
    private ?string $urlSiteWeb = null;

    #[ORM\Column(name: 'capacite', type: Types::INTEGER, nullable: true)]
    private ?int $capacite = null;

    #[ORM\Column(name: 'equipements', type: Types::JSON, nullable: true)]
    private ?array $equipements = null;

    #[ORM\Column(name: 'notes_acces', type: Types::TEXT, nullable: true)]
    private ?string $notesAcces = null;

    #[ORM\Column(name: 'est_actif', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $estActif = true;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

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

    public function getOrganizerProfile(): ?OrganizerProfile
    {
        return $this->organizerProfile;
    }

    public function setOrganizerProfile(?OrganizerProfile $organizerProfile): static
    {
        $this->organizerProfile = $organizerProfile;
        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
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

    public function getLigneAdresse1(): ?string
    {
        return $this->ligneAdresse1;
    }

    public function setLigneAdresse1(?string $ligneAdresse1): static
    {
        $this->ligneAdresse1 = $ligneAdresse1;
        return $this;
    }

    public function getLigneAdresse2(): ?string
    {
        return $this->ligneAdresse2;
    }

    public function setLigneAdresse2(?string $ligneAdresse2): static
    {
        $this->ligneAdresse2 = $ligneAdresse2;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;
        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): static
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    public function getCodePays(): string
    {
        return $this->codePays;
    }

    public function setCodePays(string $codePays): static
    {
        $this->codePays = $codePays;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getFuseauHoraire(): string
    {
        return $this->fuseauHoraire;
    }

    public function setFuseauHoraire(string $fuseauHoraire): static
    {
        $this->fuseauHoraire = $fuseauHoraire;
        return $this;
    }

    public function getEmailContact(): ?string
    {
        return $this->emailContact;
    }

    public function setEmailContact(?string $emailContact): static
    {
        $this->emailContact = $emailContact;
        return $this;
    }

    public function getTelephoneContact(): ?string
    {
        return $this->telephoneContact;
    }

    public function setTelephoneContact(?string $telephoneContact): static
    {
        $this->telephoneContact = $telephoneContact;
        return $this;
    }

    public function getUrlSiteWeb(): ?string
    {
        return $this->urlSiteWeb;
    }

    public function setUrlSiteWeb(?string $urlSiteWeb): static
    {
        $this->urlSiteWeb = $urlSiteWeb;
        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $capacite): static
    {
        $this->capacite = $capacite;
        return $this;
    }

    public function getEquipements(): ?array
    {
        return $this->equipements;
    }

    public function setEquipements(?array $equipements): static
    {
        $this->equipements = $equipements;
        return $this;
    }

    public function getNotesAcces(): ?string
    {
        return $this->notesAcces;
    }

    public function setNotesAcces(?string $notesAcces): static
    {
        $this->notesAcces = $notesAcces;
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

