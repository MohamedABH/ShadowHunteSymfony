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
 * Handler for "Chauve-souris vampire" card
 * Effect: Deal 2 damage to target player, then heal 1 damage to yourself
 */
#[AutoconfigureTag('app.card_effect_handler')]
class ChauveSourisVampireHandler implements CardEffectHandlerInterface
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
        return ['targetPlayerId']; // Requires a target player
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
        
        // Deal 2 damage to target
        $targetMaxDamage = $targetPlayer->getCharacterCard()?->getMaxDamage() ?? 14;
        $targetDamageBefore = $targetPlayer->getCurrentDamage();
        $targetDamageAfter = min($targetMaxDamage, $targetDamageBefore + 2);
        $targetPlayer->setCurrentDamage($targetDamageAfter);
        
        // Heal 1 damage to player
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
        
        return CardEffectResult::success(
            sprintf('%s dealt 2 damage to %s and healed 1 damage', 
                $player->getUser()->getUsername(),
                $targetPlayer->getUser()->getUsername()
            ),
            $changes
        );
    }
}
