<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\RoleRepository;
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
        private UserPasswordHasherInterface $passwordHasher,
        private RoleRepository $roleRepository
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
                'phone' => '+261341234567',
                'role' => 'admin',
                'emailVerified' => true,
            ],
            [
                'email' => 'organizer@aiolia.mg',
                'password' => 'organizer123',
                'firstName' => 'Jean',
                'lastName' => 'Dupont',
                'phone' => '+261332345678',
                'role' => 'organizer',
                'emailVerified' => true,
            ],
            [
                'email' => 'user@aiolia.mg',
                'password' => 'user123',
                'firstName' => 'Marie',
                'lastName' => 'Martin',
                'phone' => '+261323456789',
                'role' => 'user',
                'emailVerified' => true,
            ],
            [
                'email' => 'test@test.com',
                'password' => 'test123',
                'firstName' => 'Test',
                'lastName' => 'User',
                'phone' => '+261344567890',
                'role' => 'user',
                'emailVerified' => false,
            ],
        ];

        $io->title('Création des utilisateurs de test');

        $roleCache = [];

        foreach ($testUsers as $userData) {
            // Vérifier si l'utilisateur existe déjà
            $user = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['email' => $userData['email']]);

            $isNewUser = $user === null;

            if ($isNewUser) {
                $user = new User();
                $user->setEmail($userData['email']);
                $io->success(sprintf('Utilisateur créé: %s (%s) - Mot de passe: %s', $userData['email'], $userData['role'], $userData['password']));
            } else {
                $io->note(sprintf('Utilisateur %s mis à jour avec les nouvelles informations de test.', $userData['email']));
            }

            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setPhone($userData['phone']);

            if (!isset($roleCache[$userData['role']])) {
                $roleCache[$userData['role']] = $this->roleRepository->getByCode($userData['role']);
            }

            $user->setRole($roleCache[$userData['role']]);
            $user->setIsEmailVerified($userData['emailVerified']);
            $user->setAccountStatus('active');

            // Hasher le mot de passe (réinitialise aussi les comptes existants)
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPasswordHash($hashedPassword);

            if ($isNewUser) {
                $this->entityManager->persist($user);
            }
        }

        $this->entityManager->flush();

        $io->success('Les utilisateurs de test ont été créés ou mis à jour !');
        
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

