<?php

namespace App\Service\AbstractCardEffect\Handler\ActionCard;

use App\Service\AbstractCardEffect\AbstractCardEffectHandlerInterface;
use App\Service\AbstractCardEffect\AbstractCardEffectResult;
use App\Entity\AbstractCard;
use App\Entity\ActionCard;
use App\Entity\Player;
use App\Entity\Game;
use App\Service\DamageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Handler for "Poupée démoniaque" card
 * Effect: Target a player, roll d6. 1-4: deal 3 damage. 5-6: take 3 damage yourself
 */
#[AutoconfigureTag('app.abstract_card_effect_handler')]
class PoupeeDemoniaqueHandler implements AbstractCardEffectHandlerInterface
{
    public function __construct(
        private readonly DamageService $damageService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function supports(AbstractCard $card): bool
    {
        return $card instanceof ActionCard && $card->getName() === 'Poupée démoniaque';
    }

    public function getRequiredContext(): array
    {
        return ['targetPlayerId', 'diceRoll'];
    }

    public function execute(AbstractCard $card, Player $player, Game $game, array $context = []): AbstractCardEffectResult
    {
        if (!$card instanceof ActionCard) {
            return AbstractCardEffectResult::failure('Unsupported card type for PoupeeDemoniaqueHandler');
        }

        $targetPlayerId = $context['targetPlayerId'];
        $diceRoll = $context['diceRoll'];

        if ($diceRoll < 1 || $diceRoll > 6) {
            return AbstractCardEffectResult::failure('Invalid dice roll. Must be between 1 and 6.');
        }

        $targetPlayer = null;
        foreach ($game->getPlayers() as $p) {
            if ($p->getId() === $targetPlayerId) {
                $targetPlayer = $p;
                break;
            }
        }

        if (!$targetPlayer) {
            return AbstractCardEffectResult::failure('Target player not found');
        }

        if ($diceRoll >= 1 && $diceRoll <= 4) {
            $damageResult = $this->damageService->applyDamage($targetPlayer, 3);
            $damageBefore = $damageResult['before'];
            $damageAfter = $damageResult['after'];
            $targetKnockedOut = $this->damageService->enforceKnockout($targetPlayer);

            $this->entityManager->persist($targetPlayer);
            $this->entityManager->flush();

            return AbstractCardEffectResult::success(
                sprintf('%s rolled %d and dealt 3 damage to %s',
                    $player->getUser()->getUsername(),
                    $diceRoll,
                    $targetPlayer->getUser()->getUsername()
                ),
                [
                    'dice_roll' => $diceRoll,
                    'outcome' => 'target_damaged',
                    'target_player_id' => $targetPlayer->getId(),
                    'damage_before' => $damageBefore,
                    'damage_after' => $damageAfter,
                    'target_knocked_out' => $targetKnockedOut,
                ]
            );
        }

        $damageResult = $this->damageService->applyDamage($player, 3);
        $damageBefore = $damageResult['before'];
        $damageAfter = $damageResult['after'];
        $selfKnockedOut = $this->damageService->enforceKnockout($player);

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return AbstractCardEffectResult::success(
            sprintf('%s rolled %d and took 3 damage themselves!',
                $player->getUser()->getUsername(),
                $diceRoll
            ),
            [
                'dice_roll' => $diceRoll,
                'outcome' => 'self_damaged',
                'player_id' => $player->getId(),
                'damage_before' => $damageBefore,
                'damage_after' => $damageAfter,
                'self_knocked_out' => $selfKnockedOut,
            ]
        );
    }
}
