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

        // Générer le hash du mot de passe avec BCRYPT cost 13 (comme configuré dans security.yaml)
        $plainPassword = 'azerty!';
        
        // Essayer d'utiliser un utilisateur réel de la base pour générer le hash
        // Si aucun utilisateur n'existe, utiliser password_hash() directement
        $hashedPassword = null;
        $testUser = $this->userRepository->findOneBy([]);
        
        if ($testUser) {
            // Utiliser le hasher Symfony avec un utilisateur réel pour garantir la compatibilité
            $hashedPassword = $this->passwordHasher->hashPassword($testUser, $plainPassword);
            $io->info('Hash généré avec le hasher Symfony (utilisateur réel)');
        } else {
            // Fallback : utiliser password_hash() directement avec les mêmes paramètres
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 13]);
            $io->info('Hash généré avec password_hash() (BCRYPT cost 13)');
        }

        // Vérifier que le hash est valide en le testant
        if (!password_verify($plainPassword, $hashedPassword)) {
            $io->error('Erreur : le hash généré ne peut pas être vérifié !');
            return Command::FAILURE;
        }

        $io->info(sprintf(
            'Nouveau mot de passe : %s (hashé avec BCRYPT, cost 13)',
            $plainPassword
        ));
        $io->note('Le hash a été testé et est valide.');

        // Demander confirmation
        if (!$io->confirm('Voulez-vous vraiment changer tous les mots de passe ?', false)) {
            $io->warning('Opération annulée');
            return Command::SUCCESS;
        }

        try {
            // On travaille au niveau SQL brut pour s'assurer de toucher TOUS les enregistrements,
            // même ceux que Doctrine n'hydraterait pas (anciens comptes, données de seed, etc.)
            $connection = $this->entityManager->getConnection();

            // Nombre total de lignes dans la table aiolia.utilisateurs
            $totalUsers = (int) $connection->fetchOne('SELECT COUNT(*) FROM aiolia.utilisateurs');

            if ($totalUsers === 0) {
                $io->warning('Aucun utilisateur trouvé dans la base de données');
                return Command::SUCCESS;
            }

            $io->section(sprintf('Mise à jour de %d utilisateur(s)', $totalUsers));

            // Mise à jour directe de la colonne hash_mot_de_passe pour tous les utilisateurs
            $updatedCount = $connection->executeStatement(
                'UPDATE aiolia.utilisateurs SET hash_mot_de_passe = :hash',
                ['hash' => $hashedPassword]
            );

            $io->success(sprintf(
                '%d utilisateur(s) mis à jour avec succès',
                $updatedCount
            ));

            $io->newLine();
            $io->section('Résumé');
            $io->table(
                ['Statut', 'Nombre'],
                [
                    ['Utilisateurs mis à jour (lignes affectées)', $updatedCount],
                    ['Total utilisateurs dans aiolia.utilisateurs', $totalUsers],
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

