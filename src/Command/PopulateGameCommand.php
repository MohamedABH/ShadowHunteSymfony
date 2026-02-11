<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:populate-game',
    description: 'Creates 3 test users, and have them join a game with given id',
)]
class PopulateGameCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PlayerRepository $playerRepository,
        private readonly GameRepository $gameRepository,
        private readonly RoleRepository $roleRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
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

        $roleUser = $this->roleRepository->findOneBy(['libelle' => 'ROLE_USER']);
        if (!$roleUser) {
            $io->error('ROLE_USER not found. Make sure roles are loaded in the database.');
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
            $existingUser = $this->userRepository->findOneBy(['username' => $userData['username']]);
            if (!$existingUser) {
                $existingUser = $this->userRepository->findOneBy(['email' => $userData['email']]);
            }

            if ($existingUser) {
                $nonCompletedGames = $this->gameRepository->createQueryBuilder('g')
                    ->leftJoin('g.players', 'p')
                    ->where('p.user = :user')
                    ->andWhere('g.status IN (:statuses)')
                    ->setParameter('user', $existingUser)
                    ->setParameter('statuses', [GameStatus::PENDING, GameStatus::ONGOING])
                    ->getQuery()
                    ->getResult();

                foreach ($nonCompletedGames as $activeGame) {
                    if ($activeGame->getId() === $gameId) {
                        continue;
                    }
                    foreach ($activeGame->getPlayers() as $player) {
                        if ($player->getUser()->getId() === $existingUser->getId()) {
                            $activeGame->removePlayer($player);
                            $this->entityManager->remove($player);
                            $io->text("Removed {$userData['username']} from game ID: {$activeGame->getId()}");
                        }
                    }
                }

                if (!in_array('ROLE_USER', $existingUser->getRoles(), true)) {
                    $existingUser->addRole($roleUser);
                    $this->entityManager->persist($existingUser);
                }

                $io->text("Reusing existing user: {$userData['username']}");
                $createdUsers[] = $existingUser;
                continue;
            }

            $user = new User();
            $user->setUsername($userData['username']);
            $user->setEmail($userData['email']);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);
            $user->addRole($roleUser);
            $this->entityManager->persist($user);
            $createdUsers[] = $user;
            $io->text("Created new user: {$userData['username']}");
        }

        $this->entityManager->flush();
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
            $player = $this->playerRepository->createPlayer($user, $game);
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
