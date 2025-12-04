<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class TokenService
{
    private readonly \DateInterval $accessTokenTtl;
    private readonly \DateInterval $refreshTokenTtl;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly UserRepository $userRepository,
        private readonly string $secret,
        string $accessTokenTtl = 'PT15M',
        string $refreshTokenTtl = 'P30D'
    ) {
        $this->accessTokenTtl = new \DateInterval($accessTokenTtl);
        $this->refreshTokenTtl = new \DateInterval($refreshTokenTtl);
    }

    /**
     * Retourne un tableau contenant les deux jetons et leurs dates d'expiration.
     *
     * @return array{
     *     access_token: string,
     *     access_token_expires_at: \DateTimeImmutable,
     *     refresh_token: string,
     *     refresh_token_expires_at: \DateTimeImmutable,
     *     session_id: string
     * }
     */
    public function issueTokenPair(User $user, ?string $userAgent, ?string $ipAddress, ?string $sessionId = null, ?array $metadata = null): array
    {
        $sessionId ??= $this->generateSessionId();

        [$accessToken, $accessExpiry] = $this->createAccessToken($user, $sessionId);
        [$refreshToken, $refreshExpiry] = $this->createRefreshToken($user, $sessionId, $userAgent, $ipAddress, $metadata ?? []);

        return [
            'access_token' => $accessToken,
            'access_token_expires_at' => $accessExpiry,
            'refresh_token' => $refreshToken,
            'refresh_token_expires_at' => $refreshExpiry,
            'session_id' => $sessionId,
        ];
    }

    /**
     * Rafraîchit une paire de jetons en utilisant un refresh token valide (rotation).
     *
     * @return array{
     *     access_token: string,
     *     access_token_expires_at: \DateTimeImmutable,
     *     refresh_token: string,
     *     refresh_token_expires_at: \DateTimeImmutable,
     *     session_id: string
     * }
     */
    public function rotateRefreshToken(string $refreshToken, ?string $userAgent, ?string $ipAddress, ?array $metadata = null): array
    {
        $refreshToken = trim($refreshToken);

        if ($refreshToken === '') {
            throw new CustomUserMessageAuthenticationException('Refresh token manquant.');
        }

        $existingToken = $this->refreshTokenRepository->findOneValidByHash($this->hashToken($refreshToken));

        if (!$existingToken instanceof RefreshToken) {
            throw new CustomUserMessageAuthenticationException('Refresh token invalide ou expiré.');
        }

        $existingToken->markRevoked();

        [$accessToken, $accessExpiry] = $this->createAccessToken($existingToken->getUser(), $existingToken->getSessionId());
        [$newRefreshToken, $newRefreshExpiry, $newRefreshEntity] = $this->createRefreshTokenEntity(
            $existingToken->getUser(),
            $existingToken->getSessionId(),
            $userAgent,
            $ipAddress,
            $metadata ?? []
        );

        $existingToken->setReplacedByToken($newRefreshEntity);

        $this->entityManager->persist($existingToken);
        $this->entityManager->persist($newRefreshEntity);
        $this->entityManager->flush();

        return [
            'access_token' => $accessToken,
            'access_token_expires_at' => $accessExpiry,
            'refresh_token' => $newRefreshToken,
            'refresh_token_expires_at' => $newRefreshExpiry,
            'session_id' => $existingToken->getSessionId() ?? $newRefreshEntity->getSessionId(),
        ];
    }

    public function revokeRefreshToken(string $refreshToken, ?User $owner = null): void
    {
        $token = $this->refreshTokenRepository->findOneValidByHash($this->hashToken($refreshToken));

        if (!$token instanceof RefreshToken) {
            return;
        }

        if ($owner !== null && $token->getUser()?->getId() !== $owner->getId()) {
            throw new CustomUserMessageAuthenticationException('Le refresh token ne correspond pas à l’utilisateur connecté.');
        }

        $token->markRevoked();
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }

    public function revokeAllForUser(User $user, ?string $sessionId = null): int
    {
        return $this->refreshTokenRepository->revokeAllForUser($user, $sessionId);
    }

    /**
     * Vérifie et décode un access token signé localement.
     *
     * @return array<string, mixed>
     */
    public function decodeAccessToken(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new AuthenticationException('Structure de jeton invalide.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $headerJson = $this->base64UrlDecode($encodedHeader);
        $payloadJson = $this->base64UrlDecode($encodedPayload);
        $signature = $this->base64UrlDecode($encodedSignature);

        try {
            $header = json_decode($headerJson, true, 512, JSON_THROW_ON_ERROR);
            $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new AuthenticationException('Jeton illisible.', 0, $exception);
        }

        if (!is_array($header) || !is_array($payload)) {
            throw new AuthenticationException('Le jeton envoyé est corrompu.');
        }

        if (($header['alg'] ?? '') !== 'HS256') {
            throw new AuthenticationException('Algorithme de signature non supporté.');
        }

        $expectedSignature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret, true);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new AuthenticationException('Signature de jeton invalide.');
        }

        $now = time();

        if (($payload['nbf'] ?? 0) > $now) {
            throw new AuthenticationException('Le jeton n’est pas encore valide.');
        }

        if (($payload['exp'] ?? 0) <= $now) {
            throw new AuthenticationException('Le jeton a expiré.');
        }

        return $payload;
    }

    /**
     * Récupère l'utilisateur associé à un access token.
     */
    public function getUserFromAccessToken(string $token): User
    {
        $payload = $this->decodeAccessToken($token);
        $userId = $payload['sub'] ?? null;

        if ($userId === null) {
            throw new AuthenticationException('Jeton sans identifiant utilisateur.');
        }

        $user = $this->userRepository->find($userId);

        if (!$user instanceof User) {
            throw new AuthenticationException('Utilisateur inexistant pour ce jeton.');
        }

        return $user;
    }

    private function createAccessToken(User $user, ?string $sessionId = null): array
    {
        $now = new \DateTimeImmutable();
        $expiresAt = $now->add($this->accessTokenTtl);

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => 'aiolia-event',
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'session_id' => $sessionId,
            'iat' => $now->getTimestamp(),
            'nbf' => $now->getTimestamp(),
            'exp' => $expiresAt->getTimestamp(),
            'jti' => bin2hex(random_bytes(16)),
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret, true);
        $encodedSignature = $this->base64UrlEncode($signature);

        return [$encodedHeader . '.' . $encodedPayload . '.' . $encodedSignature, $expiresAt];
    }

    private function createRefreshToken(User $user, string $sessionId, ?string $userAgent, ?string $ipAddress, array $metadata): array
    {
        [$token, $expiresAt, $entity] = $this->createRefreshTokenEntity($user, $sessionId, $userAgent, $ipAddress, $metadata);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return [$token, $expiresAt];
    }

    /**
     * @return array{0: string, 1: \DateTimeImmutable, 2: RefreshToken}
     */
    private function createRefreshTokenEntity(User $user, string $sessionId, ?string $userAgent, ?string $ipAddress, array $metadata): array
    {
        $rawToken = $this->generateSecureToken();
        $hashedToken = $this->hashToken($rawToken);

        $issuedAt = new \DateTimeImmutable();
        $expiresAt = $issuedAt->add($this->refreshTokenTtl);

        $entity = (new RefreshToken())
            ->setUser($user)
            ->setSessionId($sessionId)
            ->setUserAgent($userAgent)
            ->setIpAddress($ipAddress)
            ->setMetadata($metadata)
            ->setIssuedAt($issuedAt)
            ->setExpiresAt($expiresAt)
            ->setTokenHash($hashedToken);

        return [$rawToken, $expiresAt, $entity];
    }

    private function generateSecureToken(int $length = 64): string
    {
        return rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
    }

    private function hashToken(string $token): string
    {
        return hash('sha512', $token . $this->secret);
    }

    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;

        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            throw new AuthenticationException('Décodage Base64URL impossible.');
        }

        return $decoded;
    }
}


