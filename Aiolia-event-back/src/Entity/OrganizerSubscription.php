<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'abonnements_organisateurs', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class OrganizerSubscription
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: OrganizerProfile::class)]
    #[ORM\JoinColumn(name: 'id_profil_organisateur', referencedColumnName: 'id', nullable: false)]
    private ?OrganizerProfile $organizerProfile = null;

    #[ORM\ManyToOne(targetEntity: SubscriptionPlan::class)]
    #[ORM\JoinColumn(name: 'id_plan', referencedColumnName: 'id', nullable: false)]
    private ?SubscriptionPlan $plan = null;

    #[ORM\Column(name: 'statut', type: Types::STRING, length: 20, options: ['default' => self::STATUS_PENDING], columnDefinition: "subscription_status_enum NOT NULL DEFAULT 'pending'")]
    private string $statut = self::STATUS_PENDING;

    #[ORM\Column(name: 'mois_prepayes_restants', type: Types::INTEGER, options: ['default' => 0])]
    private int $moisPrepayesRestants = 0;

    #[ORM\Column(name: 'commence_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $commenceLe = null;

    #[ORM\Column(name: 'debut_periode_courante', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $debutPeriodeCourante = null;

    #[ORM\Column(name: 'fin_periode_courante', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $finPeriodeCourante = null;

    #[ORM\Column(name: 'renouvellement_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $renouvellementLe = null;

    #[ORM\Column(name: 'annuler_a_la_fin_periode', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $annulerALaFinPeriode = false;

    #[ORM\Column(name: 'annule_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $annuleLe = null;

    #[ORM\Column(name: 'mis_en_pause_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $misEnPauseLe = null;

    #[ORM\Column(name: 'repris_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $reprisLe = null;

    #[ORM\Column(name: 'metadonnees', type: Types::JSON, nullable: true)]
    private ?array $metadonnees = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    public function __construct()
    {
        $now = new \DateTime();
        $this->creeLe = $now;
        $this->modifieLe = $now;
        $this->commenceLe = $now;
        $this->debutPeriodeCourante = $now;
    }

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTime();
        $this->creeLe ??= $now;
        $this->modifieLe = $now;
        $this->commenceLe ??= $now;
        $this->debutPeriodeCourante ??= $now;
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->modifieLe = new \DateTime();
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

    public function getPlan(): ?SubscriptionPlan
    {
        return $this->plan;
    }

    public function setPlan(?SubscriptionPlan $plan): static
    {
        $this->plan = $plan;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getMoisPrepayesRestants(): int
    {
        return $this->moisPrepayesRestants;
    }

    public function setMoisPrepayesRestants(int $moisPrepayesRestants): static
    {
        $this->moisPrepayesRestants = $moisPrepayesRestants;
        return $this;
    }

    public function getCommenceLe(): ?\DateTimeInterface
    {
        return $this->commenceLe;
    }

    public function setCommenceLe(?\DateTimeInterface $commenceLe): static
    {
        $this->commenceLe = $commenceLe;
        return $this;
    }

    public function getDebutPeriodeCourante(): ?\DateTimeInterface
    {
        return $this->debutPeriodeCourante;
    }

    public function setDebutPeriodeCourante(?\DateTimeInterface $debutPeriodeCourante): static
    {
        $this->debutPeriodeCourante = $debutPeriodeCourante;
        return $this;
    }

    public function getFinPeriodeCourante(): ?\DateTimeInterface
    {
        return $this->finPeriodeCourante;
    }

    public function setFinPeriodeCourante(?\DateTimeInterface $finPeriodeCourante): static
    {
        $this->finPeriodeCourante = $finPeriodeCourante;
        return $this;
    }

    public function getRenouvellementLe(): ?\DateTimeInterface
    {
        return $this->renouvellementLe;
    }

    public function setRenouvellementLe(?\DateTimeInterface $renouvellementLe): static
    {
        $this->renouvellementLe = $renouvellementLe;
        return $this;
    }

    public function isAnnulerALaFinPeriode(): bool
    {
        return $this->annulerALaFinPeriode;
    }

    public function setAnnulerALaFinPeriode(bool $annulerALaFinPeriode): static
    {
        $this->annulerALaFinPeriode = $annulerALaFinPeriode;
        return $this;
    }

    public function getAnnuleLe(): ?\DateTimeInterface
    {
        return $this->annuleLe;
    }

    public function setAnnuleLe(?\DateTimeInterface $annuleLe): static
    {
        $this->annuleLe = $annuleLe;
        return $this;
    }

    public function getMisEnPauseLe(): ?\DateTimeInterface
    {
        return $this->misEnPauseLe;
    }

    public function setMisEnPauseLe(?\DateTimeInterface $misEnPauseLe): static
    {
        $this->misEnPauseLe = $misEnPauseLe;
        return $this;
    }

    public function getReprisLe(): ?\DateTimeInterface
    {
        return $this->reprisLe;
    }

    public function setReprisLe(?\DateTimeInterface $reprisLe): static
    {
        $this->reprisLe = $reprisLe;
        return $this;
    }

    public function getMetadonnees(): ?array
    {
        return $this->metadonnees;
    }

    public function setMetadonnees(?array $metadonnees): static
    {
        $this->metadonnees = $metadonnees;
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

