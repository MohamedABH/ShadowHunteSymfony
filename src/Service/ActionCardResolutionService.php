<?php

namespace App\Service;

use App\Entity\ActionCard;
use App\Entity\Game;
use App\Entity\Player;
use App\Enum\ActionCardType;
use App\Enum\LocationEnum;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;

class ActionCardResolutionService
{
    public function __construct(
        private readonly LocationRepository $locationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function discardIfNeeded(ActionCard $card, Player $player, Game $game): void
    {
        if ($card->isEquipment()) {
            return;
        }

        $location = $this->locationRepository->findLatestInPlayActionCardLocation($game, $player, $card);
        if (!$location) {
            return;
        }

        $discardLocation = match ($card->getType()) {
            ActionCardType::DARK => LocationEnum::DARK_DISCARD,
            ActionCardType::LIGHT => LocationEnum::LIGHT_DISCARD,
            ActionCardType::SIGHT => LocationEnum::SIGHT_DISCARD,
        };

        $location->setLocation($discardLocation);
        $location->setPlayer(null);
        $location->setPosition(null);

        $this->entityManager->persist($location);
        $this->entityManager->flush();
    }
}
