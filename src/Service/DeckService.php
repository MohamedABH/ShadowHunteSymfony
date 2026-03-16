<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Location;
use App\Entity\Player;
use App\Enum\LocationEnum;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;

class DeckService
{
    public function __construct(
        private readonly LocationRepository $locationRepository,
        private readonly GameService $gameService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function drawCardFromDeck(Game $game, Player $player, LocationEnum $deckLocation): ?Location
    {
        $cardLocation = $this->locationRepository->createQueryBuilder('l')
            ->andWhere('l.game = :game')
            ->andWhere('l.location = :deck')
            ->setParameter('game', $game)
            ->setParameter('deck', $deckLocation)
            ->orderBy('l.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$cardLocation) {
            $this->gameService->reshuffleDeck($game->getId());
            $cardLocation = $this->locationRepository->createQueryBuilder('l')
                ->andWhere('l.game = :game')
                ->andWhere('l.location = :deck')
                ->setParameter('game', $game)
                ->setParameter('deck', $deckLocation)
                ->orderBy('l.position', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        if (!$cardLocation) {
            return null;
        }

        $cardLocation->setLocation(LocationEnum::IN_PLAY);
        $cardLocation->setPlayer($player);
        $cardLocation->setPosition(null);
        $this->entityManager->persist($cardLocation);
        $this->entityManager->flush();

        return $cardLocation;
    }
}
