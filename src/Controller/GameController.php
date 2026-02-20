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
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[Route('api/game', name: 'app_game')]
final class GameController extends AbstractController
{
    #[Route('/create', name: 'app_game_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        Request $request,
        PlayerRepository $playerRepository,
        GameService $gameService,
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
        
        // Use GameService to create game
        $game = $gameService->createGame($gameName, $user);

        // Automatically add the creator as a player
        $gameService->addPlayerToGame($user, $game);

        return $this->json([
            'message' => 'Game created successfully',
            'gameId' => $game->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{gameId}/join', name: 'app_game_join', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(
        int $gameId,
        GameRepository $gameRepository,
        PlayerRepository $playerRepository,
        GameService $gameService,
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

        // Use GameService to add player
        $gameService->addPlayerToGame($user, $game);

        return $this->json([
            'message' => 'Game joined successfully',
            'gameId' => $game->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/leave', name: 'app_game_leave', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function leave(
        Request $request,
        PlayerRepository $playerRepository,
        GameService $gameService,
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

        $game = $activePlayer->getGame();
        
        // Use GameService to remove player
        $gameService->removePlayerFromGame($user, $game);

        return $this->json([
            'message' => 'Game left successfully',
            'gameId' => $game->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{gameId}/start', name: 'app_game_start', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function start(
        int $gameId,
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

        try {
            // Use GameService to start game
            $gameService->startGame($game);
        } catch (\InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_CONFLICT
            );
        }

        return $this->json([
            'message' => 'Game started successfully',
            'gameId' => $game->getId(),
            'status' => $game->getStatus()->value,
        ], Response::HTTP_OK);
    }

    #[Route('/{gameId}/turn/play', name: 'app_game_turn_play', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function playTurn(
        int $gameId,
        GameRepository $gameRepository,
        PlayerRepository $playerRepository,
        GameService $gameService,
        HubInterface $hub,
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

        $activePlayer = $playerRepository->findActivePlayerByUser($user);
        if (!$activePlayer || $activePlayer->getGame()->getId() !== $gameId) {
            return $this->json(
                ['error' => 'User is not part of this game'],
                Response::HTTP_FORBIDDEN
            );
        }

        $currentPlayer = $gameService->getCurrentPlayer($game);
        if (!$currentPlayer || $currentPlayer->getId() !== $activePlayer->getId()) {
            return $this->json(
                ['error' => 'It is not your turn'],
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $result = $gameService->playCurrentTurn($game);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_CONFLICT
            );
        }

        $update = new Update(
            topics: ["game/{$gameId}"],
            data: json_encode([
                'type' => 'turn_played',
                'gameId' => $gameId,
                'playerId' => $result['player']->getId(),
                'dice' => $result['dice'],
                'position' => [
                    'id' => $result['position']->getId(),
                    'number' => $result['position']->getNumber(),
                ],
                'turn' => $result['turn'],
                'nextPlayerId' => $result['nextPlayer']?->getId(),
            ])
        );

        $hub->publish($update);

        return $this->json([
            'message' => 'Turn played successfully',
            'gameId' => $gameId,
            'playerId' => $result['player']->getId(),
            'dice' => $result['dice'],
            'position' => [
                'id' => $result['position']->getId(),
                'number' => $result['position']->getNumber(),
            ],
            'turn' => $result['turn'],
            'nextPlayerId' => $result['nextPlayer']?->getId(),
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
            }, $player->getCards()->toArray());

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
            'gameStatus' => $game->getStatus()->value,
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
