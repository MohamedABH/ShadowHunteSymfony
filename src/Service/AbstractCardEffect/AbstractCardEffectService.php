<?php

namespace App\Service\AbstractCardEffect;

use App\Entity\AbstractCard;
use App\Entity\ActionCard;
use App\Entity\Game;
use App\Entity\Player;
use App\Service\ActionCardResolutionService;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class AbstractCardEffectService
{
    /** @var iterable<AbstractCardEffectHandlerInterface> */
    private iterable $handlers;

    public function __construct(
        private readonly ActionCardResolutionService $actionCardResolutionService,
        #[TaggedIterator('app.abstract_card_effect_handler')] iterable $handlers
    ) {
        $this->handlers = $handlers;
    }

    public function executeCardEffect(AbstractCard $card, Player $player, Game $game, array $context = []): AbstractCardEffectResult
    {
        $handler = $this->getHandler($card);

        if (!$handler) {
            throw new \RuntimeException(sprintf('No handler found for card: %s', $card->getName()));
        }

        $requiredContext = $handler->getRequiredContext();
        $missingContext = array_diff($requiredContext, array_keys($context));

        if (!empty($missingContext)) {
            return AbstractCardEffectResult::requiresAction(
                'Additional information required',
                ['required' => $missingContext]
            );
        }

        $result = $handler->execute($card, $player, $game, $context);

        if ($result->isSuccess() && !$result->hasPendingActions() && $card instanceof ActionCard) {
            $this->actionCardResolutionService->discardIfNeeded($card, $player, $game);
        }

        return $result;
    }

    private function getHandler(AbstractCard $card): ?AbstractCardEffectHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($card)) {
                return $handler;
            }
        }

        return null;
    }

    public function getRequiredContext(AbstractCard $card): array
    {
        $handler = $this->getHandler($card);
        return $handler ? $handler->getRequiredContext() : [];
    }
}
