<?php

namespace App\Service\PlaceCardEffect;

use App\Entity\PlaceCard;
use App\Entity\Player;
use App\Entity\Game;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * Service to manage and execute place card effects
 */
class PlaceCardEffectService
{
    /** @var iterable<PlaceCardEffectHandlerInterface> */
    private iterable $handlers;

    public function __construct(
        #[TaggedIterator('app.place_card_effect_handler')] iterable $handlers
    ) {
        $this->handlers = $handlers;
    }

    /**
     * Execute a place card effect
     *
     * @param PlaceCard $card
     * @param Player $player
     * @param Game $game
     * @param array $context
     * @return PlaceCardEffectResult
     * @throws \RuntimeException if no handler found
     */
    public function executeCardEffect(PlaceCard $card, Player $player, Game $game, array $context = []): PlaceCardEffectResult
    {
        $handler = $this->getHandler($card);

        if (!$handler) {
            throw new \RuntimeException(sprintf('No handler found for card: %s', $card->getName()));
        }

        $requiredContext = $handler->getRequiredContext();
        $missingContext = array_diff($requiredContext, array_keys($context));

        if (!empty($missingContext)) {
            return PlaceCardEffectResult::requiresAction(
                'Additional information required',
                ['required' => $missingContext]
            );
        }

        return $handler->execute($card, $player, $game, $context);
    }

    /**
     * @param PlaceCard $card
     * @return PlaceCardEffectHandlerInterface|null
     */
    private function getHandler(PlaceCard $card): ?PlaceCardEffectHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($card)) {
                return $handler;
            }
        }

        return null;
    }

    /**
     * @param PlaceCard $card
     * @return array
     */
    public function getRequiredContext(PlaceCard $card): array
    {
        $handler = $this->getHandler($card);
        return $handler ? $handler->getRequiredContext() : [];
    }
}
