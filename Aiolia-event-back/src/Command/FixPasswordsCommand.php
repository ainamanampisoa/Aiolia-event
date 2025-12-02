<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:fix-passwords',
    description: 'Change tous les mots de passe des utilisateurs avec "azerty!" crypté avec BCRYPT (cost 13)',
)]
class FixPasswordsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Mise à jour des mots de passe des utilisateurs');

        // Générer le hash du mot de passe avec le hasher Symfony (BCRYPT cost 13)
        $plainPassword = 'azerty!';
        
        // Créer un utilisateur temporaire pour utiliser le hasher Symfony
        $tempUser = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($tempUser, $plainPassword);

        $io->info(sprintf(
            'Nouveau mot de passe : %s (hashé avec BCRYPT, cost 13)',
            $plainPassword
        ));

        // Demander confirmation
        if (!$io->confirm('Voulez-vous vraiment changer tous les mots de passe ?', false)) {
            $io->warning('Opération annulée');
            return Command::SUCCESS;
        }

        try {
            // Récupérer tous les utilisateurs
            $users = $this->userRepository->findAll();
            $totalUsers = count($users);

            if ($totalUsers === 0) {
                $io->warning('Aucun utilisateur trouvé dans la base de données');
                return Command::SUCCESS;
            }

            $io->section(sprintf('Mise à jour de %d utilisateur(s)', $totalUsers));

            $updatedCount = 0;
            $progressBar = $io->createProgressBar($totalUsers);
            $progressBar->start();

            // Mettre à jour chaque utilisateur
            foreach ($users as $user) {
                $user->setHashMotDePasse($hashedPassword);
                $this->entityManager->persist($user);
                $updatedCount++;
                $progressBar->advance();
            }

            // Flush toutes les modifications
            $this->entityManager->flush();
            $progressBar->finish();

            $io->newLine(2);
            $io->success(sprintf(
                '%d utilisateur(s) mis à jour avec succès',
                $updatedCount
            ));

            $io->newLine();
            $io->section('Résumé');
            $io->table(
                ['Statut', 'Nombre'],
                [
                    ['Utilisateurs mis à jour', $updatedCount],
                    ['Total utilisateurs', $totalUsers],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf(
                'Erreur lors de la mise à jour des mots de passe : %s',
                $e->getMessage()
            ));
            
            return Command::FAILURE;
        }
    }
}

