<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\User;
use App\Entity\Game;
use App\Entity\Role;
use App\Repository\UserRepository;
use App\Repository\PlayerRepository;
use App\Repository\GameRepository;
use App\Enum\GameStatus;
use App\Service\GameService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\Colors;

#[AsCommand(
    name: 'app:setup-game',
    description: 'Creates 4 test users, a game, joins players, and starts the game',
)]
class SetupGameCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PlayerRepository $playerRepository,
        private readonly GameRepository $gameRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly GameService $gameService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Define test user credentials
        $users = [
            ['username' => 'admin_setup_test', 'email' => 'adminsetup@test.com', 'password' => 'admin123', 'isAdmin' => true],
            ['username' => 'player1_setup_test', 'email' => 'player1setup@test.com', 'password' => 'player123', 'isAdmin' => false],
            ['username' => 'player2_setup_test', 'email' => 'player2setup@test.com', 'password' => 'player123', 'isAdmin' => false],
            ['username' => 'player3_setup_test', 'email' => 'player3setup@test.com', 'password' => 'player123', 'isAdmin' => false],
        ];

        // Get roles from database (created by fixtures)
        $roleRepository = $this->entityManager->getRepository(Role::class);
        $roleUser = $roleRepository->findOneBy(['libelle' => 'ROLE_USER']);
        $roleAdmin = $roleRepository->findOneBy(['libelle' => 'ROLE_ADMIN']);

        // Step 1: Remove test users from active games (keep users, just remove them from games)
        $io->section('Step 1: Cleaning up test users from active games...');
        $createdUsers = [];
        foreach ($users as $userData) {
            $existingUser = $this->userRepository->findOneBy(['username' => $userData['username']]);
            if ($existingUser) {
                // Find all non-completed games where this user is a player
                $nonCompletedGames = $this->gameRepository->createQueryBuilder('g')
                    ->leftJoin('g.players', 'p')
                    ->where('p.user = :user')
                    ->andWhere('g.status IN (:statuses)')
                    ->setParameter('user', $existingUser)
                    ->setParameter('statuses', [GameStatus::PENDING, GameStatus::ONGOING])
                    ->getQuery()
                    ->getResult();
                
                foreach ($nonCompletedGames as $game) {
                    $players = $game->getPlayers();
                    foreach ($players as $player) {
                        if ($player->getUser()->getId() === $existingUser->getId()) {
                            $game->removePlayer($player);
                            $this->entityManager->remove($player);
                            $io->text("Removed {$userData['username']} from game ID: {$game->getId()}");
                        }
                    }
                }
                
                // Ensure existing user has the correct roles
                if (!$existingUser->getRoles() || !in_array('ROLE_USER', $existingUser->getRoles())) {
                    $existingUser->addRole($roleUser);
                    $this->entityManager->persist($existingUser);
                }
                if ($userData['isAdmin'] && !in_array('ROLE_ADMIN', $existingUser->getRoles())) {
                    $existingUser->addRole($roleAdmin);
                    $this->entityManager->persist($existingUser);
                }
                
                $io->text("Reusing existing user: {$userData['username']}");
                $createdUsers[] = $existingUser;
            } else {
                // Create new user if they don't exist
                $user = new User();
                $user->setUsername($userData['username']);
                $user->setEmail($userData['email']);
                $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
                $user->setPassword($hashedPassword);

                // Set roles
                $user->addRole($roleUser);
                if ($userData['isAdmin']) {
                    $user->addRole($roleAdmin);
                }
                $this->entityManager->persist($user);
                $createdUsers[] = $user;
                $io->text("Created new user: {$userData['username']}" . ($userData['isAdmin'] ? ' (ADMIN)' : ''));
            }
        }
        $this->entityManager->flush();
        $io->success('Test users ready');

        // Step 2: Create game owned by admin
        $io->section('Step 2: Creating game...');
        $adminUser = $createdUsers[0];
        $game = new Game();
        $game->setName('Test Game');
        $game->setStatus(GameStatus::PENDING);
        $game->setOwner($adminUser);
        $game->setTurn(1);
        $this->entityManager->persist($game);
        $this->entityManager->flush();
        $io->success("Game created with ID: {$game->getId()}");

        // Step 3: Add players to game
        $io->section('Step 3: Adding players to game...');
        $players = [];
        for ($i = 0; $i < count($createdUsers); $i++) {
            $players = $game->getPlayers();
            $allColors = array_map(fn($c) => $c->value, Colors::cases());
            $availableColors = array_diff(
                $allColors,
                array_map(
                    fn($p) => $p->getColor()->value,
                    $players->toArray()
                )
            );
            $io->text("Currently available colors: " . implode(", ", $availableColors));
            $player = $this->playerRepository->createPlayer($createdUsers[$i], $game);
            $player->setPlayingOrder($i);
            $this->entityManager->persist($player);
            $players[] = $player;
            $io->text("Added player: {$createdUsers[$i]->getUsername()} (order: {$i}, color: {$player->getColor()->value})");
            $this->entityManager->flush();
        }
        
        // Collect IDs after flush to ensure they're generated
        $playerIds = [];
        foreach ($players as $player) {
            $playerIds[] = $player->getId();
        }
        $io->success('All players added to game');

        // Step 4: Initialize and start the game
        $io->section('Step 4: Initializing and starting game...');
        try {
            $this->gameService->initializeGame($game->getId(), $playerIds);
            $game->setStatus(GameStatus::ONGOING);
            $this->entityManager->persist($game);
            $this->entityManager->flush();
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
