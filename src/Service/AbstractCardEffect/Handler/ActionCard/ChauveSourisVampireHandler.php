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
 * Handler for "Chauve-souris vampire" card
 * Effect: Deal 2 damage to target player, then heal 1 damage to yourself
 */
#[AutoconfigureTag('app.abstract_card_effect_handler')]
class ChauveSourisVampireHandler implements AbstractCardEffectHandlerInterface
{
    public function __construct(
        private readonly DamageService $damageService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function supports(AbstractCard $card): bool
    {
        return $card instanceof ActionCard && $card->getName() === 'Chauve-souris vampire';
    }

    public function getRequiredContext(): array
    {
        return ['targetPlayerId'];
    }

    public function execute(AbstractCard $card, Player $player, Game $game, array $context = []): AbstractCardEffectResult
    {
        if (!$card instanceof ActionCard) {
            return AbstractCardEffectResult::failure('Unsupported card type for ChauveSourisVampireHandler');
        }

        $targetPlayerId = $context['targetPlayerId'];

        $targetPlayer = null;
        foreach ($game->getPlayers() as $p) {
            if ($p->getId() === $targetPlayerId) {
                $targetPlayer = $p;
                break;
            }
        }

        if (!$targetPlayer) {
            return AbstractCardEffectResult::failure('Target player not found');
        }

        if ($targetPlayer->getId() === $player->getId()) {
            return AbstractCardEffectResult::failure('You cannot target yourself');
        }

        $targetDamageResult = $this->damageService->applyDamage($targetPlayer, 2);
        $targetDamageBefore = $targetDamageResult['before'];
        $targetDamageAfter = $targetDamageResult['after'];
        $targetKnockedOut = $this->damageService->enforceKnockout($targetPlayer);

        $playerHealResult = $this->damageService->heal($player, 1);
        $playerDamageBefore = $playerHealResult['before'];
        $playerDamageAfter = $playerHealResult['after'];

        $this->entityManager->persist($targetPlayer);
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        $changes = [
            'attacker' => [
                'player_id' => $player->getId(),
                'damage_before' => $playerDamageBefore,
                'damage_after' => $playerDamageAfter,
                'healed' => $playerHealResult['healed']
            ],
            'target' => [
                'player_id' => $targetPlayer->getId(),
                'damage_before' => $targetDamageBefore,
                'damage_after' => $targetDamageAfter,
                'dealt' => $targetDamageAfter - $targetDamageBefore,
                'knocked_out' => $targetKnockedOut,
            ]
        ];

        return AbstractCardEffectResult::success(
            sprintf('%s dealt 2 damage to %s and healed 1 damage',
                $player->getUser()->getUsername(),
                $targetPlayer->getUser()->getUsername()
            ),
            $changes
        );
    }
}
