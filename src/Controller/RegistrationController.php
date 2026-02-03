<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Dto\RegistrationRequestDto;
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
        UserPasswordHasherInterface $passwordHasher,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
    ): JsonResponse {
        $dto = $serializer->deserialize($request->getContent(), RegistrationRequestDto::class, 'json');

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
        if ($userRepository->findOneBy(['email' => $dto->email])) {
            return $this->json(['error' => 'Email already registered'], Response::HTTP_CONFLICT);
        }

        if ($userRepository->findOneBy(['username' => $dto->username])) {
            return $this->json(['error' => 'Username already taken'], Response::HTTP_CONFLICT);
        }

        // Create user entity
        $user = new User();
        $user->setEmail($dto->email);
        $user->setUsername($dto->username);
        $user->setPassword($passwordHasher->hashPassword($user, $dto->password));

        // Validate entity
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Persist to database
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'User registered successfully',
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ], Response::HTTP_CREATED);
    }
}
