<?php

namespace App\Service\CardEffect\Handler;

use App\Service\CardEffect\CardEffectHandlerInterface;
use App\Service\CardEffect\CardEffectResult;
use App\Entity\ActionCard;
use App\Entity\Player;
use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Handler for "Eau bénite" card
 * Effect: Heal 2 damage immediately
 */
#[AutoconfigureTag('app.card_effect_handler')]
class EauBeniteHandler implements CardEffectHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }
    
    public function supports(ActionCard $card): bool
    {
        return $card->getName() === 'Eau bénite';
    }
    
    public function getRequiredContext(): array
    {
        return []; // No additional context needed
    }
    
    public function execute(ActionCard $card, Player $player, Game $game, array $context = []): CardEffectResult
    {
        $currentDamage = $player->getCurrentDamage();
        $healAmount = min(2, $currentDamage); // Can't heal below 0
        
        $newDamage = max(0, $currentDamage - 2);
        $player->setCurrentDamage($newDamage);
        
        $this->entityManager->persist($player);
        $this->entityManager->flush();
        
        return CardEffectResult::success(
            sprintf('%s has been healed for %d damage (from %d to %d)', 
                $player->getUser()->getUsername(), 
                $healAmount, 
                $currentDamage, 
                $newDamage
            ),
            [
                'player_id' => $player->getId(),
                'damage_before' => $currentDamage,
                'damage_after' => $newDamage,
                'healed' => $healAmount
            ]
        );
    }
}
