<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\RefreshToken;
use App\Repository\RefreshTokenRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

class AuthService
{
    public function __construct(
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly JWTTokenManagerInterface $jwtTokenManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Generate JWT access token for a user
     * 
     * @param User $user
     * @return string
     */
    public function generateAccessToken(User $user): string
    {
        return $this->jwtTokenManager->create($user);
    }

    /**
     * Create a new refresh token for a user (revokes old ones)
     * 
     * @param User $user
     * @return RefreshToken
     */
    public function createRefreshToken(User $user): RefreshToken
    {
        // Revoke old refresh tokens
        $this->refreshTokenRepository->revokeByUser($user);
        
        $refreshToken = new RefreshToken();
        $refreshToken->setUser($user);
        $refreshToken->setToken(bin2hex(random_bytes(32)));
        $refreshToken->setExpiresAt(new \DateTimeImmutable('+30 days'));
        
        $this->refreshTokenRepository->save($refreshToken, true);

        return $refreshToken;
    }

    /**
     * Validate and get a refresh token
     * 
     * @param string $tokenString
     * @param User $user
     * @return RefreshToken|null
     */
    public function validateRefreshToken(string $tokenString, User $user): ?RefreshToken
    {
        $refreshToken = $this->refreshTokenRepository->findValidByToken($tokenString);

        if (!$refreshToken || $refreshToken->getUser()->getId() !== $user->getId()) {
            return null;
        }

        return $refreshToken;
    }

    /**
     * Revoke all refresh tokens for a user
     * 
     * @param User $user
     * @return void
     */
    public function revokeUserTokens(User $user): void
    {
        $this->refreshTokenRepository->revokeByUser($user);
    }

    /**
     * Complete login flow: generate access token and refresh token
     * 
     * @param User $user
     * @return array ['accessToken' => string, 'refreshToken' => RefreshToken]
     */
    public function login(User $user): array
    {
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->createRefreshToken($user);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        ];
    }

    /**
     * Refresh tokens: generate new access token and optionally rotate refresh token
     * 
     * @param User $user
     * @return array ['accessToken' => string, 'refreshToken' => RefreshToken]
     */
    public function refreshTokens(User $user): array
    {
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->createRefreshToken($user);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        ];
    }
}
