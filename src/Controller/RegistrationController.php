<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Role;
use App\Repository\UserRepository;
use App\Dto\RegistrationRequestDto;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserService $userService,
    ): JsonResponse {
        try {
            $dto = $serializer->deserialize($request->getContent(), RegistrationRequestDto::class, 'json');
        } catch (\Symfony\Component\Serializer\Exception\NotEncodableValueException $e) {
            error_log('Invalid JSON on /register: ' . $request->getContent());
            return $this->json(['error' => 'Invalid JSON: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($dto);
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

        // Check duplicates
        if ($userService->userExists($dto->username, $dto->email)) {
            $existingUser = $userService->findUser($dto->username, $dto->email);
            
            if ($existingUser && $existingUser->getEmail() === $dto->email) {
                return $this->json(['error' => 'Email already registered'], Response::HTTP_CONFLICT);
            }
            
            if ($existingUser && $existingUser->getUsername() === $dto->username) {
                return $this->json(['error' => 'Username already taken'], Response::HTTP_CONFLICT);
            }
        }

        try {
            // Create user with default ROLE_USER
            $user = $userService->createUser($dto->username, $dto->email, $dto->password, ['ROLE_USER']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        // Validate entity
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'message' => 'User registered successfully',
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ], Response::HTTP_CREATED);
    }
}
