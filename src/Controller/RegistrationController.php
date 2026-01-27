<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validate input exists
        if (!isset($data['email']) || !isset($data['password']) || !isset($data['username'])) {
            return $this->json(
                ['error' => 'Missing required fields: email, password, username'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(
                ['error' => 'Invalid email format'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Check if user already exists by email
        if ($userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json(
                ['error' => 'Email already registered'],
                Response::HTTP_CONFLICT
            );
        }

        // Check if username already exists
        if ($userRepository->findOneBy(['username' => $data['username']])) {
            return $this->json(
                ['error' => 'Username already taken'],
                Response::HTTP_CONFLICT
            );
        }

        // Validate password strength
        if (strlen($data['password']) < 8) {
            return $this->json(
                ['error' => 'Password must be at least 8 characters long'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Create user entity
        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername($data['username']);
        $user->setPassword(
            $passwordHasher->hashPassword($user, $data['password'])
        );

        // Validate entity
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(
                ['errors' => $errorMessages],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Persist to database
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(
            [
                'message' => 'User registered successfully',
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
            ],
            Response::HTTP_CREATED
        );
    }
}
