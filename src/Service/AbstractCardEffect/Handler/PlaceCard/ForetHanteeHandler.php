<?php

namespace App\Service\AbstractCardEffect\Handler\PlaceCard;

use App\Entity\AbstractCard;
use App\Entity\Game;
use App\Entity\PlaceCard;
use App\Entity\Player;
use App\Repository\PlayerRepository;
use App\Service\AbstractCardEffect\AbstractCardEffectHandlerInterface;
use App\Service\AbstractCardEffect\AbstractCardEffectResult;
use App\Service\DamageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.abstract_card_effect_handler')]
class ForetHanteeHandler implements AbstractCardEffectHandlerInterface
{
    public function __construct(
        private readonly PlayerRepository $playerRepository,
        private readonly DamageService $damageService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(AbstractCard $card): bool
    {
        return $card instanceof PlaceCard && $card->getName() === 'Forêt hantée';
    }

    public function getRequiredContext(): array
    {
        return ['targetPlayerId', 'outcome'];
    }

    public function execute(AbstractCard $card, Player $player, Game $game, array $context = []): AbstractCardEffectResult
    {
        if (!$this->supports($card)) {
            return AbstractCardEffectResult::failure('Unsupported card type for ForetHanteeHandler');
        }

        $targetPlayerId = (int) ($context['targetPlayerId'] ?? 0);
        $outcome = strtolower((string) ($context['outcome'] ?? ''));
        if (!in_array($outcome, ['damage', 'heal'], true)) {
            return AbstractCardEffectResult::requiresAction(
                'Forêt hantée requires an outcome.',
                ['required' => ['outcome'], 'allowed' => ['damage', 'heal']]
            );
        }

        $targetPlayer = $this->playerRepository->findOneByGameAndId($game, $targetPlayerId);
        if (!$targetPlayer) {
            return AbstractCardEffectResult::failure('Target player not found in this game.');
        }

        if ($outcome === 'damage') {
            $damageResult = $this->damageService->applyDamage($targetPlayer, 2);
            $before = $damageResult['before'];
            $after = $damageResult['after'];
            $targetKnockedOut = $this->damageService->enforceKnockout($targetPlayer);
        } else {
            $healResult = $this->damageService->heal($targetPlayer, 1);
            $before = $healResult['before'];
            $after = $healResult['after'];
            $targetKnockedOut = false;
        }

        $this->entityManager->persist($targetPlayer);
        $this->entityManager->flush();

        return AbstractCardEffectResult::success(
            sprintf('%s used Forêt hantée on %s (%s).', $player->getUser()->getUsername(), $targetPlayer->getUser()->getUsername(), $outcome),
            [
                'player_id' => $player->getId(),
                'targetPlayerId' => $targetPlayer->getId(),
                'outcome' => $outcome,
                'damageBefore' => $before,
                'damageAfter' => $after,
                'targetKnockedOut' => $targetKnockedOut,
            ]
        );
    }

}
