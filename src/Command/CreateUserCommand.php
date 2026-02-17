<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\UserService;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user in the system',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserService $userService,
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
            ->addOption('admin', null, InputOption::VALUE_NONE, 'If set, the user will be an admin')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        
        // Check if user already exists
        if ($this->userService->userExists($username, $email)) {
            $io->error('User with this username or email already exists.');
            return Command::FAILURE;
        }

        // Determine roles
        $roles = ['ROLE_USER'];
        if ($input->getOption('admin')) {
            $roles[] = 'ROLE_ADMIN';
        }

        try {
            // Use UserService to create user
            $user = $this->userService->createUser($username, $email, $password, $roles);
            $io->success('User created successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to create user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
