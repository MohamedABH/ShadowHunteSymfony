<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Player;
use App\Enum\TurnPhase;
use App\Repository\PlayerRepository;
use App\Repository\PositionRepository;
use App\Service\AbstractCardEffect\AbstractCardEffectService;
use Doctrine\ORM\EntityManagerInterface;

class TurnService
{
    public function __construct(
        private readonly PositionRepository $positionRepository,
        private readonly PlayerRepository $playerRepository,
        private readonly AbstractCardEffectService $abstractCardEffectService,
        private readonly DamageService $damageService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getCurrentPlayer(Game $game): ?Player
    {
        $players = $game->getPlayers()->toArray();
        if (count($players) === 0) {
            return null;
        }

        usort($players, function (Player $a, Player $b) {
            return ($a->getPlayingOrder() ?? 999) <=> ($b->getPlayingOrder() ?? 999);
        });

        $turn = (int) ($game->getTurn() ?? 1);
        $index = ($turn - 1) % count($players);

        return $players[$index] ?? null;
    }

    public function rollCurrentTurn(Game $game): array
    {
        $this->assertGameOngoing($game);
        $this->assertTurnPhase($game, TurnPhase::ROLL);

        $currentPlayer = $this->getCurrentPlayer($game);
        if (!$currentPlayer) {
            throw new \RuntimeException('No players available for this game.');
        }

        $currentPlaceCardId = $currentPlayer->getPosition()?->getPlaceCard()?->getId();

        $maxRerolls = 50;
        $attempt = 0;
        do {
            $d4 = random_int(1, 4);
            $d6 = random_int(1, 6);
            $rollTotal = $d4 + $d6;

            $position = null;
            $requiresPositionChoice = $rollTotal === 7;
            if (!$requiresPositionChoice) {
                $position = $this->positionRepository->findOneByGameAndRoll($game->getId(), $rollTotal);
                if (!$position) {
                    throw new \RuntimeException('Position not found for roll: ' . $rollTotal);
                }
            }

            $rolledPlaceCardId = $position?->getPlaceCard()?->getId();
            $samePlaceAsCurrent = $currentPlaceCardId !== null
                && $rolledPlaceCardId !== null
                && $currentPlaceCardId === $rolledPlaceCardId;

            $attempt++;
            if ($attempt > $maxRerolls) {
                throw new \RuntimeException('Unable to roll a different place card after multiple attempts.');
            }
        } while ($samePlaceAsCurrent);

        $game->setCurrentTurnRoll($rollTotal);

        if ($requiresPositionChoice) {
            $game->setTurnPhase(TurnPhase::MOVE);
        } else {
            $currentPlayer->setPosition($position);
            $game->setTurnPhase(TurnPhase::PLACE_ABILITY);
            $this->entityManager->persist($currentPlayer);
        }

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return [
            'player' => $currentPlayer,
            'dice' => [
                'd4' => $d4,
                'd6' => $d6,
            ],
            'rollTotal' => $rollTotal,
            'requiresPositionChoice' => $requiresPositionChoice,
            'position' => $position,
            'turnPhase' => $game->getTurnPhase()?->value,
        ];
    }

    public function moveCurrentPlayer(Game $game, ?int $positionNumber): array
    {
        $this->assertGameOngoing($game);
        $this->assertTurnPhase($game, TurnPhase::MOVE);

        $currentPlayer = $this->getCurrentPlayer($game);
        if (!$currentPlayer) {
            throw new \RuntimeException('No players available for this game.');
        }

        if ($game->getCurrentTurnRoll() !== 7) {
            throw new \InvalidArgumentException('Manual position selection is only allowed when roll total is 7.');
        }

        if ($positionNumber === null) {
            throw new \InvalidArgumentException('positionNumber is required when roll total is 7.');
        }

        $position = $this->positionRepository->findOneByGameAndNumber($game->getId(), $positionNumber);
        if (!$position) {
            throw new \InvalidArgumentException('Invalid position number for this game.');
        }

        $currentPlayer->setPosition($position);
        $game->setTurnPhase(TurnPhase::PLACE_ABILITY);

        $this->entityManager->persist($currentPlayer);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return [
            'player' => $currentPlayer,
            'position' => $position,
            'turnPhase' => $game->getTurnPhase()?->value,
        ];
    }

    public function useCurrentPlaceAbility(Game $game, array $context = []): array
    {
        $this->assertGameOngoing($game);
        $this->assertTurnPhase($game, TurnPhase::PLACE_ABILITY);

        $currentPlayer = $this->getCurrentPlayer($game);
        if (!$currentPlayer) {
            throw new \RuntimeException('No players available for this game.');
        }

        $position = $currentPlayer->getPosition();
        if (!$position || !$position->getPlaceCard()) {
            throw new \RuntimeException('Current player has no place card to resolve.');
        }

        $placeCard = $position->getPlaceCard();
        $effectExecution = $this->abstractCardEffectService->executeCardEffect($placeCard, $currentPlayer, $game, $context);
        if (!$effectExecution->isSuccess()) {
            throw new \RuntimeException($effectExecution->getMessage());
        }

        $effectResult = [
            'message' => $effectExecution->getMessage(),
            'changes' => $effectExecution->getChanges(),
            'pendingActions' => $effectExecution->getPendingActions(),
        ];

        if ($effectExecution->hasPendingActions()) {
            $game->setTurnPhase(TurnPhase::PLACE_ABILITY);
        } else {
            $game->setTurnPhase(TurnPhase::ATTACK);
        }

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return [
            'player' => $currentPlayer,
            'position' => $position,
            'place' => $placeCard,
            'effect' => $effectResult,
            'turnPhase' => $game->getTurnPhase()?->value,
        ];
    }

    public function attackCurrentPlayer(Game $game, ?int $targetPlayerId): array
    {
        $this->assertGameOngoing($game);
        $this->assertTurnPhase($game, TurnPhase::ATTACK);

        $currentPlayer = $this->getCurrentPlayer($game);
        if (!$currentPlayer) {
            throw new \RuntimeException('No players available for this game.');
        }

        if (!$targetPlayerId) {
            $game->setTurnPhase(TurnPhase::END);
            $this->entityManager->persist($game);
            $this->entityManager->flush();

            return [
                'player' => $currentPlayer,
                'skipped' => true,
                'turnPhase' => $game->getTurnPhase()?->value,
            ];
        }

        $targetPlayer = $this->playerRepository->findOneByGameAndId($game, $targetPlayerId);
        if (!$targetPlayer || $targetPlayer->getId() === $currentPlayer->getId()) {
            throw new \InvalidArgumentException('Invalid target player for attack.');
        }

        $d4 = random_int(1, 4);
        $d6 = random_int(1, 6);
        $damage = $d4 + $d6;

        $damageResult = $this->damageService->applyDamage($targetPlayer, $damage);
        $before = $damageResult['before'];
        $after = $damageResult['after'];
        $targetKnockedOut = $this->damageService->enforceKnockout($targetPlayer);

        $game->setTurnPhase(TurnPhase::END);

        $this->entityManager->persist($targetPlayer);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return [
            'player' => $currentPlayer,
            'target' => $targetPlayer,
            'dice' => [
                'd4' => $d4,
                'd6' => $d6,
            ],
            'damage' => $damage,
            'damageBefore' => $before,
            'damageAfter' => $after,
            'targetKnockedOut' => $targetKnockedOut,
            'turnPhase' => $game->getTurnPhase()?->value,
        ];
    }

    public function endCurrentTurn(Game $game): array
    {
        $this->assertGameOngoing($game);
        $this->assertTurnPhase($game, TurnPhase::END);

        $currentPlayer = $this->getCurrentPlayer($game);
        if (!$currentPlayer) {
            throw new \RuntimeException('No players available for this game.');
        }

        $game->setTurn(((int) ($game->getTurn() ?? 1)) + 1);
        $game->setCurrentTurnRoll(null);
        $game->setTurnPhase(TurnPhase::ROLL);

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $nextPlayer = $this->getCurrentPlayer($game);

        return [
            'player' => $currentPlayer,
            'turn' => $game->getTurn(),
            'nextPlayer' => $nextPlayer,
            'turnPhase' => $game->getTurnPhase()?->value,
        ];
    }

    private function assertGameOngoing(Game $game): void
    {
        if ($game->getStatus()->value !== 'ongoing') {
            throw new \InvalidArgumentException('Game must be ongoing to perform turn actions.');
        }
    }

    private function assertTurnPhase(Game $game, TurnPhase $expectedPhase): void
    {
        if ($game->getTurnPhase() !== $expectedPhase) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid turn phase. Expected %s, current %s.',
                $expectedPhase->value,
                $game->getTurnPhase()?->value ?? 'none'
            ));
        }
    }
}
