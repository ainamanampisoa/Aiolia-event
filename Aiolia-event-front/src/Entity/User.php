<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users', schema: 'aiolia')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(name: 'login_identifier', type: 'string', length: 255)]
    private ?string $loginIdentifier = null;

    #[ORM\Column(name: 'login_method', type: 'string', length: 50)]
    private string $loginMethod = 'password';

    #[ORM\Column(name: 'password_hash', type: 'string', length: 255, nullable: true)]
    private ?string $passwordHash = null;

    #[ORM\Column(name: 'first_name', type: 'string', length: 100)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', type: 'string', length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'phone', type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(name: 'country_code', type: 'string', length: 2, nullable: true)]
    private ?string $countryCode = null;

    #[ORM\Column(name: 'language_code', type: 'string', length: 10)]
    private string $languageCode = 'fr-FR';

    #[ORM\Column(name: 'timezone', type: 'string', length: 150)]
    private string $timezone = 'Indian/Antananarivo';

    #[ORM\Column(name: 'avatar_url', type: 'text', nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\Column(name: 'role', type: 'string', length: 20)]
    private string $role = 'user';

    public const STATUS_SUSPENDED = -1;
    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;

    #[ORM\Column(name: 'status', type: 'smallint')]
    private int $status = self::STATUS_INACTIVE;

    #[ORM\Column(name: 'auth_provider', type: 'string', length: 50)]
    private string $authProvider = 'password';

    #[ORM\Column(name: 'oauth_provider_id', type: 'string', length: 255, nullable: true)]
    private ?string $oauthProviderId = null;

    #[ORM\Column(name: 'is_email_verified', type: 'boolean')]
    private bool $isEmailVerified = false;

    #[ORM\Column(name: 'is_phone_verified', type: 'boolean')]
    private bool $isPhoneVerified = false;

    #[ORM\Column(name: 'two_factor_type', type: 'string', length: 100, nullable: true)]
    private ?string $twoFactorType = null;

    #[ORM\Column(name: 'accepted_terms_at', type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $acceptedTermsAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'last_login_at', type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = strtolower($email);
        return $this;
    }

    public function getLoginIdentifier(): ?string
    {
        return $this->loginIdentifier;
    }

    public function setLoginIdentifier(string $loginIdentifier): self
    {
        $this->loginIdentifier = $loginIdentifier;
        return $this;
    }

    public function getLoginMethod(): string
    {
        return $this->loginMethod;
    }

    public function setLoginMethod(string $loginMethod): self
    {
        $this->loginMethod = $loginMethod;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }

    public function setPassword(string $hashedPassword): self
    {
        $this->passwordHash = $hashedPassword;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): self
    {
        $this->languageCode = $languageCode;
        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int|string $status
     */
    public function setStatus(int|string $status): self
    {
        if (is_string($status)) {
            $status = match (strtolower($status)) {
                'active', 'actif' => self::STATUS_ACTIVE,
                'inactive', 'inactif', 'pending', 'en_attente' => self::STATUS_INACTIVE,
                'suspended', 'suspendu', 'blocked', 'bloque' => self::STATUS_SUSPENDED,
                default => self::STATUS_INACTIVE,
            };
        }

        if (!in_array($status, [self::STATUS_SUSPENDED, self::STATUS_INACTIVE, self::STATUS_ACTIVE], true)) {
            throw new \InvalidArgumentException(sprintf('Statut utilisateur invalide : %s', (string) $status));
        }

        $this->status = $status;
        return $this;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'active',
            self::STATUS_SUSPENDED => 'suspended',
            default => 'inactive',
        };
    }

    public function getAuthProvider(): string
    {
        return $this->authProvider;
    }

    public function setAuthProvider(string $authProvider): self
    {
        $this->authProvider = $authProvider;
        return $this;
    }

    public function getOauthProviderId(): ?string
    {
        return $this->oauthProviderId;
    }

    public function setOauthProviderId(?string $oauthProviderId): self
    {
        $this->oauthProviderId = $oauthProviderId;
        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->isEmailVerified;
    }

    public function setIsEmailVerified(bool $isEmailVerified): self
    {
        $this->isEmailVerified = $isEmailVerified;
        return $this;
    }

    public function isPhoneVerified(): bool
    {
        return $this->isPhoneVerified;
    }

    public function setIsPhoneVerified(bool $isPhoneVerified): self
    {
        $this->isPhoneVerified = $isPhoneVerified;
        return $this;
    }

    public function getTwoFactorType(): ?string
    {
        return $this->twoFactorType;
    }

    public function setTwoFactorType(?string $twoFactorType): self
    {
        $this->twoFactorType = $twoFactorType;
        return $this;
    }

    public function getAcceptedTermsAt(): ?\DateTimeImmutable
    {
        return $this->acceptedTermsAt;
    }

    public function setAcceptedTermsAt(?\DateTimeImmutable $acceptedTermsAt): self
    {
        $this->acceptedTermsAt = $acceptedTermsAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->firstName ?? '', $this->lastName ?? ''));
    }

    public function getUserIdentifier(): string
    {
        return $this->email ?? (string) $this->loginIdentifier;
    }

    public function getRoles(): array
    {
        return match ($this->role) {
            'admin' => ['ROLE_ADMIN', 'ROLE_USER'],
            'organizer' => ['ROLE_ORGANIZER', 'ROLE_USER'],
            default => ['ROLE_USER'],
        };
    }

    public function eraseCredentials(): void
    {
        // nothing to erase
    }

    public function markAsLoggedIn(): void
    {
        $this->lastLoginAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}

