<?php

namespace App\Controller;

use App\Entity\RefreshToken;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/api', name: 'api_')]
final class AuthController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
        RefreshTokenRepository $refreshTokenRepository
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ((!isset($data['username']) && !isset($data['email'])) || !isset($data['password'])) {
            return $this->json(
                ['error' => 'Provide username or email and password'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = null;
        if (isset($data['username'])) {
            $user = $userRepository->findOneBy(['username' => $data['username']]);
        }
        if (!$user && isset($data['email'])) {
            $user = $userRepository->findOneBy(['email' => $data['email']]);
        }

        if (!$user) {
            return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $jwtManager->create($user);

        // Revoke old refresh tokens and create new one
        $refreshTokenRepository->revokeByUser($user);
        
        $refreshToken = new RefreshToken();
        $refreshToken->setUser($user);
        $refreshToken->setToken(bin2hex(random_bytes(32)));
        $refreshToken->setExpiresAt(new \DateTimeImmutable('+30 days'));
        $refreshTokenRepository->save($refreshToken, true);

        return $this->json([
            'message' => 'Login successful',
            'token' => $token,
            'refresh_token' => $refreshToken->getToken(),
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ], Response::HTTP_OK);
    }

    #[Route('/auth-check/public', name: 'auth_check_public', methods: ['GET'])]
    public function authCheckPublic(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['authenticated' => false], Response::HTTP_OK);
        }

        return $this->json([
            'authenticated' => true,
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ], Response::HTTP_OK);
    }

    #[Route('/auth-check/protected', name: 'auth_check_protected', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function authCheckProtected(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json([
            'authenticated' => true,
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ], Response::HTTP_OK);
    }
}
