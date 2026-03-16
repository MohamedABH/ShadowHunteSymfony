<?php

namespace App\Service\AbstractCardEffect\Handler\ActionCard;

use App\Service\AbstractCardEffect\AbstractCardEffectHandlerInterface;
use App\Service\AbstractCardEffect\AbstractCardEffectResult;
use App\Entity\AbstractCard;
use App\Entity\ActionCard;
use App\Entity\Player;
use App\Entity\Game;
use App\Service\DamageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Handler for "Eau bénite" card
 * Effect: Heal 2 damage immediately
 */
#[AutoconfigureTag('app.abstract_card_effect_handler')]
class EauBeniteHandler implements AbstractCardEffectHandlerInterface
{
    public function __construct(
        private readonly DamageService $damageService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function supports(AbstractCard $card): bool
    {
        return $card instanceof ActionCard && $card->getName() === 'Eau bénite';
    }

    public function getRequiredContext(): array
    {
        return [];
    }

    public function execute(AbstractCard $card, Player $player, Game $game, array $context = []): AbstractCardEffectResult
    {
        if (!$card instanceof ActionCard) {
            return AbstractCardEffectResult::failure('Unsupported card type for EauBeniteHandler');
        }

        $healResult = $this->damageService->heal($player, 2);
        $currentDamage = $healResult['before'];
        $newDamage = $healResult['after'];
        $healAmount = $healResult['healed'];

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return AbstractCardEffectResult::success(
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
