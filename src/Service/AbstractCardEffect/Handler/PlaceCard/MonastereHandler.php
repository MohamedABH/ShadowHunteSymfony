<?php

namespace App\Service\AbstractCardEffect\Handler\PlaceCard;

use App\Entity\AbstractCard;
use App\Entity\ActionCard;
use App\Entity\Game;
use App\Entity\PlaceCard;
use App\Entity\Player;
use App\Enum\LocationEnum;
use App\Service\ActionCardResolutionService;
use App\Service\AbstractCardEffect\AbstractCardEffectHandlerInterface;
use App\Service\AbstractCardEffect\AbstractCardEffectResult;
use App\Service\DeckService;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

#[AutoconfigureTag('app.abstract_card_effect_handler')]
class MonastereHandler implements AbstractCardEffectHandlerInterface
{
    /** @var iterable<AbstractCardEffectHandlerInterface> */
    private iterable $handlers;

    public function __construct(
        private readonly DeckService $deckService,
        private readonly ActionCardResolutionService $actionCardResolutionService,
        #[TaggedIterator('app.abstract_card_effect_handler')] iterable $handlers,
    ) {
        $this->handlers = $handlers;
    }

    public function supports(AbstractCard $card): bool
    {
        return $card instanceof PlaceCard && $card->getName() === 'Monastère';
    }

    public function getRequiredContext(): array
    {
        return [];
    }

    public function execute(AbstractCard $card, Player $player, Game $game, array $context = []): AbstractCardEffectResult
    {
        if (!$this->supports($card)) {
            return AbstractCardEffectResult::failure('Unsupported card type for MonastereHandler');
        }

        $drawnLocation = $this->deckService->drawCardFromDeck($game, $player, LocationEnum::LIGHT_DECK);
        if (!$drawnLocation || !$drawnLocation->getActionCard()) {
            return AbstractCardEffectResult::failure('No Light card available to draw.');
        }

        $drawnCard = $drawnLocation->getActionCard();
        $handler = $this->resolveHandlerForCard($drawnCard);
        if (!$handler) {
            return AbstractCardEffectResult::failure(sprintf('No handler found for drawn card: %s', $drawnCard->getName()));
        }

        $drawnCardContext = is_array($context['drawnCardContext'] ?? null) ? $context['drawnCardContext'] : [];
        $requiredContext = $handler->getRequiredContext();
        $missingContext = array_diff($requiredContext, array_keys($drawnCardContext));

        if (!empty($missingContext)) {
            return AbstractCardEffectResult::requiresAction(
                sprintf('Light card "%s" drawn. Additional information is required to execute it.', $drawnCard->getName()),
                [
                    'drawnCard' => [
                        'id' => $drawnCard->getId(),
                        'name' => $drawnCard->getName(),
                    ],
                    'required' => array_values($missingContext),
                ]
            );
        }

        $drawnCardResult = $handler->execute($drawnCard, $player, $game, $drawnCardContext);
        if (!$drawnCardResult->isSuccess()) {
            return AbstractCardEffectResult::failure($drawnCardResult->getMessage());
        }

        if ($drawnCardResult->hasPendingActions()) {
            return AbstractCardEffectResult::requiresAction(
                sprintf('Light card "%s" drawn. Follow-up action required.', $drawnCard->getName()),
                $drawnCardResult->getPendingActions() ?? []
            );
        }

        $this->actionCardResolutionService->discardIfNeeded($drawnCard, $player, $game);

        return AbstractCardEffectResult::success(
            sprintf('%s drew and executed light card "%s"', $player->getUser()->getUsername(), $drawnCard->getName()),
            [
                'player_id' => $player->getId(),
                'effect' => 'draw_and_execute_light_card',
                'drawnCard' => [
                    'id' => $drawnCard->getId(),
                    'name' => $drawnCard->getName(),
                ],
                'drawnCardEffect' => $drawnCardResult->getChanges(),
            ]
        );
    }

    private function resolveHandlerForCard(ActionCard $card): ?AbstractCardEffectHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler === $this) {
                continue;
            }

            if ($handler->supports($card)) {
                return $handler;
            }
        }

        return null;
    }
}