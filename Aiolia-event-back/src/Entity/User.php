<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $passwordHash = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(length: 20)]
    private string $role = 'user';

    #[ORM\Column]
    private bool $emailVerified = false;

    #[ORM\Column(length: 20)]
    private string $oauthProvider = 'local';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $oauthProviderId = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(length: 20)]
    private string $accountStatus = 'active'; // active, pending_validation, rejected, suspended

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): static
    {
        $this->photoUrl = $photoUrl;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): static
    {
        $this->emailVerified = $emailVerified;
        return $this;
    }

    public function getOauthProvider(): string
    {
        return $this->oauthProvider;
    }

    public function setOauthProvider(string $oauthProvider): static
    {
        $this->oauthProvider = $oauthProvider;
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    // Implémentation de UserInterface
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        
        if ($this->role === 'admin') {
            $roles[] = 'ROLE_ADMIN';
        } elseif ($this->role === 'organizer') {
            $roles[] = 'ROLE_ORGANIZER';
        } elseif ($this->role === 'co_organizer') {
            $roles[] = 'ROLE_CO_ORGANIZER';
        }
        
        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // Si vous stockez des données sensibles temporaires sur l'utilisateur, effacez-les ici
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
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

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getAccountStatus(): string
    {
        return $this->accountStatus;
    }

    public function setAccountStatus(string $accountStatus): static
    {
        $this->accountStatus = $accountStatus;
        return $this;
    }

    public function isPendingValidation(): bool
    {
        return $this->accountStatus === 'pending_validation';
    }

    public function isRejected(): bool
    {
        return $this->accountStatus === 'rejected';
    }

    public function isSuspended(): bool
    {
        return $this->accountStatus === 'suspended';
    }
}

