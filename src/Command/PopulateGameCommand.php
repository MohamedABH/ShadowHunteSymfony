<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Service\UserService;
use App\Service\GameService;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:populate-game',
    description: 'Creates 3 test users, and have them join a game with given id',
)]
class PopulateGameCommand extends Command
{
    public function __construct(
        private readonly GameRepository $gameRepository,
        private readonly PlayerRepository $playerRepository,
        private readonly UserService $userService,
        private readonly GameService $gameService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('gameId', InputArgument::REQUIRED, 'The game ID to join');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $gameId = (int) $input->getArgument('gameId');

        $game = $this->gameRepository->find($gameId);
        if (!$game) {
            $io->error("Game with ID {$gameId} not found.");
            return Command::FAILURE;
        }

        $users = [
            ['username' => 'player1_populate_test', 'email' => 'player1populate@test.com', 'password' => 'player123'],
            ['username' => 'player2_populate_test', 'email' => 'player2populate@test.com', 'password' => 'player123'],
            ['username' => 'player3_populate_test', 'email' => 'player3populate@test.com', 'password' => 'player123'],
        ];

        $io->section('Step 1: Preparing test users...');
        $createdUsers = [];
        foreach ($users as $userData) {
            $existingUser = $this->userService->findUser($userData['username'], $userData['email']);

            if ($existingUser) {
                // Remove user from active games (except current game)
                $removedCount = $this->userService->removeUserFromActiveGames($existingUser);
                if ($removedCount > 0) {
                    $io->text("Removed {$userData['username']} from {$removedCount} active game(s)");
                }

                $io->text("Reusing existing user: {$userData['username']}");
                $createdUsers[] = $existingUser;
                continue;
            }

            // Create new user
            try {
                $user = $this->userService->createUser(
                    $userData['username'],
                    $userData['email'],
                    $userData['password'],
                    ['ROLE_USER']
                );
                $createdUsers[] = $user;
                $io->text("Created new user: {$userData['username']}");
            } catch (\Exception $e) {
                $io->error("Failed to create user {$userData['username']}: " . $e->getMessage());
                continue;
            }
        }

        $io->success('Test users ready');

        $io->section("Step 2: Joining users to game ID {$gameId}...");
        $existingPlayerUserIds = [];
        foreach ($game->getPlayers() as $player) {
            $existingPlayerUserIds[] = $player->getUser()->getId();
        }

        $playingOrder = $game->getPlayers()->count();
        $addedCount = 0;
        foreach ($createdUsers as $user) {
            if (in_array($user->getId(), $existingPlayerUserIds, true)) {
                $io->text("User already in game: {$user->getUsername()}");
                continue;
            }
            
            // Use GameService to add player
            $player = $this->gameService->addPlayerToGame($user, $game);
            $player->setPlayingOrder($playingOrder);
            $playingOrder++;
            $this->entityManager->persist($player);
            $addedCount++;
            $io->text("Added player: {$user->getUsername()} (order: {$player->getPlayingOrder()}, color: {$player->getColor()->value})");
        }

        $this->entityManager->flush();

        $io->success("Added {$addedCount} players to game ID {$gameId}");

        return Command::SUCCESS;
    }
}
