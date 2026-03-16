<?php

namespace App\Service\AbstractCardEffect\Handler\PlaceCard;

use App\Entity\AbstractCard;
use App\Entity\Game;
use App\Entity\Location;
use App\Entity\PlaceCard;
use App\Entity\Player;
use App\Enum\LocationEnum;
use App\Repository\PlayerRepository;
use App\Service\AbstractCardEffect\AbstractCardEffectHandlerInterface;
use App\Service\AbstractCardEffect\AbstractCardEffectResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.abstract_card_effect_handler')]
class SanctuaireAncienHandler implements AbstractCardEffectHandlerInterface
{
    public function __construct(
        private readonly PlayerRepository $playerRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(AbstractCard $card): bool
    {
        return $card instanceof PlaceCard && $card->getName() === 'Sanctuaire ancien';
    }

    public function getRequiredContext(): array
    {
        return ['targetPlayerId'];
    }

    public function execute(AbstractCard $card, Player $player, Game $game, array $context = []): AbstractCardEffectResult
    {
        if (!$this->supports($card)) {
            return AbstractCardEffectResult::failure('Unsupported card type for SanctuaireAncienHandler');
        }

        $targetPlayerId = (int) ($context['targetPlayerId'] ?? 0);
        $targetPlayer = $this->playerRepository->findOneByGameAndId($game, $targetPlayerId);

        if (!$targetPlayer || $targetPlayer->getId() === $player->getId()) {
            return AbstractCardEffectResult::failure('Invalid target player for Sanctuaire ancien.');
        }

        $equipments = array_values(array_filter(
            $targetPlayer->getCards()->toArray(),
            fn (Location $location) => $location->getLocation() === LocationEnum::IN_PLAY
        ));

        if (count($equipments) === 0) {
            return AbstractCardEffectResult::success(
                sprintf('%s has no equipment to steal.', $targetPlayer->getUser()->getUsername()),
                [
                    'player_id' => $player->getId(),
                    'targetPlayerId' => $targetPlayer->getId(),
                    'stolenCardId' => null,
                ]
            );
        }

        $selectedLocation = null;
        $targetLocationId = isset($context['targetLocationId']) ? (int) $context['targetLocationId'] : null;
        if ($targetLocationId) {
            foreach ($equipments as $equipment) {
                if ($equipment->getId() === $targetLocationId) {
                    $selectedLocation = $equipment;
                    break;
                }
            }

            if (!$selectedLocation) {
                return AbstractCardEffectResult::failure('targetLocationId is not a valid equipment for the target player.');
            }
        } elseif (count($equipments) === 1) {
            $selectedLocation = $equipments[0];
        } else {
            return AbstractCardEffectResult::requiresAction(
                'Target player has multiple equipments. Choose one to steal.',
                [
                    'required' => ['targetLocationId'],
                    'equipments' => array_map(
                        fn (Location $location) => [
                            'locationId' => $location->getId(),
                            'cardId' => $location->getActionCard()?->getId(),
                            'cardName' => $location->getActionCard()?->getName(),
                        ],
                        $equipments
                    ),
                ]
            );
        }

        $selectedLocation->setPlayer($player);
        $selectedLocation->setLocation(LocationEnum::IN_PLAY);
        $this->entityManager->persist($selectedLocation);
        $this->entityManager->flush();

        return AbstractCardEffectResult::success(
            sprintf('%s stole "%s" from %s.', $player->getUser()->getUsername(), $selectedLocation->getActionCard()?->getName() ?? 'unknown', $targetPlayer->getUser()->getUsername()),
            [
                'player_id' => $player->getId(),
                'targetPlayerId' => $targetPlayer->getId(),
                'stolenCardId' => $selectedLocation->getActionCard()?->getId(),
                'stolenLocationId' => $selectedLocation->getId(),
            ]
        );
    }

}
