<?php

namespace App\Service\ActionCardEffect\Handler;

use App\Service\ActionCardEffect\ActionCardEffectHandlerInterface;
use App\Service\ActionCardEffect\ActionCardEffectResult;
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
#[AutoconfigureTag('app.action_card_effect_handler')]
class VisionFurtiveHandler implements ActionCardEffectHandlerInterface
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
        return ['targetPlayerId'];
    }

    public function execute(ActionCard $card, Player $player, Game $game, array $context = []): ActionCardEffectResult
    {
        $targetPlayerId = $context['targetPlayerId'];

        $targetPlayer = null;
        foreach ($game->getPlayers() as $p) {
            if ($p->getId() === $targetPlayerId) {
                $targetPlayer = $p;
                break;
            }
        }

        if (!$targetPlayer) {
            return ActionCardEffectResult::failure('Target player not found');
        }

        if ($targetPlayer->getId() === $player->getId()) {
            return ActionCardEffectResult::failure('You cannot target yourself');
        }

        $targetType = $targetPlayer->getCharacterCard()?->getType();
        $isHunterOrShadow = in_array($targetType, [CharacterCardType::HUNTER, CharacterCardType::SHADOW], true);

        if (!$isHunterOrShadow) {
            return ActionCardEffectResult::success(
                sprintf('%s was not Hunter or Shadow. No effect.', $targetPlayer->getUser()->getUsername()),
                ['correct_guess' => false, 'target_player_id' => $targetPlayer->getId()]
            );
        }

        if (!isset($context['targetChoice'])) {
            return ActionCardEffectResult::requiresAction(
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
                return ActionCardEffectResult::requiresAction(
                    'Select an equipment card to give',
                    [
                        'required' => ['equipmentCardId'],
                        'target_player_id' => $targetPlayer->getId()
                    ]
                );
            }

            return ActionCardEffectResult::success(
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

        $maxDamage = $targetPlayer->getCharacterCard()?->getMaxDamage() ?? 14;
        $damageBefore = $targetPlayer->getCurrentDamage();
        $damageAfter = min($maxDamage, $damageBefore + 1);
        $targetPlayer->setCurrentDamage($damageAfter);

        $this->entityManager->persist($targetPlayer);
        $this->entityManager->flush();

        return ActionCardEffectResult::success(
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
