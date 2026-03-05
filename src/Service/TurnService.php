<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Location;
use App\Entity\Player;
use App\Enum\LocationEnum;
use App\Enum\TurnPhase;
use App\Repository\LocationRepository;
use App\Repository\PositionRepository;
use Doctrine\ORM\EntityManagerInterface;

class TurnService
{
    public function __construct(
        private readonly PositionRepository $positionRepository,
        private readonly LocationRepository $locationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly GameService $gameService,
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

        $d4 = random_int(1, 4);
        $d6 = random_int(1, 6);
        $rollTotal = $d4 + $d6;

        $game->setCurrentTurnRoll($rollTotal);

        $position = null;
        $requiresPositionChoice = $rollTotal === 7;
        if ($requiresPositionChoice) {
            $game->setTurnPhase(TurnPhase::MOVE);
        } else {
            $position = $this->positionRepository->findOneByGameAndRoll($game->getId(), $rollTotal);
            if (!$position) {
                throw new \RuntimeException('Position not found for roll: ' . $rollTotal);
            }

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
        $placeName = $placeCard->getName();
        $effectResult = ['place' => $placeName];

        switch ($placeName) {
            case 'Antre de l\'ermite':
                $drawn = $this->drawCardFromDeck($game, $currentPlayer, LocationEnum::SIGHT_DECK);
                $effectResult['drawnCardId'] = $drawn?->getActionCard()?->getId();
                break;

            case 'Porte de l\'Outremonde':
                $deckType = $context['deckType'] ?? null;
                if (!in_array($deckType, ['dark', 'light', 'sight'], true)) {
                    throw new \InvalidArgumentException('deckType is required and must be dark, light, or sight.');
                }

                $deck = match ($deckType) {
                    'dark' => LocationEnum::DARK_DECK,
                    'light' => LocationEnum::LIGHT_DECK,
                    default => LocationEnum::SIGHT_DECK,
                };
                $drawn = $this->drawCardFromDeck($game, $currentPlayer, $deck);
                $effectResult['deckType'] = $deckType;
                $effectResult['drawnCardId'] = $drawn?->getActionCard()?->getId();
                break;

            case 'Monastère':
                $drawn = $this->drawCardFromDeck($game, $currentPlayer, LocationEnum::LIGHT_DECK);
                $effectResult['drawnCardId'] = $drawn?->getActionCard()?->getId();
                break;

            case 'Cimetière':
                $drawn = $this->drawCardFromDeck($game, $currentPlayer, LocationEnum::DARK_DECK);
                $effectResult['drawnCardId'] = $drawn?->getActionCard()?->getId();
                break;

            case 'Forêt hantée':
                $targetPlayerId = isset($context['targetPlayerId']) ? (int) $context['targetPlayerId'] : null;
                $outcome = $context['outcome'] ?? null;

                if (!$targetPlayerId || !in_array($outcome, ['damage', 'heal'], true)) {
                    throw new \InvalidArgumentException('Forêt hantée requires targetPlayerId and outcome (damage|heal).');
                }

                $targetPlayer = $this->findPlayerInGame($game, $targetPlayerId);
                if (!$targetPlayer) {
                    throw new \InvalidArgumentException('Target player not found in this game.');
                }

                $before = $targetPlayer->getCurrentDamage() ?? 0;
                if ($outcome === 'damage') {
                    $maxDamage = $targetPlayer->getCharacterCard()?->getMaxDamage() ?? 14;
                    $after = min($maxDamage, $before + 2);
                } else {
                    $after = max(0, $before - 1);
                }

                $targetPlayer->setCurrentDamage($after);
                $this->entityManager->persist($targetPlayer);

                $effectResult['targetPlayerId'] = $targetPlayer->getId();
                $effectResult['outcome'] = $outcome;
                $effectResult['damageBefore'] = $before;
                $effectResult['damageAfter'] = $after;
                break;

            case 'Sanctuaire ancien':
                $targetPlayerId = isset($context['targetPlayerId']) ? (int) $context['targetPlayerId'] : null;
                if (!$targetPlayerId) {
                    throw new \InvalidArgumentException('Sanctuaire ancien requires targetPlayerId.');
                }

                $targetPlayer = $this->findPlayerInGame($game, $targetPlayerId);
                if (!$targetPlayer || $targetPlayer->getId() === $currentPlayer->getId()) {
                    throw new \InvalidArgumentException('Invalid target player for Sanctuaire ancien.');
                }

                $equipments = array_values(array_filter(
                    $targetPlayer->getCards()->toArray(),
                    fn (Location $location) => $location->getLocation() === LocationEnum::IN_PLAY
                ));

                if (count($equipments) === 0) {
                    $effectResult['targetPlayerId'] = $targetPlayer->getId();
                    $effectResult['stolenCardId'] = null;
                    break;
                }

                $selectedLocation = null;
                $targetLocationId = isset($context['targetLocationId']) ? (int) $context['targetLocationId'] : null;
                if ($targetLocationId) {
                    foreach ($equipments as $equipment) {
                        if ($equipment->getId() === $targetLocationId) {
                            $selectedLocation = $equipment;
                            break;
                        }
                    }
                    if (!$selectedLocation) {
                        throw new \InvalidArgumentException('targetLocationId is not a valid equipment for the target player.');
                    }
                } elseif (count($equipments) === 1) {
                    $selectedLocation = $equipments[0];
                } else {
                    throw new \InvalidArgumentException('targetLocationId is required when target player has multiple equipments.');
                }

                $selectedLocation->setPlayer($currentPlayer);
                $selectedLocation->setLocation(LocationEnum::IN_PLAY);
                $this->entityManager->persist($selectedLocation);

                $effectResult['targetPlayerId'] = $targetPlayer->getId();
                $effectResult['stolenCardId'] = $selectedLocation->getActionCard()?->getId();
                break;

            default:
                $effectResult['message'] = 'No implemented effect for this place.';
                break;
        }

        $game->setTurnPhase(TurnPhase::ATTACK);
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

        $targetPlayer = $this->findPlayerInGame($game, $targetPlayerId);
        if (!$targetPlayer || $targetPlayer->getId() === $currentPlayer->getId()) {
            throw new \InvalidArgumentException('Invalid target player for attack.');
        }

        $d4 = random_int(1, 4);
        $d6 = random_int(1, 6);
        $damage = $d4 + $d6;

        $before = $targetPlayer->getCurrentDamage() ?? 0;
        $maxDamage = $targetPlayer->getCharacterCard()?->getMaxDamage() ?? 14;
        $after = min($maxDamage, $before + $damage);
        $targetPlayer->setCurrentDamage($after);

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

    private function drawCardFromDeck(Game $game, Player $player, LocationEnum $deckLocation): ?Location
    {
        $cardLocation = $this->locationRepository->createQueryBuilder('l')
            ->andWhere('l.game = :game')
            ->andWhere('l.location = :deck')
            ->setParameter('game', $game)
            ->setParameter('deck', $deckLocation)
            ->orderBy('l.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$cardLocation) {
            $this->gameService->reshuffleDeck($game->getId());
            $cardLocation = $this->locationRepository->createQueryBuilder('l')
                ->andWhere('l.game = :game')
                ->andWhere('l.location = :deck')
                ->setParameter('game', $game)
                ->setParameter('deck', $deckLocation)
                ->orderBy('l.position', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        if (!$cardLocation) {
            return null;
        }

        $cardLocation->setLocation(LocationEnum::IN_PLAY);
        $cardLocation->setPlayer($player);
        $cardLocation->setPosition(null);
        $this->entityManager->persist($cardLocation);

        return $cardLocation;
    }

    private function findPlayerInGame(Game $game, int $playerId): ?Player
    {
        foreach ($game->getPlayers() as $player) {
            if ($player->getId() === $playerId) {
                return $player;
            }
        }

        return null;
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
