<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Enum\Role;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user in the system',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the new user')
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the new user')
            ->addArgument('password', InputArgument::REQUIRED, 'The password of the new user')
            ->addOption('role', null, InputOption::VALUE_OPTIONAL, 'Role of the new user (user|admin)', 'user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $roleOption = strtolower((string) $input->getOption('role'));
        if ($roleOption === 'admin') {
            $role = Role::ADMIN->value;
        } else {
            $io->error('Invalid role option. Allowed values: admin');
            return Command::FAILURE;
        }
        // Check if user already exists
        if ($this->userRepository->findOneBy(['username' => $username]) || $this->userRepository->findOneBy(['email' => $email])) {
            $io->error('User with this username or email already exists.');
            return Command::FAILURE;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $roles = array_unique([$role, Role::USER->value]);
        $user->setRoles($roles);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('User created successfully!');

        return Command::SUCCESS;
    }
}
