<?php

namespace App\Service\CardEffect;

use App\Entity\ActionCard;
use App\Entity\Player;
use App\Entity\Game;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * Service to manage and execute card effects
 */
class CardEffectService
{
    /** @var iterable<CardEffectHandlerInterface> */
    private iterable $handlers;
    
    public function __construct(
        #[TaggedIterator('app.card_effect_handler')] iterable $handlers
    ) {
        $this->handlers = $handlers;
    }
    
    /**
     * Execute a card effect
     * 
     * @param ActionCard $card
     * @param Player $player
     * @param Game $game
     * @param array $context
     * @return CardEffectResult
     * @throws \RuntimeException if no handler found
     */
    public function executeCardEffect(ActionCard $card, Player $player, Game $game, array $context = []): CardEffectResult
    {
        $handler = $this->getHandler($card);
        
        if (!$handler) {
            throw new \RuntimeException(sprintf('No handler found for card: %s', $card->getName()));
        }
        
        // Validate required context
        $requiredContext = $handler->getRequiredContext();
        $missingContext = array_diff($requiredContext, array_keys($context));
        
        if (!empty($missingContext)) {
            return CardEffectResult::requiresAction(
                'Additional information required',
                ['required' => $missingContext]
            );
        }
        
        return $handler->execute($card, $player, $game, $context);
    }
    
    /**
     * Get the handler for a specific card
     * 
     * @param ActionCard $card
     * @return CardEffectHandlerInterface|null
     */
    private function getHandler(ActionCard $card): ?CardEffectHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($card)) {
                return $handler;
            }
        }
        
        return null;
    }
    
    /**
     * Get required context for a card
     * 
     * @param ActionCard $card
     * @return array
     */
    public function getRequiredContext(ActionCard $card): array
    {
        $handler = $this->getHandler($card);
        return $handler ? $handler->getRequiredContext() : [];
    }
}
