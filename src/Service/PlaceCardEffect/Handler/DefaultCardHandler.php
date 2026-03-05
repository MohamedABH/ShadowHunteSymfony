<?php

namespace App\Service\ActionCardEffect\Handler;

use App\Service\ActionCardEffect\ActionCardEffectHandlerInterface;
use App\Service\ActionCardEffect\ActionCardEffectResult;
use App\Entity\ActionCard;
use App\Entity\Player;
use App\Entity\Game;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Default handler for cards without specific implementation
 * This should be registered last (lowest priority) in the service container
 */
#[AutoconfigureTag('app.action_card_effect_handler', ['priority' => -100])]
class DefaultCardHandler implements ActionCardEffectHandlerInterface
{
    public function supports(ActionCard $card): bool
    {
        return true;
    }

    public function getRequiredContext(): array
    {
        return [];
    }

    public function execute(ActionCard $card, Player $player, Game $game, array $context = []): ActionCardEffectResult
    {
        return ActionCardEffectResult::success(
            sprintf('Card "%s" played by %s (not yet implemented)',
                $card->getName(),
                $player->getUser()->getUsername()
            ),
            [
                'card_name' => $card->getName(),
                'player_id' => $player->getId(),
                'implemented' => false
            ]
        );
    }
}
