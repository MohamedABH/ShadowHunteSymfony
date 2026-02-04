<?php

namespace App\Controller;

use App\Dto\GameCreateRequestDto;
use App\Dto\GameJoinRequestDto;
use App\Enum\GameStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Service\GameService;

#[Route('api/game', name: 'app_game')]
final class GameController extends AbstractController
{
    #[Route('/create', name: 'app_game_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        GameRepository $gameRepository,
        PlayerRepository $playerRepository,
        #[CurrentUser] $user,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        // Check if user is already part of a non-completed game
        $activePlayer = $playerRepository->findActivePlayerByUser($user);

        if ($activePlayer) {
            return $this->json(
                [
                    'error' => 'User is already part of an active game',
                    'currentGameId' => $activePlayer->getGame()->getId(),
                ],
                Response::HTTP_CONFLICT
            );
        }

        try {
            $dto = $serializer->deserialize($request->getContent(), GameCreateRequestDto::class, 'json');
        } catch (NotEncodableValueException $e) {
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

        $gameName = $dto->name ?? 'New Game';
        $game = $gameRepository->createGame($gameName, $user);
        $game->setOwner($user);

        $entityManager->persist($game);
        // Persist any newly created players attached to the game
        foreach ($game->getPlayers() as $p) {
            $entityManager->persist($p);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Game created successfully',
            'gameId' => $game->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/join', name: 'app_game_join', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(
        Request $request,
        EntityManagerInterface $entityManager,
        GameRepository $gameRepository,
        PlayerRepository $playerRepository,
        #[CurrentUser] $user,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        // Check if user is already part of a non-completed game
        $activePlayer = $playerRepository->findActivePlayerByUser($user);

        if ($activePlayer) {
            return $this->json(
                [
                    'error' => 'User is already part of an active game',
                    'currentGameId' => $activePlayer->getGame()->getId(),
                ],
                Response::HTTP_CONFLICT
            );
        }

        try {
            $dto = $serializer->deserialize($request->getContent(), GameJoinRequestDto::class, 'json');
        } catch (NotEncodableValueException $e) {
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

        $gameId = $dto->gameId ?? null;
        $game = $gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Game not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $player = $playerRepository->createPlayer($user, $game);
        $entityManager->persist($player);

        $entityManager->flush();

        return $this->json([
            'message' => 'Game joined successfully',
            'gameId' => $game->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/leave', name: 'app_game_leave', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function leave(
        Request $request,
        EntityManagerInterface $entityManager,
        GameRepository $gameRepository,
        PlayerRepository $playerRepository,
        #[CurrentUser] $user,
    ): JsonResponse
    {
        // Check if user is already part of a non-completed game
        $activePlayer = $playerRepository->findActivePlayerByUser($user);

        if (!$activePlayer) {
            return $this->json(
                [
                    'error' => 'User is not part of an active game',
                ],
                Response::HTTP_CONFLICT
            );
        }

        // delete player from game if game is not started, else inflic max damage to player to eliminate them
        $game = $activePlayer->getGame();
        
        // Check if user is the game owner
        $isOwner = $game->getOwner()->getId() === $user->getId();
        
        if ($isOwner) {
            // Owner leaves: designate another player as the new owner
            $remainingPlayers = $game->getPlayers()->filter(function($p) use ($activePlayer) {
                return $p->getId() !== $activePlayer->getId();
            });
            
            if ($remainingPlayers->count() > 0) {
                // Promote the first remaining player to owner
                $newOwner = $remainingPlayers->first()->getUser();
                $game->setOwner($newOwner);
                $entityManager->persist($game);
            } else {
                // Owner is the only player: end the game
                $game->setStatus(GameStatus::ENDED);
                $entityManager->persist($game);
            }
        }
        
        // Remove the leaving player
        if ($game->getStatus()->value === 'waiting') {
            $entityManager->remove($activePlayer);
        } else {
            $activePlayer->setCurrentDamage(100); // assuming 100 is max damage
            $entityManager->persist($activePlayer);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Game left successfully',
            'gameId' => $game->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/current', name: 'app_game_current', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getCurrentGame(
        #[CurrentUser] $user,
        PlayerRepository $playerRepository
    ): JsonResponse
    {
        $player = $playerRepository->findOneBy(['user' => $user]);

        if (!$player) {
            return $this->json(
                ['currentGame' => null, 'message' => 'User is not part of any game'],
                Response::HTTP_OK
            );
        }

        $game = $player->getGame();

        return $this->json([
            'currentGame' => [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'status' => $game->getStatus(),
                'turn' => $game->getTurn(),
                'owner' => [
                    'id' => $game->getOwner()->getId(),
                    'username' => $game->getOwner()->getUsername(),
                ],
            ],
            'player' => [
                'id' => $player->getId(),
                'color' => $player->getColor(),
                'currentDamage' => $player->getCurrentDamage(),
                'revealed' => $player->isRevealed(),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/start', name: 'app_game_start', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function start(
        Request $request,
        EntityManagerInterface $entityManager,
        GameRepository $gameRepository,
        GameService $gameService,
        #[CurrentUser] $user,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $gameId = $data['gameId'] ?? null;

        $game = $gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Game not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Check if user is the game owner
        if ($game->getOwner()->getId() !== $user->getId()) {
            return $this->json(
                ['error' => 'Only the game owner can start the game'],
                Response::HTTP_FORBIDDEN
            );
        }

        // Check game status is 'pending' before starting
        if ($game->getStatus()->value !== 'pending') {
            return $this->json(
                ['error' => 'Game cannot be started. Current status: ' . $game->getStatus()->value],
                Response::HTTP_CONFLICT
            );
        }

        // Update game status to 'in_progress'
        $game->setStatus(GameStatus::ONGOING);
        $entityManager->persist($game);
        $entityManager->flush();

        // Initialize game with character card assignment and deck creation
        $playerIds = array_map(fn($player) => $player->getId(), $game->getPlayers()->toArray());
        $gameService->initializeGame($game->getId(), $playerIds);

        return $this->json([
            'message' => 'Game started successfully',
            'gameId' => $game->getId(),
            'status' => $game->getStatus()->value,
        ], Response::HTTP_OK);
    }

    #[Route('/admin/{gameId}/locations', name: 'app_game_locations_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getLocations(
        int $gameId,
        GameRepository $gameRepository,
    ): JsonResponse
    {
        $game = $gameRepository->find($gameId);
        if (!$game) {
            return $this->json(
                ['error' => 'Game not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Get all locations for this game, organized by location type and sorted by position
        $locations = $game->getLocations()->toArray();
        
        // Sort by location and then by position
        usort($locations, function ($a, $b) {
            if ($a->getLocation()->value === $b->getLocation()->value) {
                return ($a->getPosition() ?? 0) <=> ($b->getPosition() ?? 0);
            }
            return $a->getLocation()->value <=> $b->getLocation()->value;
        });

        // Format the response
        $formattedLocations = array_map(function ($location) {
            return [
                'id' => $location->getId(),
                'actionCard' => [
                    'id' => $location->getActionCard()->getId(),
                    'name' => $location->getActionCard()->getName(),
                    'type' => $location->getActionCard()->getType()?->value,
                ],
                'location' => $location->getLocation()->value,
                'position' => $location->getPosition(),
                'player' => $location->getPlayer() ? [
                    'id' => $location->getPlayer()->getId(),
                    'user' => $location->getPlayer()->getUser()->getUsername(),
                ] : null,
            ];
        }, $locations);

        return $this->json([
            'gameId' => $game->getId(),
            'totalLocations' => count($formattedLocations),
            'locations' => $formattedLocations,
        ], Response::HTTP_OK);
    }

    
}
