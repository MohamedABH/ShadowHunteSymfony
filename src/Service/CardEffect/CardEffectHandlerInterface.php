<?php

namespace App\Service\CardEffect;

use App\Entity\ActionCard;
use App\Entity\Player;
use App\Entity\Game;

/**
 * Interface for handling action card effects
 */
interface CardEffectHandlerInterface
{
    /**
     * Execute the card's effect
     * 
     * @param ActionCard $card The card being played
     * @param Player $player The player who played the card
     * @param Game $game The current game
     * @param array $context Additional context (target player, dice results, choices, etc.)
     * @return CardEffectResult Result of the effect execution
     */
    public function execute(ActionCard $card, Player $player, Game $game, array $context = []): CardEffectResult;
    
    /**
     * Check if this handler supports the given card
     * 
     * @param ActionCard $card
     * @return bool
     */
    public function supports(ActionCard $card): bool;
    
    /**
     * Get required context parameters for this card
     * Returns an array of required context keys (e.g., ['targetPlayer', 'diceRoll'])
     * 
     * @return array
     */
    public function getRequiredContext(): array;
}
