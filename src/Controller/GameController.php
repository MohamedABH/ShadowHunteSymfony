<?php

namespace App\Controller;

use App\Dto\GameCreateRequestDto;
use App\Dto\GameJoinRequestDto;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

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

        $dto = $serializer->deserialize($request->getContent(), GameCreateRequestDto::class, 'json');

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

        $dto = $serializer->deserialize($request->getContent(), GameJoinRequestDto::class, 'json');

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
        if ($game->getStatus() === 'waiting') {
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
            ],
            'player' => [
                'id' => $player->getId(),
                'color' => $player->getColor(),
                'currentDamage' => $player->getCurrentDamage(),
                'revealed' => $player->isRevealed(),
            ],
        ], Response::HTTP_OK);
    }
}
