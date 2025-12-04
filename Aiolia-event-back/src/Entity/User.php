<?php

namespace App\Entity;

use App\Enum\Role as UserRoleEnum;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'utilisateurs', schema: 'aiolia', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_utilisateurs_nom_complet', columns: ['prenom', 'nom']),
    new ORM\UniqueConstraint(name: 'uq_utilisateurs_connexion', columns: ['identifiant_connexion', 'methode_connexion'])
])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['prenom', 'nom'], message: 'Un compte existe déjà avec ce prénom et ce nom')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const STATUS_DELETED = -1;
    public const STATUS_PENDING = 0;
    public const STATUS_ACTIVE = 1;

    public const AUTH_PROVIDER_PASSWORD = 'password';
    public const AUTH_PROVIDER_GOOGLE = 'google';
    public const AUTH_PROVIDER_FACEBOOK = 'facebook';

    public const TWO_FACTOR_TOTP = 'totp';
    public const TWO_FACTOR_SMS = 'sms';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(length: 255, columnDefinition: 'CITEXT')]
    private string $email;

    #[ORM\Column(name: 'identifiant_connexion', type: Types::STRING, length: 255)]
    private string $identifiantConnexion;

    #[ORM\Column(name: 'methode_connexion', type: Types::STRING, length: 20, options: ['default' => self::AUTH_PROVIDER_PASSWORD])]
    private string $methodeConnexion = self::AUTH_PROVIDER_PASSWORD;

    #[ORM\Column(name: 'hash_mot_de_passe', type: Types::TEXT)]
    private string $hashMotDePasse;

    #[ORM\Column(name: 'prenom', type: Types::TEXT)]
    private string $prenom;

    #[ORM\Column(name: 'nom', type: Types::TEXT, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(name: 'telephone', type: Types::TEXT, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(name: 'code_pays', type: Types::STRING, length: 2, nullable: true, options: ['fixed' => true])]
    private ?string $codePays = null;

    #[ORM\Column(name: 'code_langue', type: Types::STRING, length: 10, options: ['default' => 'fr-FR'])]
    private string $codeLangue = 'fr-FR';

    #[ORM\Column(name: 'fuseau_horaire', type: Types::TEXT, options: ['default' => 'Indian/Antananarivo'])]
    private string $fuseauHoraire = 'Indian/Antananarivo';

    #[ORM\Column(name: 'url_avatar', type: Types::TEXT, nullable: true)]
    private ?string $urlAvatar = null;

    #[ORM\Column(name: 'role', type: Types::STRING, length: 20, options: ['default' => UserRoleEnum::USER], columnDefinition: "user_role_enum NOT NULL DEFAULT 'user'")]
    private string $role = UserRoleEnum::USER;

    #[ORM\Column(name: 'statut', type: Types::SMALLINT, options: ['default' => self::STATUS_PENDING])]
    private int|string $statut = self::STATUS_PENDING;

    #[ORM\Column(name: 'fournisseur_auth', type: Types::STRING, length: 20, options: ['default' => self::AUTH_PROVIDER_PASSWORD])]
    private string $fournisseurAuth = self::AUTH_PROVIDER_PASSWORD;

    #[ORM\Column(name: 'id_fournisseur_oauth', type: Types::TEXT, nullable: true)]
    private ?string $idFournisseurOauth = null;

    #[ORM\Column(name: 'email_verifie', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $emailVerifie = false;

    #[ORM\Column(name: 'telephone_verifie', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $telephoneVerifie = false;

    #[ORM\Column(name: 'type_double_authentification', type: Types::STRING, nullable: true)]
    private ?string $typeDoubleAuthentification = null;

    #[ORM\Column(name: 'termes_acceptes_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $termesAcceptesLe = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    #[ORM\Column(name: 'derniere_connexion_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $derniereConnexionLe = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->creeLe = $now;
        $this->modifieLe = $now;
        $this->identifiantConnexion = '';
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        if ($this->identifiantConnexion === '') {
            $this->setIdentifiantConnexion($email);
        }

        return $this;
    }

    public function getIdentifiantConnexion(): string
    {
        return $this->identifiantConnexion;
    }

    public function setIdentifiantConnexion(string $identifiantConnexion): static
    {
        $this->identifiantConnexion = trim($identifiantConnexion);

        return $this;
    }

    public function getMethodeConnexion(): string
    {
        return $this->methodeConnexion;
    }

    public function setMethodeConnexion(string $methodeConnexion): static
    {
        $this->methodeConnexion = strtolower(trim($methodeConnexion)) ?: self::AUTH_PROVIDER_PASSWORD;

        return $this;
    }

    public function getHashMotDePasse(): string
    {
        return $this->hashMotDePasse;
    }

    public function setHashMotDePasse(string $hashMotDePasse): static
    {
        $this->hashMotDePasse = $hashMotDePasse;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->hashMotDePasse;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getNomComplet(): string
    {
        return trim($this->prenom . ' ' . ($this->nom ?? ''));
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getCodePays(): ?string
    {
        return $this->codePays;
    }

    public function setCodePays(?string $codePays): static
    {
        $this->codePays = $codePays;

        return $this;
    }

    public function getCodeLangue(): string
    {
        return $this->codeLangue;
    }

    public function setCodeLangue(string $codeLangue): static
    {
        $this->codeLangue = $codeLangue;

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

    public function getUrlAvatar(): ?string
    {
        return $this->urlAvatar;
    }

    public function setUrlAvatar(?string $urlAvatar): static
    {
        $this->urlAvatar = $urlAvatar;

        return $this;
    }

    public function getUrlPhoto(): string
    {
        if (!empty($this->urlAvatar)) {
            return $this->urlAvatar;
        }

        $initials = '';

        if (!empty($this->prenom)) {
            $initials .= mb_substr($this->prenom, 0, 1);
        }

        if (!empty($this->nom)) {
            $initials .= mb_substr($this->nom, 0, 1);
        }

        if ($initials === '' && !empty($this->email)) {
            $initials = mb_substr($this->email, 0, 1);
        }

        return mb_strtoupper($initials);
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $normalized = UserRoleEnum::normalize($role);
        UserRoleEnum::assertValid($normalized);
        $this->role = $normalized;

        return $this;
    }

    public function getStatut(): int
    {
        return (int) $this->statut;
    }

    public function setStatut(int|string $statut): static
    {
        if (is_string($statut) && !is_numeric($statut)) {
            $statut = self::accountStatusToDatabaseStatus($statut);
        }

        $this->statut = (int) $statut;
        return $this;
    }

    public function getStatutCompte(): string
    {
        return self::databaseStatusToAccountStatus($this->statut);
    }

    public function setStatutCompte(string $statutCompte): static
    {
        $this->statut = self::accountStatusToDatabaseStatus($statutCompte);

        return $this;
    }

    public function estActif(): bool
    {
        return $this->getStatut() === self::STATUS_ACTIVE;
    }

    public static function accountStatusToDatabaseStatus(string $accountStatus): int
    {
        $normalized = strtolower(trim($accountStatus));

        return match ($normalized) {
            'active' => self::STATUS_ACTIVE,
            'pending', 'pending_validation', 'inactive', 'suspended' => self::STATUS_PENDING,
            'rejected', 'deleted' => self::STATUS_DELETED,
            default => self::STATUS_PENDING,
        };
    }

    public static function databaseStatusToAccountStatus(int|string $status): string
    {
        if (is_string($status) && !is_numeric($status)) {
            $normalized = strtolower(trim($status));
            return match ($normalized) {
                'active' => 'active',
                'rejected', 'deleted' => 'rejected',
                default => 'pending_validation',
            };
        }

        $value = (int) $status;

        return match ($value) {
            self::STATUS_ACTIVE => 'active',
            self::STATUS_DELETED => 'rejected',
            default => 'pending_validation',
        };
    }

    public function getFournisseurAuth(): string
    {
        return $this->fournisseurAuth;
    }

    public function setFournisseurAuth(string $fournisseurAuth): static
    {
        $this->fournisseurAuth = $fournisseurAuth;

        return $this;
    }

    public function getIdFournisseurOauth(): ?string
    {
        return $this->idFournisseurOauth;
    }

    public function setIdFournisseurOauth(?string $idFournisseurOauth): static
    {
        $this->idFournisseurOauth = $idFournisseurOauth;

        return $this;
    }

    public function estEmailVerifie(): bool
    {
        return $this->emailVerifie;
    }

    /** @deprecated Utiliser estEmailVerifie() */
    public function isEmailVerifie(): bool
    {
        return $this->estEmailVerifie();
    }

    public function setEmailVerifie(bool $emailVerifie): static
    {
        $this->emailVerifie = $emailVerifie;

        return $this;
    }

    public function estTelephoneVerifie(): bool
    {
        return $this->telephoneVerifie;
    }

    /** @deprecated Utiliser estTelephoneVerifie() */
    public function isTelephoneVerifie(): bool
    {
        return $this->estTelephoneVerifie();
    }

    public function setTelephoneVerifie(bool $telephoneVerifie): static
    {
        $this->telephoneVerifie = $telephoneVerifie;

        return $this;
    }

    public function getTypeDoubleAuthentification(): ?string
    {
        return $this->typeDoubleAuthentification;
    }

    public function setTypeDoubleAuthentification(?string $typeDoubleAuthentification): static
    {
        $this->typeDoubleAuthentification = $typeDoubleAuthentification;

        return $this;
    }

    public function getTermesAcceptesLe(): ?\DateTimeInterface
    {
        return $this->termesAcceptesLe;
    }

    public function setTermesAcceptesLe(?\DateTimeInterface $termesAcceptesLe): static
    {
        $this->termesAcceptesLe = $termesAcceptesLe;

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

    public function getDerniereConnexionLe(): ?\DateTimeInterface
    {
        return $this->derniereConnexionLe;
    }

    public function setDerniereConnexionLe(?\DateTimeInterface $derniereConnexionLe): static
    {
        $this->derniereConnexionLe = $derniereConnexionLe;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        if (!UserRoleEnum::isValid($this->role)) {
            return ['ROLE_USER'];
        }

        return array_values(array_unique(UserRoleEnum::toSecurityRoles($this->role)));
    }

    public function eraseCredentials(): void
    {
        // Rien à effacer pour l'instant
    }

    // ============================================================
    // Méthodes de compatibilité (dépréciées - utiliser les noms français)
    // ============================================================
    
    /** @deprecated Utiliser getPrenom() */
    public function getFirstName(): string
    {
        return $this->prenom;
    }

    /** @deprecated Utiliser setPrenom() */
    public function setFirstName(string $prenom): static
    {
        return $this->setPrenom($prenom);
    }

    /** @deprecated Utiliser getNom() */
    public function getLastName(): ?string
    {
        return $this->nom;
    }

    /** @deprecated Utiliser setNom() */
    public function setLastName(?string $nom): static
    {
        return $this->setNom($nom);
    }

    /** @deprecated Utiliser getNomComplet() */
    public function getFullName(): string
    {
        return $this->getNomComplet();
    }

    /** @deprecated Utiliser getTelephone() */
    public function getPhone(): ?string
    {
        return $this->telephone;
    }

    /** @deprecated Utiliser setTelephone() */
    public function setPhone(?string $telephone): static
    {
        return $this->setTelephone($telephone);
    }

    /** @deprecated Utiliser getUrlAvatar() */
    public function getAvatarUrl(): ?string
    {
        return $this->urlAvatar;
    }

    /** @deprecated Utiliser setUrlAvatar() */
    public function setAvatarUrl(?string $urlAvatar): static
    {
        return $this->setUrlAvatar($urlAvatar);
    }

    /** @deprecated Utiliser getUrlPhoto() */
    public function getPhotoUrl(): string
    {
        return $this->getUrlPhoto();
    }

    /** @deprecated Utiliser getCreeLe() */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->creeLe;
    }

    /** @deprecated Utiliser setCreeLe() */
    public function setCreatedAt(?\DateTimeInterface $creeLe): static
    {
        return $this->setCreeLe($creeLe);
    }

    /** @deprecated Utiliser getModifieLe() */
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->modifieLe;
    }

    /** @deprecated Utiliser setModifieLe() */
    public function setUpdatedAt(?\DateTimeInterface $modifieLe): static
    {
        return $this->setModifieLe($modifieLe);
    }

    /** @deprecated Utiliser getDerniereConnexionLe() */
    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->derniereConnexionLe;
    }

    /** @deprecated Utiliser setDerniereConnexionLe() */
    public function setLastLoginAt(?\DateTimeInterface $derniereConnexionLe): static
    {
        return $this->setDerniereConnexionLe($derniereConnexionLe);
    }

    /** @deprecated Utiliser getStatut() */
    public function getStatus(): int
    {
        return (int) $this->statut;
    }

    /** @deprecated Utiliser setStatut() */
    public function setStatus(int|string $statut): static
    {
        return $this->setStatut($statut);
    }

    /** @deprecated Utiliser getStatutCompte() */
    public function getAccountStatus(): string
    {
        return $this->getStatutCompte();
    }

    /** @deprecated Utiliser setStatutCompte() */
    public function setAccountStatus(string $statutCompte): static
    {
        return $this->setStatutCompte($statutCompte);
    }

    /** @deprecated Utiliser getHashMotDePasse() */
    public function getPasswordHash(): string
    {
        return $this->hashMotDePasse;
    }

    /** @deprecated Utiliser setHashMotDePasse() */
    public function setPasswordHash(string $hashMotDePasse): static
    {
        return $this->setHashMotDePasse($hashMotDePasse);
    }

    /** @deprecated Utiliser getIdentifiantConnexion() */
    public function getLoginIdentifier(): string
    {
        return $this->identifiantConnexion;
    }

    /** @deprecated Utiliser setIdentifiantConnexion() */
    public function setLoginIdentifier(string $identifiantConnexion): static
    {
        return $this->setIdentifiantConnexion($identifiantConnexion);
    }

    /** @deprecated Utiliser getMethodeConnexion() */
    public function getLoginMethod(): string
    {
        return $this->methodeConnexion;
    }

    /** @deprecated Utiliser setMethodeConnexion() */
    public function setLoginMethod(string $methodeConnexion): static
    {
        return $this->setMethodeConnexion($methodeConnexion);
    }
}

