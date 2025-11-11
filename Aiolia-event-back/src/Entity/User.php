<?php

namespace App\Entity;

use App\Enum\Role as UserRoleEnum;
use App\Entity\Role;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users', schema: 'aiolia', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_users_fullname', columns: ['first_name', 'last_name'])
])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['firstName', 'lastName'], message: 'Un compte existe déjà avec ce prénom et ce nom')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_DELETED = 'deleted';

    public const AUTH_PROVIDER_PASSWORD = 'password';
    public const AUTH_PROVIDER_GOOGLE = 'google';
    public const AUTH_PROVIDER_FACEBOOK = 'facebook';

    public const TWO_FACTOR_TOTP = 'totp';
    public const TWO_FACTOR_SMS = 'sms';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(length: 255, columnDefinition: 'CITEXT NOT NULL')]
    private string $email;

    #[ORM\Column(name: 'password_hash', type: Types::TEXT)]
    private string $passwordHash;

    #[ORM\Column(name: 'first_name', type: Types::TEXT)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: Types::TEXT, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true, columnDefinition: 'phone_e164')]
    private ?string $phone = null;

    #[ORM\Column(name: 'country_code', type: Types::STRING, length: 2, nullable: true, options: ['fixed' => true])]
    private ?string $countryCode = null;

    #[ORM\Column(name: 'language_code', type: Types::STRING, length: 10, options: ['default' => 'fr-FR'])]
    private string $languageCode = 'fr-FR';

    #[ORM\Column(type: Types::TEXT, options: ['default' => 'Indian/Antananarivo'])]
    private string $timezone = 'Indian/Antananarivo';

    #[ORM\Column(name: 'avatar_url', type: Types::TEXT, nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_role_assignments', schema: 'aiolia')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'role_id', referencedColumnName: 'id')]
    private Collection $roles;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => self::STATUS_PENDING])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'auth_provider', type: Types::STRING, length: 20, options: ['default' => self::AUTH_PROVIDER_PASSWORD])]
    private string $authProvider = self::AUTH_PROVIDER_PASSWORD;

    #[ORM\Column(name: 'oauth_provider_id', type: Types::TEXT, nullable: true)]
    private ?string $oauthProviderId = null;

    #[ORM\Column(name: 'is_email_verified', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isEmailVerified = false;

    #[ORM\Column(name: 'is_phone_verified', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isPhoneVerified = false;

    #[ORM\Column(name: 'two_factor_type', type: Types::STRING, nullable: true)]
    private ?string $twoFactorType = null;

    #[ORM\Column(name: 'accepted_terms_at', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $acceptedTermsAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'last_login_at', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->roles = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt ??= $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

        return $this;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . ($this->lastName ?? ''));
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): static
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): static
    {
        $this->languageCode = $languageCode;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }

    public function getPhotoUrl(): string
    {
        if (!empty($this->avatarUrl)) {
            return $this->avatarUrl;
        }

        $initials = '';

        if (!empty($this->firstName)) {
            $initials .= mb_substr($this->firstName, 0, 1);
        }

        if (!empty($this->lastName)) {
            $initials .= mb_substr($this->lastName, 0, 1);
        }

        if ($initials === '' && !empty($this->email)) {
            $initials = mb_substr($this->email, 0, 1);
        }

        return mb_strtoupper($initials);
    }

    public function getRole(): string
    {
        foreach ($this->roles as $role) {
            return $role->getCode();
        }

        return UserRoleEnum::USER;
    }

    public function setRole(Role $role): static
    {
        foreach ($this->roles->toArray() as $existingRole) {
            if ($existingRole !== $role) {
                $this->roles->removeElement($existingRole);
                $existingRole->removeUser($this);
            }
        }

        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }

        $role->addUser($this);

        return $this;
    }

    public function addRole(Role $role): static
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
            $role->addUser($this);
        }

        return $this;
    }

    public function removeRole(Role $role): static
    {
        if ($this->roles->removeElement($role)) {
            $role->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getRoleEntities(): Collection
    {
        return $this->roles;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getAccountStatus(): string
    {
        return $this->mapStatusToAccountStatus($this->status);
    }

    public function setAccountStatus(string $accountStatus): static
    {
        $normalized = strtolower(trim($accountStatus));
        $this->status = $this->mapAccountStatusToStatus($normalized);

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    private function mapAccountStatusToStatus(string $accountStatus): string
    {
        return match ($accountStatus) {
            'active' => self::STATUS_ACTIVE,
            'suspended' => self::STATUS_SUSPENDED,
            'rejected' => self::STATUS_SUSPENDED,
            'deleted' => self::STATUS_DELETED,
            default => self::STATUS_PENDING,
        };
    }

    private function mapStatusToAccountStatus(string $status): string
    {
        return match ($status) {
            self::STATUS_ACTIVE => 'active',
            self::STATUS_SUSPENDED => 'suspended',
            self::STATUS_DELETED => 'deleted',
            default => 'pending_validation',
        };
    }

    public function getAuthProvider(): string
    {
        return $this->authProvider;
    }

    public function setAuthProvider(string $authProvider): static
    {
        $this->authProvider = $authProvider;

        return $this;
    }

    public function getOauthProviderId(): ?string
    {
        return $this->oauthProviderId;
    }

    public function setOauthProviderId(?string $oauthProviderId): static
    {
        $this->oauthProviderId = $oauthProviderId;

        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->isEmailVerified;
    }

    public function setIsEmailVerified(bool $isEmailVerified): static
    {
        $this->isEmailVerified = $isEmailVerified;

        return $this;
    }

    public function isPhoneVerified(): bool
    {
        return $this->isPhoneVerified;
    }

    public function setIsPhoneVerified(bool $isPhoneVerified): static
    {
        $this->isPhoneVerified = $isPhoneVerified;

        return $this;
    }

    public function getTwoFactorType(): ?string
    {
        return $this->twoFactorType;
    }

    public function setTwoFactorType(?string $twoFactorType): static
    {
        $this->twoFactorType = $twoFactorType;

        return $this;
    }

    public function getAcceptedTermsAt(): ?\DateTimeInterface
    {
        return $this->acceptedTermsAt;
    }

    public function setAcceptedTermsAt(?\DateTimeInterface $acceptedTermsAt): static
    {
        $this->acceptedTermsAt = $acceptedTermsAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        if ($this->roles->isEmpty()) {
            return UserRoleEnum::toSecurityRoles(UserRoleEnum::USER);
        }

        $securityRoles = [];

        foreach ($this->roles as $role) {
            $code = $role->getCode();

            if (UserRoleEnum::isValid($code)) {
                $securityRoles = array_merge($securityRoles, UserRoleEnum::toSecurityRoles($code));
            } else {
                $securityRoles[] = 'ROLE_USER';
            }
        }

        $securityRoles[] = 'ROLE_USER';

        return array_values(array_unique($securityRoles));
    }

    public function eraseCredentials(): void
    {
        // Rien à effacer pour l'instant
    }
}

