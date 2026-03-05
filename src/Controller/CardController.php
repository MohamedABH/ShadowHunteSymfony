<?php

namespace App\Controller;

use App\Entity\ActionCard;
use App\Entity\Player;
use App\Service\ActionCardEffect\ActionCardEffectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Repository\ActionCardRepository;
use Doctrine\ORM\EntityManagerInterface;

#[Route('api/card', name: 'app_card')]
final class CardController extends AbstractController
{
    #[Route('/play/{cardId}', name: 'app_card_play', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function playCard(
        int $cardId,
        Request $request,
        #[CurrentUser] $user,
        ActionCardRepository $cardRepository,
        PlayerRepository $playerRepository,
        ActionCardEffectService $actionCardEffectService,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        // Find the card
        $card = $cardRepository->find($cardId);
        if (!$card) {
            return $this->json(['error' => 'Card not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Find the player
        $player = $playerRepository->findActivePlayerByUser($user);
        if (!$player) {
            return $this->json(['error' => 'Player not in an active game'], Response::HTTP_BAD_REQUEST);
        }
        
        $game = $player->getGame();
        
        // Get context from request (targetPlayer, diceRoll, choices, etc.)
        $context = json_decode($request->getContent(), true) ?? [];
        
        // Get required context to inform the client what's needed
        $requiredContext = $actionCardEffectService->getRequiredContext($card);
        
        try {
            // Execute the card effect
            $result = $actionCardEffectService->executeCardEffect($card, $player, $game, $context);
            
            if ($result->hasPendingActions()) {
                // Need more information from the client
                return $this->json([
                    'status' => 'pending',
                    'message' => $result->getMessage(),
                    'pendingActions' => $result->getPendingActions(),
                    'requiredContext' => $requiredContext
                ], Response::HTTP_ACCEPTED);
            }
            
            if ($result->isSuccess()) {
                return $this->json([
                    'status' => 'success',
                    'message' => $result->getMessage(),
                    'changes' => $result->getChanges()
                ], Response::HTTP_OK);
            } else {
                return $this->json([
                    'status' => 'failure',
                    'message' => $result->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }
            
        } catch (\RuntimeException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
