<?php

namespace App\Service\ActionCardEffect;

use App\Entity\ActionCard;
use App\Entity\Player;
use App\Entity\Game;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * Service to manage and execute action card effects
 */
class ActionCardEffectService
{
    /** @var iterable<ActionCardEffectHandlerInterface> */
    private iterable $handlers;

    public function __construct(
        #[TaggedIterator('app.action_card_effect_handler')] iterable $handlers
    ) {
        $this->handlers = $handlers;
    }

    /**
     * Execute an action card effect
     *
     * @param ActionCard $card
     * @param Player $player
     * @param Game $game
     * @param array $context
     * @return ActionCardEffectResult
     * @throws \RuntimeException if no handler found
     */
    public function executeCardEffect(ActionCard $card, Player $player, Game $game, array $context = []): ActionCardEffectResult
    {
        $handler = $this->getHandler($card);

        if (!$handler) {
            throw new \RuntimeException(sprintf('No handler found for card: %s', $card->getName()));
        }

        $requiredContext = $handler->getRequiredContext();
        $missingContext = array_diff($requiredContext, array_keys($context));

        if (!empty($missingContext)) {
            return ActionCardEffectResult::requiresAction(
                'Additional information required',
                ['required' => $missingContext]
            );
        }

        return $handler->execute($card, $player, $game, $context);
    }

    /**
     * @param ActionCard $card
     * @return ActionCardEffectHandlerInterface|null
     */
    private function getHandler(ActionCard $card): ?ActionCardEffectHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($card)) {
                return $handler;
            }
        }

        return null;
    }

    /**
     * @param ActionCard $card
     * @return array
     */
    public function getRequiredContext(ActionCard $card): array
    {
        $handler = $this->getHandler($card);
        return $handler ? $handler->getRequiredContext() : [];
    }
}
