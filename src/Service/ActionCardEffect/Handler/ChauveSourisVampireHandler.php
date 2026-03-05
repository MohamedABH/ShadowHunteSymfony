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
 * Handler for "Chauve-souris vampire" card
 * Effect: Deal 2 damage to target player, then heal 1 damage to yourself
 */
#[AutoconfigureTag('app.action_card_effect_handler')]
class ChauveSourisVampireHandler implements ActionCardEffectHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function supports(ActionCard $card): bool
    {
        return $card->getName() === 'Chauve-souris vampire';
    }

    public function getRequiredContext(): array
    {
        return ['targetPlayerId'];
    }

    public function execute(ActionCard $card, Player $player, Game $game, array $context = []): ActionCardEffectResult
    {
        $targetPlayerId = $context['targetPlayerId'];

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

        if ($targetPlayer->getId() === $player->getId()) {
            return ActionCardEffectResult::failure('You cannot target yourself');
        }

        $targetMaxDamage = $targetPlayer->getCharacterCard()?->getMaxDamage() ?? 14;
        $targetDamageBefore = $targetPlayer->getCurrentDamage();
        $targetDamageAfter = min($targetMaxDamage, $targetDamageBefore + 2);
        $targetPlayer->setCurrentDamage($targetDamageAfter);

        $playerDamageBefore = $player->getCurrentDamage();
        $playerDamageAfter = max(0, $playerDamageBefore - 1);
        $player->setCurrentDamage($playerDamageAfter);

        $this->entityManager->persist($targetPlayer);
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        $changes = [
            'attacker' => [
                'player_id' => $player->getId(),
                'damage_before' => $playerDamageBefore,
                'damage_after' => $playerDamageAfter,
                'healed' => $playerDamageBefore - $playerDamageAfter
            ],
            'target' => [
                'player_id' => $targetPlayer->getId(),
                'damage_before' => $targetDamageBefore,
                'damage_after' => $targetDamageAfter,
                'dealt' => $targetDamageAfter - $targetDamageBefore
            ]
        ];

        return ActionCardEffectResult::success(
            sprintf('%s dealt 2 damage to %s and healed 1 damage',
                $player->getUser()->getUsername(),
                $targetPlayer->getUser()->getUsername()
            ),
            $changes
        );
    }
}
