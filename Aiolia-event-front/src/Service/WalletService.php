<?php

namespace App\Service;

use App\Repository\WalletRepository;
use App\Repository\WalletTransactionRepository;

class WalletService
{
    private const MAX_BALANCE = 5_000_000.0; // 5 millions MGA
    private const MAX_MONTHLY_RECHARGE = 1_000_000.0; // 1 million MGA/mois

    public function __construct(
        private readonly WalletRepository $walletRepository,
        private readonly WalletTransactionRepository $transactionRepository
    ) {
    }

    /**
     * Récupère ou crée le wallet d'un utilisateur.
     */
    public function getOrCreateWallet(int $userId): int
    {
        $wallet = $this->walletRepository->findWalletByUserId($userId);

        if ($wallet) {
            return $wallet['id'];
        }

        return $this->walletRepository->createWallet($userId);
    }

    /**
     * Récupère le solde et les points d'un utilisateur.
     */
    public function getWalletBalance(int $userId): array
    {
        return $this->walletRepository->getBalance($userId);
    }

    /**
     * Recharge le wallet d'un utilisateur.
     */
    public function rechargeWallet(
        int $userId,
        float $amount,
        string $paymentMethod,
        ?string $reference = null
    ): int {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif');
        }

        // Vérifier la limite mensuelle de recharge
        $monthlyRecharge = $this->getMonthlyRecharge($userId);
        if ($monthlyRecharge + $amount > self::MAX_MONTHLY_RECHARGE) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Limite de recharge mensuelle dépassée (max: %s MGA)',
                    number_format(self::MAX_MONTHLY_RECHARGE, 0, ',', ' ')
                )
            );
        }

        $walletId = $this->getOrCreateWallet($userId);
        $wallet = $this->walletRepository->findWalletByUserId($userId);
        
        if (!$wallet) {
            throw new \RuntimeException('Impossible de récupérer le wallet');
        }

        $newBalance = $wallet['balance'] + $amount;

        // Vérifier la limite de solde maximum
        if ($newBalance > self::MAX_BALANCE) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le solde maximum autorisé est de %s MGA',
                    number_format(self::MAX_BALANCE, 0, ',', ' ')
                )
            );
        }

        // Créer la transaction avec statut 'pending' (simulation du processus de paiement)
        $transactionId = $this->transactionRepository->createTransaction([
            'wallet_id' => $walletId,
            'transaction_type' => 'credit',
            'status' => 'pending',
            'amount' => $amount,
            'points_delta' => 0,
            'description' => sprintf('Recharge via %s%s', $paymentMethod, $reference ? " - Ref: $reference" : ''),
            'related_entity' => 'recharge',
        ]);

        // SIMULATION MODE : Simuler un paiement réussi après un court délai
        // En production, on appellerait ici l'API de paiement réelle (Mobile Money, Mvola, etc.)
        // et on attendrait le callback pour confirmer le paiement
        
        // Pour la simulation, on simule un délai de traitement (comme un vrai paiement)
        // En réalité, on pourrait utiliser un job queue ou un processus asynchrone
        // Pour l'instant, on simule un succès immédiat avec un petit délai
        
        // Simuler le traitement du paiement (délai de 500ms pour simuler l'appel API)
        usleep(500000); // 0.5 seconde
        
        // Simuler un succès (en production, ce serait basé sur la réponse de l'API)
        $paymentSuccess = true; // En simulation, toujours réussi
        
        if ($paymentSuccess) {
            // Mettre à jour le solde
            $this->walletRepository->updateBalance($walletId, $newBalance);
            
            // Marquer la transaction comme complétée
            $this->transactionRepository->updateTransactionStatus($transactionId, 'completed');
            
            // Log pour debug
            error_log(sprintf(
                '[Wallet] Recharge simulée réussie - User: %d, Montant: %s MGA, Nouveau solde: %s MGA',
                $userId,
                number_format($amount, 0, ',', ' '),
                number_format($newBalance, 0, ',', ' ')
            ));
        } else {
            // En cas d'échec (ne devrait pas arriver en simulation)
            $this->transactionRepository->updateTransactionStatus($transactionId, 'failed');
            throw new \RuntimeException('Le paiement a échoué');
        }

        return $transactionId;
    }

    /**
     * Débite le wallet d'un utilisateur.
     */
    public function debitWallet(
        int $userId,
        float $amount,
        string $description,
        string $relatedEntity,
        ?int $relatedId = null
    ): int {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif');
        }

        $walletId = $this->getOrCreateWallet($userId);
        $wallet = $this->walletRepository->findWalletByUserId($userId);

        if (!$wallet) {
            throw new \RuntimeException('Impossible de récupérer le wallet');
        }

        if ($wallet['balance'] < $amount) {
            throw new \RuntimeException('Solde insuffisant');
        }

        $newBalance = $wallet['balance'] - $amount;

        // Créer la transaction
        $transactionId = $this->transactionRepository->createTransaction([
            'wallet_id' => $walletId,
            'transaction_type' => 'debit',
            'status' => 'completed',
            'amount' => $amount,
            'points_delta' => 0,
            'description' => $description,
            'related_entity' => $relatedEntity,
            'related_id' => $relatedId,
        ]);

        // Mettre à jour le solde
        $this->walletRepository->updateBalance($walletId, $newBalance);

        return $transactionId;
    }

    /**
     * Crédite le wallet d'un utilisateur.
     */
    public function creditWallet(
        int $userId,
        float $amount,
        string $description,
        string $relatedEntity,
        ?int $relatedId = null
    ): int {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif');
        }

        $walletId = $this->getOrCreateWallet($userId);
        $wallet = $this->walletRepository->findWalletByUserId($userId);

        if (!$wallet) {
            throw new \RuntimeException('Impossible de récupérer le wallet');
        }

        $newBalance = $wallet['balance'] + $amount;

        // Vérifier la limite de solde maximum
        if ($newBalance > self::MAX_BALANCE) {
            $newBalance = self::MAX_BALANCE; // Plafonner au maximum
        }

        // Créer la transaction
        $transactionId = $this->transactionRepository->createTransaction([
            'wallet_id' => $walletId,
            'transaction_type' => 'credit',
            'status' => 'completed',
            'amount' => $amount,
            'points_delta' => 0,
            'description' => $description,
            'related_entity' => $relatedEntity,
            'related_id' => $relatedId,
        ]);

        // Mettre à jour le solde
        $this->walletRepository->updateBalance($walletId, $newBalance);

        return $transactionId;
    }

    /**
     * Vérifie si l'utilisateur a un solde suffisant.
     */
    public function hasSufficientBalance(int $userId, float $requiredAmount): bool
    {
        $balance = $this->walletRepository->getBalance($userId);
        return $balance['balance'] >= $requiredAmount;
    }

    /**
     * Transfère de l'argent d'un wallet à un autre.
     */
    public function transferToWallet(
        int $fromUserId,
        int $toUserId,
        float $amount,
        string $description
    ): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif');
        }

        // Débiter l'expéditeur
        $debitTransactionId = $this->debitWallet(
            $fromUserId,
            $amount,
            sprintf('Transfert vers utilisateur #%d - %s', $toUserId, $description),
            'transfer',
            $toUserId
        );

        // Créditer le destinataire
        $creditTransactionId = $this->creditWallet(
            $toUserId,
            $amount,
            sprintf('Transfert de l\'utilisateur #%d - %s', $fromUserId, $description),
            'transfer',
            $fromUserId
        );

        return [
            'debit_transaction_id' => $debitTransactionId,
            'credit_transaction_id' => $creditTransactionId,
        ];
    }

    /**
     * Récupère le total des recharges du mois en cours.
     */
    private function getMonthlyRecharge(int $userId): float
    {
        $startOfMonth = new \DateTime('first day of this month');
        $startOfMonth->setTime(0, 0, 0);

        $transactions = $this->transactionRepository->getTransactionHistory(
            $userId,
            $startOfMonth,
            null,
            'credit'
        );

        $total = 0.0;
        foreach ($transactions as $transaction) {
            if ($transaction['status'] === 'completed' && $transaction['related_entity'] === 'recharge') {
                $total += $transaction['amount'];
            }
        }

        return $total;
    }

    /**
     * Récupère le total des recharges du mois en cours (méthode publique).
     */
    public function getMonthlyRechargeTotal(int $userId): float
    {
        return $this->getMonthlyRecharge($userId);
    }
}

