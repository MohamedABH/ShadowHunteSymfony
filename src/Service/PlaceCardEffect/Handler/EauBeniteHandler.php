<?php

namespace App\Service\ActionCardEffect\Handler;

use App\Service\ActionCardEffect\ActionCardEffectHandlerInterface;
use App\Service\ActionCardEffect\ActionCardEffectResult;
use App\Entity\ActionCard;
use App\Entity\Player;
use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Handler for "Eau bénite" card
 * Effect: Heal 2 damage immediately
 */
#[AutoconfigureTag('app.action_card_effect_handler')]
class EauBeniteHandler implements ActionCardEffectHandlerInterface
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
        return [];
    }

    public function execute(ActionCard $card, Player $player, Game $game, array $context = []): ActionCardEffectResult
    {
        $currentDamage = $player->getCurrentDamage();
        $healAmount = min(2, $currentDamage);

        $newDamage = max(0, $currentDamage - 2);
        $player->setCurrentDamage($newDamage);

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return ActionCardEffectResult::success(
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
