<?php

namespace App\Service\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthTokenService $authTokenService
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function register(array $payload, ?string $userAgent = null, ?string $ipAddress = null): array
    {
        $email = $this->normalizeEmail($payload['email'] ?? null);
        $password = (string) ($payload['password'] ?? '');
        $firstName = trim((string) ($payload['first_name'] ?? ''));
        $lastName = trim((string) ($payload['last_name'] ?? ''));
        $phone = isset($payload['phone']) ? trim((string) $payload['phone']) : null;

        if ('' === $email || '' === $password) {
            throw new \InvalidArgumentException('Adresse email et mot de passe requis.');
        }

        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit comporter au moins 8 caractères.');
        }

        if ('' === $firstName || '' === $lastName) {
            throw new \InvalidArgumentException('Le prénom et le nom sont requis.');
        }

        if (null !== $this->userRepository->findByEmail($email)) {
            throw new \InvalidArgumentException('Cette adresse email est déjà utilisée.');
        }

        $user = (new User())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setIsActive(true)
            ->setRoles($payload['roles'] ?? ['ROLE_USER'])
            ->setUpdatedAt(new \DateTimeImmutable());

        if (null !== $phone && '' !== $phone) {
            $user->setPhone($phone);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->buildAuthPayload($user, $userAgent, $ipAddress);
    }

    /**
     * @return array<string, mixed>
     */
    public function login(string $email, string $password, ?string $userAgent = null, ?string $ipAddress = null): array
    {
        $email = $this->normalizeEmail($email);

        $user = $this->userRepository->findByEmail($email);
        if (null === $user) {
            throw new \InvalidArgumentException('Identifiants invalides.');
        }

        if (!$user->isActive()) {
            throw new \InvalidArgumentException('Votre compte est inactif. Contactez le support.');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new \InvalidArgumentException('Identifiants invalides.');
        }

        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->buildAuthPayload($user, $userAgent, $ipAddress);
    }

    /**
     * Login simplifié via un fournisseur OAuth externe (Google, Facebook...).
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function loginWithProvider(array $payload, ?string $userAgent = null, ?string $ipAddress = null): array
    {
        $provider = strtolower(trim((string) ($payload['provider'] ?? '')));
        $providerId = trim((string) ($payload['provider_id'] ?? ''));

        if ('' === $provider || '' === $providerId) {
            throw new \InvalidArgumentException('Les informations du fournisseur OAuth sont manquantes.');
        }

        $email = $this->normalizeEmail($payload['email'] ?? ($providerId . '@' . $provider . '.oauth'));
        $firstName = trim((string) ($payload['first_name'] ?? 'Invité'));
        $lastName = trim((string) ($payload['last_name'] ?? ucfirst($provider)));

        $user = $this->userRepository->findByEmail($email);

        if (null === $user) {
            $user = (new User())
                ->setEmail($email)
                ->setFirstName($firstName ?: 'Invité')
                ->setLastName($lastName ?: ucfirst($provider))
                ->setIsActive(true)
                ->setRoles(['ROLE_USER'])
                ->setUpdatedAt(new \DateTimeImmutable());

            $randomPassword = bin2hex(random_bytes(16));
            $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
            $user->setPassword($hashedPassword);

            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                throw new \InvalidArgumentException((string) $errors);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        if (!$user->isActive()) {
            throw new \InvalidArgumentException('Votre compte est inactif. Contactez le support.');
        }

        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->buildAuthPayload($user, $userAgent, $ipAddress, [
            'provider' => $provider,
            'provider_id' => $providerId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshTokens(string $refreshToken, ?string $userAgent = null, ?string $ipAddress = null): array
    {
        try {
            $rotated = $this->authTokenService->rotateRefreshToken($refreshToken, $userAgent, $ipAddress);
        } catch (\RuntimeException $exception) {
            throw new \InvalidArgumentException($exception->getMessage(), previous: $exception);
        }

        return [
            'access_token' => $rotated['access_token'],
            'refresh_token' => $rotated['refresh_token'],
            'refresh_token_expires_at' => $rotated['expires_at']->format(DATE_ATOM),
            'token_type' => 'Bearer',
        ];
    }

    public function logout(User $user, ?string $refreshToken = null): void
    {
        if (null !== $refreshToken) {
            $this->authTokenService->revokeRefreshToken($refreshToken);
        } else {
            $this->authTokenService->revokeAllForUser($user);
        }
    }

    public function logoutByEmail(string $email, ?string $refreshToken = null): void
    {
        $user = $this->userRepository->findByEmail($this->normalizeEmail($email));

        if (null === $user) {
            return;
        }

        $this->logout($user, $refreshToken);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function updateProfile(User $user, array $data): array
    {
        $hasUpdates = false;

        if (array_key_exists('email', $data)) {
            $newEmail = $this->normalizeEmail($data['email']);
            if ('' === $newEmail) {
                throw new \InvalidArgumentException('L\'email ne peut pas être vide.');
            }

            if ($newEmail !== $user->getEmail()) {
                if (null !== $this->userRepository->findByEmail($newEmail)) {
                    throw new \InvalidArgumentException('Cette adresse email est déjà utilisée.');
                }
                $user->setEmail($newEmail);
                $hasUpdates = true;
            }
        }

        if (array_key_exists('first_name', $data)) {
            $firstName = trim((string) $data['first_name']);
            if ('' === $firstName) {
                throw new \InvalidArgumentException('Le prénom est obligatoire.');
            }
            $user->setFirstName($firstName);
            $hasUpdates = true;
        }

        if (array_key_exists('last_name', $data)) {
            $lastName = trim((string) $data['last_name']);
            if ('' === $lastName) {
                throw new \InvalidArgumentException('Le nom est obligatoire.');
            }
            $user->setLastName($lastName);
            $hasUpdates = true;
        }

        if (array_key_exists('phone', $data)) {
            $phone = $data['phone'];
            $user->setPhone(null !== $phone ? trim((string) $phone) : null);
            $hasUpdates = true;
        }

        if (array_key_exists('password', $data)) {
            $newPassword = (string) $data['password'];
            if ('' !== $newPassword) {
                if (strlen($newPassword) < 8) {
                    throw new \InvalidArgumentException('Le mot de passe doit comporter au moins 8 caractères.');
                }
                $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $hasUpdates = true;
            }
        }

        if (!$hasUpdates) {
            return $this->serializeUser($user);
        }

        $user->setUpdatedAt(new \DateTimeImmutable());

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->flush();

        return $this->serializeUser($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeUser(User $user): array
    {
        return $this->normalizeUser($user);
    }

    private function normalizeEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAuthPayload(User $user, ?string $userAgent, ?string $ipAddress, array $extra = []): array
    {
        $refreshToken = $this->authTokenService->createRefreshToken($user, $userAgent, $ipAddress);
        $accessToken = $this->authTokenService->createAccessToken($user);

        return [
            'user' => $this->normalizeUser($user),
            'tokens' => [
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => $refreshToken->getToken(),
                'refresh_token_expires_at' => $refreshToken->getExpiresAt()->format(DATE_ATOM),
            ] + $extra,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'full_name' => $user->getFullName(),
            'roles' => $user->getRoles(),
            'loyalty_points' => $user->getLoyaltyPoints(),
            'is_active' => $user->isActive(),
        ];
    }
}

