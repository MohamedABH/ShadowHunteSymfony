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

        $entityManager->persist($game);
        // Persist any newly created players attached to the game
        foreach ($game->getPlayers() as $p) {
            $entityManager->persist($p);
        }

        $entityManager->flush();

        // Automatically add the creator as a player
        $player = $playerRepository->createPlayer($user, $game);
        $entityManager->persist($player);
        $entityManager->flush();

        return $this->json([
            'message' => 'Game created successfully',
            'gameId' => $game->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{gameId}/join', name: 'app_game_join', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(
        int $gameId,
        EntityManagerInterface $entityManager,
        GameRepository $gameRepository,
        PlayerRepository $playerRepository,
        #[CurrentUser] $user,
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

        // delete player from game if game is not started, else inflict max damage to player to eliminate them
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
                // Owner is the only player: abort the game
                $game->setStatus(GameStatus::ABORTED);
                $entityManager->persist($game);
            }
        }
        
        // Remove the leaving player
        if ($game->getStatus()->value === 'pending') {
            $entityManager->remove($activePlayer);
        } else {
            $activePlayer->setCurrentDamage(100); // assuming 100 is max damage
            $entityManager->persist($activePlayer);
        }

        // Check if game has no players left, if so abort the game
        $remainingPlayersCount = $game->getPlayers()->filter(function($p) use ($activePlayer) {
            return $p->getId() !== $activePlayer->getId();
        })->count();
        
        if ($remainingPlayersCount === 0) {
            $game->setStatus(GameStatus::ABORTED);
            $entityManager->persist($game);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Game left successfully',
            'gameId' => $game->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{gameId}/start', name: 'app_game_start', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function start(
        int $gameId,
        EntityManagerInterface $entityManager,
        GameRepository $gameRepository,
        GameService $gameService,
        #[CurrentUser] $user,
    ): JsonResponse
    {
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

    #[Route('/state', name: 'app_game_state', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getGameState(
        #[CurrentUser] $user,
        PlayerRepository $playerRepository
    ): JsonResponse
    {
        // Get the current player for this user
        $currentPlayer = $playerRepository->findActivePlayerByUser($user);

        if (!$currentPlayer) {
            return $this->json(
                ['error' => 'User is not part of an active game'],
                Response::HTTP_NOT_FOUND
            );
        }

        $game = $currentPlayer->getGame();

        // Get all positions for this game
        $positions = $game->getPositions()->toArray();
        
        // Sort positions by number
        usort($positions, function ($a, $b) {
            return $a->getNumber() <=> $b->getNumber();
        });

        // Format positions with placeCards
        $formattedPositions = array_map(function ($position) {
            $placeCard = $position->getPlaceCard();
            return [
                'id' => $position->getId(),
                'number' => $position->getNumber(),
                'placeCard' => $placeCard ? [
                    'id' => $placeCard->getId(),
                    'name' => $placeCard->getName(),
                    'description' => $placeCard->getDescription(),
                    'abilityMessage' => $placeCard->getAbilityMessage(),
                    'link' => $placeCard->getLink(),
                    'roll' => $placeCard->getRoll(),
                ] : null,
            ];
        }, $positions);

        // Get all players for this game
        $players = $game->getPlayers()->toArray();
        
        // Sort players by playing order
        usort($players, function ($a, $b) {
            return ($a->getPlayingOrder() ?? 999) <=> ($b->getPlayingOrder() ?? 999);
        });

        // Calculate whose turn it is based on turn count and number of players
        $turnCount = $game->getTurn();
        $playerCount = count($players);
        $currentPlayerIndex = $playerCount > 0 ? ($turnCount - 1) % $playerCount : null;
        $currentPlayerId = $currentPlayerIndex !== null && isset($players[$currentPlayerIndex]) 
            ? $players[$currentPlayerIndex]->getId() 
            : null;

        // Format players with their data
        $formattedPlayers = array_map(function ($player) {
            $characterCard = null;
            if ($player->isRevealed() && $player->getCharacterCard()) {
                $card = $player->getCharacterCard();
                $characterCard = [
                    'id' => $card->getId(),
                    'name' => $card->getName(),
                    'description' => $card->getDescription(),
                    'abilityMessage' => $card->getAbilityMessage(),
                    'link' => $card->getLink(),
                    'type' => $card->getType()?->value,
                    'maxDamage' => $card->getMaxDamage(),
                    'initial' => $card->getInitial(),
                ];
            }

            // Get equipment (action cards) held by the player
            $equipments = array_map(function ($location) {
                $actionCard = $location->getActionCard();
                return [
                    'id' => $actionCard->getId(),
                    'name' => $actionCard->getName(),
                    'description' => $actionCard->getDescription(),
                    'abilityMessage' => $actionCard->getAbilityMessage(),
                    'link' => $actionCard->getLink(),
                    'type' => $actionCard->getType()?->value,
                    'count' => $actionCard->getCount(),
                ];
            }, $player->getCardss()->toArray());

            $position = $player->getPosition();
            return [
                'id' => $player->getId(),
                'username' => $player->getUser()->getUsername(),
                'color' => $player->getColor()->value,
                'revealed' => $player->isRevealed(),
                'characterCard' => $characterCard,
                'position' => $position ? [
                    'id' => $position->getId(),
                    'number' => $position->getNumber(),
                ] : null,
                'currentDamage' => $player->getCurrentDamage(),
                'playingOrder' => $player->getPlayingOrder(),
                'equipments' => $equipments,
            ];
        }, $players);

        return $this->json([
            'gameId' => $game->getId(),
            'turn' => $turnCount,
            'currentPlayerId' => $currentPlayerId,
            'positions' => $formattedPositions,
            'players' => $formattedPlayers,
        ], Response::HTTP_OK);
    }

    #[Route('/list', name: 'app_game_list', methods: ['GET'])]
    public function listGames(
        GameRepository $gameRepository
    ): JsonResponse
    {
        $games = $gameRepository->findAll();

        $formattedGames = array_map(function ($game) {
            return [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'status' => $game->getStatus()->value,
                'playerCount' => $game->getPlayers()->count(),
            ];
        }, $games);

        return $this->json([
            'games' => $formattedGames,
            'total' => count($formattedGames),
        ], Response::HTTP_OK);
    }

    
}
