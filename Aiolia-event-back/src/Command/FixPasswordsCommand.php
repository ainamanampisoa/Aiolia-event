<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:fix-passwords',
    description: 'Corrige les mots de passe des utilisateurs en les re-hachant avec le système Symfony',
)]
class FixPasswordsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('role', 'r', InputOption::VALUE_OPTIONAL, 'Filtrer par rôle (admin, organizer, user)')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'Mot de passe à utiliser (par défaut: selon le rôle)')
            ->setHelp('Cette commande re-hache tous les mots de passe avec le système Symfony pour corriger les incompatibilités avec PostgreSQL crypt().');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Correction des mots de passe');

        $roleFilter = $input->getOption('role');
        $customPassword = $input->getOption('password');

        // Définir les mots de passe par défaut selon le rôle
        $defaultPasswords = [
            'admin' => 'Admin#Test123',
            'organizer' => 'Org#Test123',
            'user' => 'User#Test123',
        ];

        // Récupérer tous les utilisateurs
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u');
        
        if ($roleFilter) {
            $qb->where('u.role = :role')
               ->setParameter('role', $roleFilter);
        }

        $users = $qb->getQuery()->getResult();

        if (empty($users)) {
            $io->warning('Aucun utilisateur trouvé.');
            return Command::FAILURE;
        }

        $io->info(sprintf('Trouvé %d utilisateur(s) à traiter.', count($users)));

        $updated = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            // Déterminer le mot de passe à utiliser
            $password = $customPassword;
            if (!$password) {
                $userRole = $user->getRole();
                $password = $defaultPasswords[$userRole] ?? 'Test123';
            }

            // Re-hasher le mot de passe avec Symfony
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setHashMotDePasse($hashedPassword);

            $this->entityManager->persist($user);
            $updated++;

            $io->text(sprintf(
                '✓ %s (%s) - Mot de passe mis à jour',
                $user->getEmail(),
                $user->getRole()
            ));
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Mise à jour terminée : %d utilisateur(s) mis à jour, %d ignoré(s).',
            $updated,
            $skipped
        ));

        $io->section('Mots de passe par défaut :');
        $io->table(
            ['Rôle', 'Mot de passe'],
            [
                ['admin', 'Admin#Test123'],
                ['organizer', 'Org#Test123'],
                ['user', 'User#Test123'],
            ]
        );

        return Command::SUCCESS;
    }
}

