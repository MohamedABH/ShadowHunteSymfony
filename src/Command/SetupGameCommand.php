<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\PlayerRepository;
use App\Service\UserService;
use App\Service\GameService;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\Colors;

#[AsCommand(
    name: 'app:setup-game',
    description: 'Creates 4 test users, a game, joins players, and starts the game',
)]
class SetupGameCommand extends Command
{
    public function __construct(
        private readonly PlayerRepository $playerRepository,
        private readonly UserService $userService,
        private readonly GameService $gameService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Define test user credentials
        $usersData = [
            ['username' => 'admin_setup_test', 'email' => 'adminsetup@test.com', 'password' => 'admin123', 'isAdmin' => true],
            ['username' => 'player1_setup_test', 'email' => 'player1setup@test.com', 'password' => 'player123', 'isAdmin' => false],
            ['username' => 'player2_setup_test', 'email' => 'player2setup@test.com', 'password' => 'player123', 'isAdmin' => false],
            ['username' => 'player3_setup_test', 'email' => 'player3setup@test.com', 'password' => 'player123', 'isAdmin' => false],
        ];

        // Step 1: Remove test users from active games (keep users, just remove them from games)
        $io->section('Step 1: Cleaning up test users from active games...');
        $createdUsers = [];
        foreach ($usersData as $userData) {
            $existingUser = $this->userService->findUser($userData['username'], $userData['email']);
            
            if ($existingUser) {
                // Remove from active games
                $removedCount = $this->userService->removeUserFromActiveGames($existingUser);
                if ($removedCount > 0) {
                    $io->text("Removed {$userData['username']} from {$removedCount} game(s)");
                }
                
                $io->text("Reusing existing user: {$userData['username']}");
                $createdUsers[] = $existingUser;
            } else {
                // Create new user
                try {
                    $user = $this->userService->findOrCreateUser($userData);
                    $createdUsers[] = $user;
                    $io->text("Created new user: {$userData['username']}" . ($userData['isAdmin'] ? ' (ADMIN)' : ''));
                } catch (\Exception $e) {
                    $io->error("Failed to create user {$userData['username']}: " . $e->getMessage());
                    return Command::FAILURE;
                }
            }
        }
        $io->success('Test users ready');

        // Step 2: Create game owned by admin
        $io->section('Step 2: Creating game...');
        $adminUser = $createdUsers[0];
        
        try {
            $game = $this->gameService->createGame('Test Game', $adminUser);
            $io->success("Game created with ID: {$game->getId()}");
        } catch (\Exception $e) {
            $io->error("Failed to create game: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Step 3: Add players to game
        $io->section('Step 3: Adding players to game...');
        $players = [];
        for ($i = 0; $i < count($createdUsers); $i++) {
            $existingPlayers = $game->getPlayers();
            $allColors = array_map(fn($c) => $c->value, Colors::cases());
            $availableColors = array_diff(
                $allColors,
                array_map(
                    fn($p) => $p->getColor()->value,
                    $existingPlayers->toArray()
                )
            );
            $io->text("Currently available colors: " . implode(", ", $availableColors));
            
            try {
                $player = $this->gameService->addPlayerToGame($createdUsers[$i], $game);
                $player->setPlayingOrder($i);
                $this->entityManager->persist($player);
                $players[] = $player;
                $io->text("Added player: {$createdUsers[$i]->getUsername()} (order: {$i}, color: {$player->getColor()->value})");
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $io->error("Failed to add player: " . $e->getMessage());
                return Command::FAILURE;
            }
        }
        
        // Collect IDs after flush to ensure they're generated
        $playerIds = [];
        foreach ($players as $player) {
            $playerIds[] = $player->getId();
        }
        $io->success('All players added to game');

        // Refresh game from database to ensure players are loaded
        $this->entityManager->refresh($game);

        // Step 4: Initialize and start the game
        $io->section('Step 4: Initializing and starting game...');
        try {
            $this->gameService->startGame($game);
            $io->success('Game initialized and started');
        } catch (\Exception $e) {
            $io->error("Failed to initialize game: {$e->getMessage()}");
            return Command::FAILURE;
        }

        // Final output
        $io->section('Game Setup Complete');
        $io->text("Admin User: {$adminUser->getUsername()}");
        $io->text("Game ID: {$game->getId()}");
        $io->text("Game Status: {$game->getStatus()->value}");
        $io->text("Total Players: " . count($playerIds));

        return Command::SUCCESS;
    }
}
