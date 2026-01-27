<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/api', name: 'api_')]
final class AuthController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): JsonResponse
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

        return $this->json([
            'message' => 'Login successful',
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ], Response::HTTP_OK);
    }
}
