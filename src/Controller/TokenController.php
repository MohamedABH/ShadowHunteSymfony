<?php

namespace App\Controller;

use App\Dto\RefreshTokenRequestDto;
use App\Entity\RefreshToken;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/token')]
class TokenController extends AbstractController
{
    public function __construct(
        private RefreshTokenRepository $refreshTokenRepository,
        private JWTTokenManagerInterface $jwtTokenManager,
    ) {}

    #[Route('/refresh', methods: ['POST'])]
    public function refresh(Request $request, #[CurrentUser] ?User $user, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        try {
            $dto = $serializer->deserialize($request->getContent(), RefreshTokenRequestDto::class, 'json');
        } catch (\Symfony\Component\Serializer\Exception\NotEncodableValueException $e) {
            error_log('Invalid JSON on /api/token/refresh: ' . $request->getContent());
            return $this->json(['error' => 'Invalid JSON: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $refreshTokenString = $dto->refresh_token;

        if (!$refreshTokenString) {
            return $this->json(['error' => 'Missing refresh_token'], Response::HTTP_BAD_REQUEST);
        }

        $refreshToken = $this->refreshTokenRepository->findValidByToken($refreshTokenString);

        if (!$refreshToken || $refreshToken->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
        }

        // Generate new access token
        $newAccessToken = $this->jwtTokenManager->create($user);

        // Optionally rotate refresh token: revoke old, create new
        $this->refreshTokenRepository->revokeByUser($user);
        
        $newRefreshToken = new RefreshToken();
        $newRefreshToken->setUser($user);
        $newRefreshToken->setToken(bin2hex(random_bytes(32)));
        $newRefreshToken->setExpiresAt(new \DateTimeImmutable('+30 days'));
        $this->refreshTokenRepository->save($newRefreshToken, true);

        return $this->json([
            'token' => $newAccessToken,
            'refresh_token' => $newRefreshToken->getToken(),
        ]);
    }
}
