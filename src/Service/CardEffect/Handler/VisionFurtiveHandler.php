<?php

namespace App\Service\CardEffect\Handler;

use App\Service\CardEffect\CardEffectHandlerInterface;
use App\Service\CardEffect\CardEffectResult;
use App\Entity\ActionCard;
use App\Entity\Player;
use App\Entity\Game;
use App\Enum\CharacterCardType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Handler for "Vision furtive" card
 * Effect: "I think you are Hunter or Shadow. If so, you must: give me equipment OR take 1 damage"
 */
#[AutoconfigureTag('app.card_effect_handler')]
class VisionFurtiveHandler implements CardEffectHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }
    
    public function supports(ActionCard $card): bool
    {
        return $card->getName() === 'Vision furtive';
    }
    
    public function getRequiredContext(): array
    {
        // First, need target player, then need their response
        return ['targetPlayerId'];
    }
    
    public function execute(ActionCard $card, Player $player, Game $game, array $context = []): CardEffectResult
    {
        $targetPlayerId = $context['targetPlayerId'];
        
        // Find target player
        $targetPlayer = null;
        foreach ($game->getPlayers() as $p) {
            if ($p->getId() === $targetPlayerId) {
                $targetPlayer = $p;
                break;
            }
        }
        
        if (!$targetPlayer) {
            return CardEffectResult::failure('Target player not found');
        }
        
        if ($targetPlayer->getId() === $player->getId()) {
            return CardEffectResult::failure('You cannot target yourself');
        }
        
        // Check if target is Hunter or Shadow
        $targetType = $targetPlayer->getCharacterCard()?->getType();
        $isHunterOrShadow = in_array($targetType, [CharacterCardType::HUNTER, CharacterCardType::SHADOW], true);
        
        if (!$isHunterOrShadow) {
            // Target is Neutral, card has no effect
            return CardEffectResult::success(
                sprintf('%s was not Hunter or Shadow. No effect.', $targetPlayer->getUser()->getUsername()),
                ['correct_guess' => false, 'target_player_id' => $targetPlayer->getId()]
            );
        }
        
        // Target is Hunter or Shadow, they must choose
        if (!isset($context['targetChoice'])) {
            // Need target to make a choice
            return CardEffectResult::requiresAction(
                sprintf('%s is Hunter or Shadow! They must choose.', $targetPlayer->getUser()->getUsername()),
                [
                    'required' => ['targetChoice'],
                    'choices' => ['give_equipment', 'take_damage'],
                    'target_player_id' => $targetPlayer->getId()
                ]
            );
        }
        
        $choice = $context['targetChoice'];
        
        if ($choice === 'give_equipment') {
            // Need equipment card selection
            if (!isset($context['equipmentCardId'])) {
                return CardEffectResult::requiresAction(
                    'Select an equipment card to give',
                    [
                        'required' => ['equipmentCardId'],
                        'target_player_id' => $targetPlayer->getId()
                    ]
                );
            }
            
            // Transfer equipment (implementation depends on your equipment system)
            return CardEffectResult::success(
                sprintf('%s gave an equipment card to %s', 
                    $targetPlayer->getUser()->getUsername(),
                    $player->getUser()->getUsername()
                ),
                [
                    'choice' => 'equipment',
                    'equipment_id' => $context['equipmentCardId'],
                    'from_player' => $targetPlayer->getId(),
                    'to_player' => $player->getId()
                ]
            );
        } else {
            // Take 1 damage
            $maxDamage = $targetPlayer->getCharacterCard()?->getMaxDamage() ?? 14;
            $damageBefore = $targetPlayer->getCurrentDamage();
            $damageAfter = min($maxDamage, $damageBefore + 1);
            $targetPlayer->setCurrentDamage($damageAfter);
            
            $this->entityManager->persist($targetPlayer);
            $this->entityManager->flush();
            
            return CardEffectResult::success(
                sprintf('%s took 1 damage (from %d to %d)', 
                    $targetPlayer->getUser()->getUsername(),
                    $damageBefore,
                    $damageAfter
                ),
                [
                    'choice' => 'damage',
                    'target_player_id' => $targetPlayer->getId(),
                    'damage_before' => $damageBefore,
                    'damage_after' => $damageAfter
                ]
            );
        }
    }
}
