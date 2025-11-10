<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-user-emails',
    description: 'Remplace toutes les adresses email utilisateurs par des alias sur les boîtes Gmail données.'
)]
class UpdateUserEmailsCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->userRepository->findBy(['role' => 'user'], ['id' => 'ASC']);

        if (empty($users)) {
            $io->warning('Aucun utilisateur avec le rôle "user" n’a été trouvé, aucune modification effectuée.');
            return Command::SUCCESS;
        }

        $targets = array_slice($users, 0, 2);
        $newEmails = [
            'valeafifaliana@gmail.com',
            'malalavalea@gmail.com',
        ];

        $updated = 0;

        foreach ($targets as $idx => $user) {
            $newEmail = $newEmails[$idx];

            if ($user->getEmail() === $newEmail) {
                continue;
            }

            $user->setEmail($newEmail);
            $updated++;
        }

        if ($updated > 0) {
            $this->entityManager->flush();
        }

        $io->success(sprintf('%d utilisateur(s) mis à jour (premiers comptes rôle "user").', $updated));

        return Command::SUCCESS;
    }
}


