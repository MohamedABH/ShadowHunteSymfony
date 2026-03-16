<?php

namespace App\Service\AbstractCardEffect\Handler\ActionCard;

use App\Service\AbstractCardEffect\AbstractCardEffectHandlerInterface;
use App\Service\AbstractCardEffect\AbstractCardEffectResult;
use App\Entity\AbstractCard;
use App\Entity\ActionCard;
use App\Entity\Player;
use App\Entity\Game;
use App\Enum\CharacterCardType;
use App\Service\DamageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Handler for "Vision furtive" card
 * Effect: "I think you are Hunter or Shadow. If so, you must: give me equipment OR take 1 damage"
 */
#[AutoconfigureTag('app.abstract_card_effect_handler')]
class VisionFurtiveHandler implements AbstractCardEffectHandlerInterface
{
    public function __construct(
        private readonly DamageService $damageService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function supports(AbstractCard $card): bool
    {
        return $card instanceof ActionCard && $card->getName() === 'Vision furtive';
    }

    public function getRequiredContext(): array
    {
        return ['targetPlayerId'];
    }

    public function execute(AbstractCard $card, Player $player, Game $game, array $context = []): AbstractCardEffectResult
    {
        if (!$card instanceof ActionCard) {
            return AbstractCardEffectResult::failure('Unsupported card type for VisionFurtiveHandler');
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

        $targetType = $targetPlayer->getCharacterCard()?->getType();
        $isHunterOrShadow = in_array($targetType, [CharacterCardType::HUNTER, CharacterCardType::SHADOW], true);

        if (!$isHunterOrShadow) {
            return AbstractCardEffectResult::success(
                sprintf('%s was not Hunter or Shadow. No effect.', $targetPlayer->getUser()->getUsername()),
                ['correct_guess' => false, 'target_player_id' => $targetPlayer->getId()]
            );
        }

        if (!isset($context['targetChoice'])) {
            return AbstractCardEffectResult::requiresAction(
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
            if (!isset($context['equipmentCardId'])) {
                return AbstractCardEffectResult::requiresAction(
                    'Select an equipment card to give',
                    [
                        'required' => ['equipmentCardId'],
                        'target_player_id' => $targetPlayer->getId()
                    ]
                );
            }

            return AbstractCardEffectResult::success(
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
        }

        $damageResult = $this->damageService->applyDamage($targetPlayer, 1);
        $damageBefore = $damageResult['before'];
        $damageAfter = $damageResult['after'];
        $targetKnockedOut = $this->damageService->enforceKnockout($targetPlayer);

        $this->entityManager->persist($targetPlayer);
        $this->entityManager->flush();

        return AbstractCardEffectResult::success(
            sprintf('%s took 1 damage (from %d to %d)',
                $targetPlayer->getUser()->getUsername(),
                $damageBefore,
                $damageAfter
            ),
            [
                'choice' => 'damage',
                'target_player_id' => $targetPlayer->getId(),
                'damage_before' => $damageBefore,
                'damage_after' => $damageAfter,
                'target_knocked_out' => $targetKnockedOut,
            ]
        );
    }
}
