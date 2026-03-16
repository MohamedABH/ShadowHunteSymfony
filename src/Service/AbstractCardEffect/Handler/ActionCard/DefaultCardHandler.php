<?php

namespace App\Service\AbstractCardEffect\Handler\ActionCard;

use App\Service\AbstractCardEffect\AbstractCardEffectHandlerInterface;
use App\Service\AbstractCardEffect\AbstractCardEffectResult;
use App\Entity\AbstractCard;
use App\Entity\ActionCard;
use App\Entity\Player;
use App\Entity\Game;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Default handler for cards without specific implementation
 * This should be registered last (lowest priority) in the service container
 */
#[AutoconfigureTag('app.abstract_card_effect_handler', ['priority' => -100])]
class DefaultCardHandler implements AbstractCardEffectHandlerInterface
{
    public function supports(AbstractCard $card): bool
    {
        return true;
    }

    public function getRequiredContext(): array
    {
        return [];
    }

    public function execute(AbstractCard $card, Player $player, Game $game, array $context = []): AbstractCardEffectResult
    {
        return AbstractCardEffectResult::success(
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
