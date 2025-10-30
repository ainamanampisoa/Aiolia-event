<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Crée des utilisateurs de test pour l\'authentification',
)]
class CreateTestUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $testUsers = [
            [
                'email' => 'admin@aiolia.mg',
                'password' => 'admin123',
                'firstName' => 'Admin',
                'lastName' => 'System',
                'phone' => '+261 34 12 345 67',
                'role' => 'admin',
                'emailVerified' => true,
            ],
            [
                'email' => 'organizer@aiolia.mg',
                'password' => 'organizer123',
                'firstName' => 'Jean',
                'lastName' => 'Dupont',
                'phone' => '+261 33 23 456 78',
                'role' => 'organizer',
                'emailVerified' => true,
            ],
            [
                'email' => 'user@aiolia.mg',
                'password' => 'user123',
                'firstName' => 'Marie',
                'lastName' => 'Martin',
                'phone' => '+261 32 34 567 89',
                'role' => 'user',
                'emailVerified' => true,
            ],
            [
                'email' => 'test@test.com',
                'password' => 'test123',
                'firstName' => 'Test',
                'lastName' => 'User',
                'phone' => '+261 34 45 678 90',
                'role' => 'user',
                'emailVerified' => false,
            ],
            [
                'email' => 'coorganizer@aiolia.mg',
                'password' => 'coorg123',
                'firstName' => 'Pierre',
                'lastName' => 'Bernard',
                'phone' => '+261 33 56 789 01',
                'role' => 'co_organizer',
                'emailVerified' => true,
            ],
        ];

        $io->title('Création des utilisateurs de test');

        foreach ($testUsers as $userData) {
            // Vérifier si l'utilisateur existe déjà
            $existingUser = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['email' => $userData['email']]);

            if ($existingUser) {
                $io->warning(sprintf('L\'utilisateur %s existe déjà, ignoré.', $userData['email']));
                continue;
            }

            // Créer le nouvel utilisateur
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setPhone($userData['phone']);
            $user->setRole($userData['role']);
            $user->setEmailVerified($userData['emailVerified']);
            
            // Hasher le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPasswordHash($hashedPassword);

            $this->entityManager->persist($user);

            $io->success(sprintf(
                'Utilisateur créé: %s (%s) - Mot de passe: %s',
                $userData['email'],
                $userData['role'],
                $userData['password']
            ));
        }

        $this->entityManager->flush();

        $io->success('Tous les utilisateurs de test ont été créés !');
        
        $io->section('Informations de connexion :');
        $io->table(
            ['Email', 'Mot de passe', 'Rôle', 'Nom'],
            [
                ['admin@aiolia.mg', 'admin123', 'Admin', 'Admin System'],
                ['organizer@aiolia.mg', 'organizer123', 'Organizer', 'Jean Dupont'],
                ['user@aiolia.mg', 'user123', 'User', 'Marie Martin'],
                ['test@test.com', 'test123', 'User', 'Test User'],
                ['coorganizer@aiolia.mg', 'coorg123', 'Co-Organizer', 'Pierre Bernard'],
            ]
        );

        return Command::SUCCESS;
    }
}

