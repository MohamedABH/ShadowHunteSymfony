<?php

namespace App\Controller;

use App\Dto\LoginRequestDto;
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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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
        RefreshTokenRepository $refreshTokenRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        try {
            $dto = $serializer->deserialize($request->getContent(), LoginRequestDto::class, 'json');
        } catch (\Symfony\Component\Serializer\Exception\NotEncodableValueException $e) {
            error_log('Invalid JSON on /login: ' . $request->getContent());
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

        if (empty($dto->username) && empty($dto->email)) {
            return $this->json(['error' => 'Provide username or email and password'], Response::HTTP_BAD_REQUEST);
        }

        $user = null;
        if (!empty($dto->username)) {
            $user = $userRepository->findOneBy(['username' => $dto->username]);
        }
        if (!$user && !empty($dto->email)) {
            $user = $userRepository->findOneBy(['email' => $dto->email]);
        }

        if (!$user || !$passwordHasher->isPasswordValid($user, $dto->password)) {
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
