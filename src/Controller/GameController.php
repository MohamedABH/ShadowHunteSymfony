<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('api/game', name: 'app_game')]
final class GameController extends AbstractController
{
    #[Route('/create', name: 'app_game_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        Request $request,
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

        $data = json_decode($request->getContent(), true);
        $gameName = $data['name'] ?? 'New Game';
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
