<?php

namespace App\Service\Security;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthTokenService
{
    private const DEFAULT_REFRESH_TTL = 604800; // 7 jours

    public function __construct(
        private readonly JWTTokenManagerInterface $jwtTokenManager,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function createAccessToken(UserInterface $user): string
    {
        return $this->jwtTokenManager->create($user);
    }

    public function createRefreshToken(User $user, ?string $userAgent = null, ?string $ipAddress = null, ?int $ttl = null): RefreshToken
    {
        $token = bin2hex(random_bytes(64));
        $now = new \DateTimeImmutable();
        $expiresAt = $now->modify(sprintf('+%d seconds', $ttl ?? self::DEFAULT_REFRESH_TTL));

        $refreshToken = (new RefreshToken())
            ->setUser($user)
            ->setToken($token)
            ->setIssuedAt($now)
            ->setExpiresAt($expiresAt)
            ->setUserAgent($userAgent)
            ->setIpAddress($ipAddress);

        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return $refreshToken;
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_at: \DateTimeImmutable}
     */
    public function rotateRefreshToken(string $token, ?string $userAgent = null, ?string $ipAddress = null): array
    {
        $refreshToken = $this->refreshTokenRepository->findValidToken($token);

        if (null === $refreshToken || $refreshToken->isRevoked() || $refreshToken->isExpired()) {
            throw new \RuntimeException('Refresh token invalide ou expiré.');
        }

        $refreshToken->revoke();
        $this->entityManager->flush();

        $user = $refreshToken->getUser();
        $accessToken = $this->createAccessToken($user);
        $newRefreshToken = $this->createRefreshToken($user, $userAgent, $ipAddress);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken->getToken(),
            'expires_at' => $newRefreshToken->getExpiresAt(),
        ];
    }

    public function revokeRefreshToken(string $token): void
    {
        $refreshToken = $this->refreshTokenRepository->findOneBy(['token' => $token]);

        if (null === $refreshToken) {
            return;
        }

        $refreshToken->revoke();
        $this->entityManager->flush();
    }

    public function revokeAllForUser(User $user): void
    {
        $this->refreshTokenRepository->revokeAllForUser($user);
    }
}
