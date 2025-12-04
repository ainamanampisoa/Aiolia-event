<?php

namespace App\Entity;

use App\Repository\Organisateur\OrganizerProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrganizerProfileRepository::class)]
#[ORM\Table(name: 'profils_organisateurs', schema: 'aiolia', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uq_profils_organisateurs_utilisateur', columns: ['id_utilisateur'])
])]
#[ORM\HasLifecycleCallbacks]
class OrganizerProfile
{
    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_COMPANY = 'company';
    public const TYPE_NON_PROFIT = 'non_profit';
    public const TYPE_COLLECTIVE = 'collective';

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', nullable: false, unique: true)]
    private ?User $utilisateur = null;

    #[ORM\Column(name: 'nom_affichage', type: Types::TEXT)]
    private string $nomAffichage;

    #[ORM\Column(name: 'nom_legal', type: Types::TEXT, nullable: true)]
    private ?string $nomLegal = null;

    #[ORM\Column(name: 'numero_tva', type: Types::TEXT, nullable: true)]
    private ?string $numeroTva = null;

    #[ORM\Column(name: 'email_support', type: Types::STRING, length: 255, nullable: true, columnDefinition: 'CITEXT')]
    private ?string $emailSupport = null;

    #[ORM\Column(name: 'telephone_support', type: Types::STRING, length: 20, nullable: true, columnDefinition: 'phone_e164')]
    private ?string $telephoneSupport = null;

    #[ORM\Column(name: 'url_site_web', type: Types::TEXT, nullable: true)]
    private ?string $urlSiteWeb = null;

    #[ORM\Column(name: 'biographie', type: Types::TEXT, nullable: true)]
    private ?string $biographie = null;

    #[ORM\Column(name: 'type_organisation', type: Types::STRING, length: 20, options: ['default' => self::TYPE_INDIVIDUAL], columnDefinition: "organizer_type_enum NOT NULL DEFAULT 'individual'")]
    private string $typeOrganisation = self::TYPE_INDIVIDUAL;

    #[ORM\Column(name: 'numero_immatriculation', type: Types::TEXT, nullable: true)]
    private ?string $numeroImmatriculation = null;

    #[ORM\Column(name: 'taille_entreprise', type: Types::TEXT, nullable: true)]
    private ?string $tailleEntreprise = null;

    #[ORM\Column(name: 'statut_verification', type: Types::STRING, length: 20, options: ['default' => self::STATUS_PENDING])]
    private string $statutVerification = self::STATUS_PENDING;

    #[ORM\Column(name: 'onboarding_termine_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $onboardingTermineLe = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    #[ORM\OneToMany(targetEntity: OrganisateurEvenement::class, mappedBy: 'profilOrganisateur', cascade: ['persist', 'remove'])]
    private \Doctrine\Common\Collections\Collection $organisateursEvenements;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->creeLe = $now;
        $this->modifieLe = $now;
        $this->organisateursEvenements = new \Doctrine\Common\Collections\ArrayCollection();
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

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getNomAffichage(): string
    {
        return $this->nomAffichage;
    }

    public function setNomAffichage(string $nomAffichage): static
    {
        $this->nomAffichage = $nomAffichage;

        return $this;
    }

    public function getNomLegal(): ?string
    {
        return $this->nomLegal;
    }

    public function setNomLegal(?string $nomLegal): static
    {
        $this->nomLegal = $nomLegal;

        return $this;
    }

    public function getNumeroTva(): ?string
    {
        return $this->numeroTva;
    }

    public function setNumeroTva(?string $numeroTva): static
    {
        $this->numeroTva = $numeroTva;

        return $this;
    }

    public function getEmailSupport(): ?string
    {
        return $this->emailSupport;
    }

    public function setEmailSupport(?string $emailSupport): static
    {
        $this->emailSupport = $emailSupport;

        return $this;
    }

    public function getTelephoneSupport(): ?string
    {
        return $this->telephoneSupport;
    }

    public function setTelephoneSupport(?string $telephoneSupport): static
    {
        $this->telephoneSupport = $telephoneSupport;

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

    public function getBiographie(): ?string
    {
        return $this->biographie;
    }

    public function setBiographie(?string $biographie): static
    {
        $this->biographie = $biographie;

        return $this;
    }

    public function getTypeOrganisation(): string
    {
        return $this->typeOrganisation;
    }

    public function setTypeOrganisation(string $typeOrganisation): static
    {
        $this->typeOrganisation = $typeOrganisation;

        return $this;
    }

    public function getNumeroImmatriculation(): ?string
    {
        return $this->numeroImmatriculation;
    }

    public function setNumeroImmatriculation(?string $numeroImmatriculation): static
    {
        $this->numeroImmatriculation = $numeroImmatriculation;

        return $this;
    }

    public function getTailleEntreprise(): ?string
    {
        return $this->tailleEntreprise;
    }

    public function setTailleEntreprise(?string $tailleEntreprise): static
    {
        $this->tailleEntreprise = $tailleEntreprise;

        return $this;
    }

    public function getStatutVerification(): string
    {
        return $this->statutVerification;
    }

    public function setStatutVerification(string $statutVerification): static
    {
        $this->statutVerification = $statutVerification;

        return $this;
    }

    public function getOnboardingTermineLe(): ?\DateTimeInterface
    {
        return $this->onboardingTermineLe;
    }

    public function setOnboardingTermineLe(?\DateTimeInterface $onboardingTermineLe): static
    {
        $this->onboardingTermineLe = $onboardingTermineLe;

        return $this;
    }

    public function getCreeLe(): ?\DateTimeInterface
    {
        return $this->creeLe;
    }

    public function setCreeLe(\DateTimeInterface $creeLe): static
    {
        $this->creeLe = $creeLe;

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

    /**
     * @return \Doctrine\Common\Collections\Collection<int, OrganisateurEvenement>
     */
    public function getOrganisateursEvenements(): \Doctrine\Common\Collections\Collection
    {
        return $this->organisateursEvenements;
    }

    /**
     * Récupère tous les événements associés à cet organisateur (via la table de liaison)
     *
     * @return \Doctrine\Common\Collections\Collection<int, Event>
     */
    public function getEvenements(): \Doctrine\Common\Collections\Collection
    {
        $evenements = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($this->organisateursEvenements as $organisateurEvenement) {
            if ($organisateurEvenement->getEvenement()) {
                $evenements->add($organisateurEvenement->getEvenement());
            }
        }
        return $evenements;
    }
}

