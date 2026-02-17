<?php

namespace App\Controller;

use App\Dto\RefreshTokenRequestDto;
use App\Entity\RefreshToken;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Service\AuthService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/token')]
class TokenController extends AbstractController
{
    public function __construct(
        private AuthService $authService,
    ) {}

    #[Route('/refresh', methods: ['POST'])]
    public function refresh(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // Read refresh token from cookie
        $refreshTokenString = $request->cookies->get('refresh_token');

        if (!$refreshTokenString) {
            return $this->json(['error' => 'Missing refresh_token'], Response::HTTP_BAD_REQUEST);
        }

        $refreshToken = $this->authService->validateRefreshToken($refreshTokenString, $user);

        if (!$refreshToken) {
            return $this->json(['error' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
        }

        // Generate new tokens
        $tokens = $this->authService->refreshTokens($user);

        $response = $this->json([
            'message' => 'Token refreshed successfully',
        ]);

        // Set new JWT token in HTTP-only cookie
        $response->headers->setCookie(
            Cookie::create('jwt_token')
                ->withValue($tokens['accessToken'])
                ->withExpires(new \DateTimeImmutable('+1 hour'))
                ->withPath('/')
                ->withSecure(false) // Set to true in production with HTTPS
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX)
        );

        // Set new refresh token in HTTP-only cookie
        $response->headers->setCookie(
            Cookie::create('refresh_token')
                ->withValue($tokens['refreshToken']->getToken())
                ->withExpires(new \DateTimeImmutable('+30 days'))
                ->withPath('/')
                ->withSecure(false) // Set to true in production with HTTPS
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX)
        );

        return $response;
    }
}
