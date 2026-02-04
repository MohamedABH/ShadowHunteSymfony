<?php

namespace App\Service;

use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Repository\CharacterCardRepository;
use App\Repository\LocationRepository;
use App\Entity\Location;
use App\Entity\ActionCard;
use App\Enum\LocationEnum;
use Doctrine\ORM\EntityManagerInterface;

class GameService {

    public function __construct(
        private readonly GameRepository $gameRepository,
        private readonly PlayerRepository $playerRepository,
        private readonly CharacterCardRepository $characterRepository,
        private readonly LocationRepository $locationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function initializeGame(int $gameId, array $playerIds): void {

        $reparition = [
            4 => ["shadow" => 2, "hunter" => 2, "neutral" => 0],
            5 => ["shadow" => 2, "hunter" => 2, "neutral" => 1],
            6 => ["shadow" => 2, "hunter" => 2, "neutral" => 2],
            7 => ["shadow" => 2, "hunter" => 2, "neutral" => 3],
            8 => ["shadow" => 3, "hunter" => 3, "neutral" => 2],
        ];

        if (!isset($reparition[count($playerIds)])) {
            throw new \InvalidArgumentException("Invalid number of players: " . count($playerIds));
        }

        $repartition = $reparition[count($playerIds)];

        $game = $this->gameRepository->find($gameId);
        if (!$game) {
            throw new \InvalidArgumentException("Game with ID $gameId not found.");
        }

        // Get players and shuffle them
        $players = [];
        foreach ($playerIds as $playerId) {
            $player = $this->playerRepository->find($playerId);
            if (!$player) {
                throw new \InvalidArgumentException("Player with ID $playerId not found.");
            }
            $player->setCurrentDamage(0);
            $player->setRevealed(false);
            $players[] = $player;
            $this->entityManager->persist($player);
        }

        // Shuffle players for random assignment
        shuffle($players);

        // Get character cards grouped by type
        $shadowCards = $this->characterRepository->findByType('shadow');
        $hunterCards = $this->characterRepository->findByType('hunter');
        $neutralCards = $this->characterRepository->findByType('neutral');

        // Assign character cards ensuring different initials globally
        $assignedCards = [];
        $usedInitials = [];

        // Create role queue
        $roleQueue = [];
        for ($i = 0; $i < $repartition['shadow']; $i++) {
            $roleQueue[] = 'shadow';
        }
        for ($i = 0; $i < $repartition['hunter']; $i++) {
            $roleQueue[] = 'hunter';
        }
        for ($i = 0; $i < $repartition['neutral']; $i++) {
            $roleQueue[] = 'neutral';
        }
        shuffle($roleQueue);

        foreach ($players as $index => $player) {
            $role = $roleQueue[$index];
            
            // Get cards for this role
            if ($role === 'shadow') {
                $cards = $shadowCards;
            } elseif ($role === 'hunter') {
                $cards = $hunterCards;
            } else {
                $cards = $neutralCards;
            }

            // Find a card with a unique initial (global)
            $selectedCard = null;
            foreach ($cards as $card) {
                if (!in_array($card->getInitial(), $usedInitials)) {
                    $selectedCard = $card;
                    $usedInitials[] = $card->getInitial();
                    break;
                }
            }

            if (!$selectedCard) {
                throw new \RuntimeException("Not enough character cards with different initials for role: $role");
            }

            $player->setCharacterCard($selectedCard);
            $this->entityManager->persist($player);
        }

        $this->initializeDeck($gameId);
        $this->reshuffleDeck($gameId);

        $game->setTurn(1);
        $this->entityManager->persist($game);
        $this->entityManager->flush();
    }

    /**
     * Initialize the game decks with all ActionCard instances sorted by type
     * Creates multiple entries based on each card's count attribute
     * DARK cards go to DARK_DECK, LIGHT cards to LIGHT_DECK, SIGHT cards to VISION_DECK
     */
    public function initializeDeck(int $gameId): void
    {
        $game = $this->gameRepository->find($gameId);
        if (!$game) {
            throw new \InvalidArgumentException("Game with ID $gameId not found.");
        }

        // Get all ActionCards using a query for the ActionCard class
        $actionCards = $this->entityManager->getRepository(ActionCard::class)->findAll();

        // Separate cards by type and assign to appropriate decks
        $positions = [
            'dark' => 0,
            'light' => 0,
            'sight' => 0,
        ];

        // Create Location entries for each ActionCard based on its count
        foreach ($actionCards as $actionCard) {
            // Determine which deck this card goes to based on its type
            $type = $actionCard->getType()->value;
            $deckLocation = match ($type) {
                'dark' => LocationEnum::DARK_DECK,
                'light' => LocationEnum::LIGHT_DECK,
                'sight' => LocationEnum::SIGHT_DECK,
                default => throw new \InvalidArgumentException("Unknown action card type: $type"),
            };

            // Create as many Location entries as the card's count
            for ($i = 0; $i < $actionCard->getCount(); $i++) {
                $location = new Location();
                $location->setGame($game);
                $location->setActionCard($actionCard);
                $location->setLocation($deckLocation);
                $location->setPosition($positions[$type]++);
                $this->entityManager->persist($location);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Shuffle and reshuffle all three decks with their discard piles
     * Combines DARK_DECK with DARK_DISCARD, LIGHT_DECK with LIGHT_DISCARD, VISION_DECK with VISION_DISCARD
     */
    public function reshuffleDeck(int $gameId): void
    {
        $game = $this->gameRepository->find($gameId);
        if (!$game) {
            throw new \InvalidArgumentException("Game with ID $gameId not found.");
        }

        // Define deck and discard pairs
        $deckPairs = [
            [LocationEnum::DARK_DECK, LocationEnum::DARK_DISCARD],
            [LocationEnum::LIGHT_DECK, LocationEnum::LIGHT_DISCARD],
            [LocationEnum::SIGHT_DECK, LocationEnum::SIGHT_DISCARD],
        ];

        // Process each deck/discard pair
        foreach ($deckPairs as [$deckLocation, $discardLocation]) {
            // Get all cards in this deck and its discard pile for this game
            $deckAndDiscardCards = $this->locationRepository->createQueryBuilder('l')
                ->andWhere('l.game = :game')
                ->andWhere('l.location IN (:locations)')
                ->setParameter('game', $game)
                ->setParameter('locations', [$deckLocation, $discardLocation])
                ->getQuery()
                ->getResult();

            // Shuffle the combined array
            shuffle($deckAndDiscardCards);

            // Update all cards to be in the deck location with new positions
            foreach ($deckAndDiscardCards as $index => $location) {
                $location->setLocation($deckLocation);
                $location->setPosition($index);
                $this->entityManager->persist($location);
            }
        }

        $this->entityManager->flush();
    }
}