<?php

namespace App\Service;

use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Repository\CharacterCardRepository;
use App\Repository\LocationRepository;
use App\Repository\PositionRepository;
use App\Entity\Game;
use App\Entity\User;
use App\Entity\Player;
use App\Entity\Location;
use App\Entity\Position;
use App\Entity\ActionCard;
use App\Enum\LocationEnum;
use App\Enum\GameStatus;
use Doctrine\ORM\EntityManagerInterface;

class GameService {

    public function __construct(
        private readonly GameRepository $gameRepository,
        private readonly PlayerRepository $playerRepository,
        private readonly CharacterCardRepository $characterRepository,
        private readonly LocationRepository $locationRepository,
        private readonly PositionRepository $positionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Create a new game
     * 
     * @param string $name
     * @param User $owner
     * @return Game
     */
    public function createGame(string $name, User $owner): Game
    {
        $game = $this->gameRepository->createGame($name, $owner);
        $this->entityManager->persist($game);
        
        // Persist any newly created players attached to the game
        foreach ($game->getPlayers() as $player) {
            $this->entityManager->persist($player);
        }
        
        $this->entityManager->flush();

        return $game;
    }

    /**
     * Add a player to a game
     * 
     * @param User $user
     * @param Game $game
     * @return Player
     */
    public function addPlayerToGame(User $user, Game $game): Player
    {
        $player = $this->playerRepository->createPlayer($user, $game);
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return $player;
    }

    /**
     * Remove a player from a game
     * If the player is the owner, transfer ownership or abort the game
     * If game is ongoing, inflict max damage instead of removing
     * 
     * @param User $user
     * @param Game $game
     * @return void
     */
    public function removePlayerFromGame(User $user, Game $game): void
    {
        // Find the player in the game
        $playerToRemove = null;
        foreach ($game->getPlayers() as $player) {
            if ($player->getUser()->getId() === $user->getId()) {
                $playerToRemove = $player;
                break;
            }
        }

        if (!$playerToRemove) {
            throw new \InvalidArgumentException('User is not part of this game');
        }

        // Check if user is the game owner
        $isOwner = $game->getOwner()->getId() === $user->getId();
        
        if ($isOwner) {
            // Owner leaves: designate another player as the new owner
            $remainingPlayers = $game->getPlayers()->filter(function($p) use ($playerToRemove) {
                return $p->getId() !== $playerToRemove->getId();
            });
            
            if ($remainingPlayers->count() > 0) {
                // Promote the first remaining player to owner
                $newOwner = $remainingPlayers->first()->getUser();
                $game->setOwner($newOwner);
                $this->entityManager->persist($game);
            } else {
                // Owner is the only player: abort the game
                $game->setStatus(GameStatus::ABORTED);
                $this->entityManager->persist($game);
            }
        }
        
        // Remove the leaving player
        if ($game->getStatus()->value === 'pending') {
            $this->entityManager->remove($playerToRemove);
        } else {
            $playerToRemove->setCurrentDamage(100); // assuming 100 is max damage
            $this->entityManager->persist($playerToRemove);
        }

        // Check if game has no players left, if so abort the game
        $remainingPlayersCount = $game->getPlayers()->filter(function($p) use ($playerToRemove) {
            return $p->getId() !== $playerToRemove->getId();
        })->count();
        
        if ($remainingPlayersCount === 0) {
            $game->setStatus(GameStatus::ABORTED);
            $this->entityManager->persist($game);
        }

        $this->entityManager->flush();
    }

    /**
     * Start a game (set status to ONGOING and initialize game mechanics)
     * 
     * @param Game $game
     * @return void
     */
    public function startGame(Game $game): void
    {
        if ($game->getStatus()->value !== 'pending') {
            throw new \InvalidArgumentException('Game cannot be started. Current status: ' . $game->getStatus()->value);
        }

        // Update game status to 'ongoing'
        $game->setStatus(GameStatus::ONGOING);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        // Initialize game with character card assignment and deck creation
        $playerIds = array_map(fn($player) => $player->getId(), $game->getPlayers()->toArray());
        $this->initializeGame($game->getId(), $playerIds);
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
        $this->createPositions($gameId);
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

    /**
     * Create positions on the board for each PlaceCard
     * Positions represent the locations where players can move
     */
    public function createPositions(int $gameId): void
    {
        $game = $this->gameRepository->find($gameId);
        if (!$game) {
            throw new \InvalidArgumentException("Game with ID $gameId not found.");
        }

        // Get all PlaceCards from the database
        $placeCards = $this->entityManager->getRepository('App\\Entity\\PlaceCard')->findAll();

        // Create a position for each PlaceCard
        $positionNumber = 1;
        foreach ($placeCards as $placeCard) {
            $position = new Position();
            $position->setGame($game);
            $position->setPlaceCard($placeCard);
            $position->setNumber($positionNumber);
            $this->entityManager->persist($position);
            $positionNumber++;
        }

        $this->entityManager->flush();
    }
}