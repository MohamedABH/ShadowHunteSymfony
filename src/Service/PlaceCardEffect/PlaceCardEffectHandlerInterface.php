<?php

namespace App\Service\PlaceCardEffect;

use App\Entity\PlaceCard;
use App\Entity\Player;
use App\Entity\Game;

/**
 * Interface for handling place card effects
 */
interface PlaceCardEffectHandlerInterface
{
    /**
     * Execute the card's effect
     *
     * @param PlaceCard $card The card being played
     * @param Player $player The player who played the card
     * @param Game $game The current game
     * @param array $context Additional context (target player, dice results, choices, etc.)
     * @return PlaceCardEffectResult Result of the effect execution
     */
    public function execute(PlaceCard $card, Player $player, Game $game, array $context = []): PlaceCardEffectResult;

    /**
     * Check if this handler supports the given card
     *
     * @param PlaceCard $card
     * @return bool
     */
    public function supports(PlaceCard $card): bool;

    /**
     * Get required context parameters for this card
     * Returns an array of required context keys (e.g., ['targetPlayer', 'diceRoll'])
     *
     * @return array
     */
    public function getRequiredContext(): array;
}
