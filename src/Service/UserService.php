<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Role;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Enum\GameStatus;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly GameRepository $gameRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Check if a user exists by username or email
     */
    public function userExists(?string $username = null, ?string $email = null): bool
    {
        if ($username && $this->userRepository->findOneBy(['username' => $username])) {
            return true;
        }

        if ($email && $this->userRepository->findOneBy(['email' => $email])) {
            return true;
        }

        return false;
    }

    /**
     * Find existing user by username or email
     */
    public function findUser(?string $username = null, ?string $email = null): ?User
    {
        if ($username) {
            $user = $this->userRepository->findOneBy(['username' => $username]);
            if ($user) {
                return $user;
            }
        }

        if ($email) {
            return $this->userRepository->findOneBy(['email' => $email]);
        }

        return null;
    }

    /**
     * Create a new user with the given credentials and roles
     * 
     * @param string $username
     * @param string $email
     * @param string $password Plain text password (will be hashed)
     * @param array $roleNames Array of role names (e.g., ['ROLE_USER', 'ROLE_ADMIN'])
     * @return User
     * @throws \InvalidArgumentException if user already exists
     */
    public function createUser(string $username, string $email, string $password, array $roleNames = ['ROLE_USER']): User
    {
        // Check if user already exists
        if ($this->userExists($username, $email)) {
            throw new \InvalidArgumentException('User with this username or email already exists');
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        
        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Assign roles
        $roles = $this->roleRepository->findBy(['libelle' => $roleNames]);
        foreach ($roles as $role) {
            $user->addRole($role);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Find existing user by username or email, or create a new one if not found
     * 
     * @param array $userData Array with keys: username, email, password, isAdmin (optional)
     * @return User
     */
    public function findOrCreateUser(array $userData): User
    {
        $existingUser = $this->findUser($userData['username'] ?? null, $userData['email'] ?? null);

        if ($existingUser) {
            // Ensure user has the correct roles
            $roleUser = $this->roleRepository->findOneBy(['libelle' => 'ROLE_USER']);
            if ($roleUser && !in_array('ROLE_USER', $existingUser->getRoles(), true)) {
                $existingUser->addRole($roleUser);
            }

            if (!empty($userData['isAdmin'])) {
                $roleAdmin = $this->roleRepository->findOneBy(['libelle' => 'ROLE_ADMIN']);
                if ($roleAdmin && !in_array('ROLE_ADMIN', $existingUser->getRoles(), true)) {
                    $existingUser->addRole($roleAdmin);
                }
            }

            $this->entityManager->persist($existingUser);
            $this->entityManager->flush();

            return $existingUser;
        }

        // Create new user
        $roleNames = ['ROLE_USER'];
        if (!empty($userData['isAdmin'])) {
            $roleNames[] = 'ROLE_ADMIN';
        }

        return $this->createUser(
            $userData['username'],
            $userData['email'],
            $userData['password'],
            $roleNames
        );
    }

    /**
     * Remove user from all active (non-completed) games
     * 
     * @param User $user
     * @return int Number of games the user was removed from
     */
    public function removeUserFromActiveGames(User $user): int
    {
        $nonCompletedGames = $this->gameRepository->createQueryBuilder('g')
            ->leftJoin('g.players', 'p')
            ->where('p.user = :user')
            ->andWhere('g.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', [GameStatus::PENDING, GameStatus::ONGOING])
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($nonCompletedGames as $game) {
            foreach ($game->getPlayers() as $player) {
                if ($player->getUser()->getId() === $user->getId()) {
                    $game->removePlayer($player);
                    $this->entityManager->remove($player);
                    $count++;
                }
            }
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }

    /**
     * Validate user credentials
     * 
     * @param User $user
     * @param string $password
     * @return bool
     */
    public function isPasswordValid(User $user, string $password): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $password);
    }
}
