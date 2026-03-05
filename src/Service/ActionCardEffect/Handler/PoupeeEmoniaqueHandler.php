<?php

namespace App\Service\ActionCardEffect\Handler;

use App\Service\ActionCardEffect\ActionCardEffectHandlerInterface;
use App\Service\ActionCardEffect\ActionCardEffectResult;
use App\Entity\ActionCard;
use App\Entity\Player;
use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Handler for "Poupée démoniaque" card
 * Effect: Target a player, roll d6. 1-4: deal 3 damage. 5-6: take 3 damage yourself
 */
#[AutoconfigureTag('app.action_card_effect_handler')]
class PoupeeDemoniaqueHandler implements ActionCardEffectHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function supports(ActionCard $card): bool
    {
        return $card->getName() === 'Poupée démoniaque';
    }

    public function getRequiredContext(): array
    {
        return ['targetPlayerId', 'diceRoll'];
    }

    public function execute(ActionCard $card, Player $player, Game $game, array $context = []): ActionCardEffectResult
    {
        $targetPlayerId = $context['targetPlayerId'];
        $diceRoll = $context['diceRoll'];

        if ($diceRoll < 1 || $diceRoll > 6) {
            return ActionCardEffectResult::failure('Invalid dice roll. Must be between 1 and 6.');
        }

        $targetPlayer = null;
        foreach ($game->getPlayers() as $p) {
            if ($p->getId() === $targetPlayerId) {
                $targetPlayer = $p;
                break;
            }
        }

        if (!$targetPlayer) {
            return ActionCardEffectResult::failure('Target player not found');
        }

        if ($diceRoll >= 1 && $diceRoll <= 4) {
            $maxDamage = $targetPlayer->getCharacterCard()?->getMaxDamage() ?? 14;
            $damageBefore = $targetPlayer->getCurrentDamage();
            $damageAfter = min($maxDamage, $damageBefore + 3);
            $targetPlayer->setCurrentDamage($damageAfter);

            $this->entityManager->persist($targetPlayer);
            $this->entityManager->flush();

            return ActionCardEffectResult::success(
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
                    'damage_after' => $damageAfter
                ]
            );
        }

        $maxDamage = $player->getCharacterCard()?->getMaxDamage() ?? 14;
        $damageBefore = $player->getCurrentDamage();
        $damageAfter = min($maxDamage, $damageBefore + 3);
        $player->setCurrentDamage($damageAfter);

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return ActionCardEffectResult::success(
            sprintf('%s rolled %d and took 3 damage themselves!',
                $player->getUser()->getUsername(),
                $diceRoll
            ),
            [
                'dice_roll' => $diceRoll,
                'outcome' => 'self_damaged',
                'player_id' => $player->getId(),
                'damage_before' => $damageBefore,
                'damage_after' => $damageAfter
            ]
        );
    }
}
