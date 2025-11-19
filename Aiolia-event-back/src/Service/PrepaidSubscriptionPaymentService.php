<?php

namespace App\Service;

use App\Entity\SubscriptionInvoice;
use App\Entity\User;
use App\Repository\SubscriptionInvoiceRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer les paiements en avance pour plusieurs mois d'abonnement
 * 
 * Ce service permet aux organisateurs de payer plusieurs mois d'abonnement à l'avance.
 * Les mois payés sont stockés comme crédit prépayé et seront consommés automatiquement
 * lors de la génération mensuelle des factures.
 */
class PrepaidSubscriptionPaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvoiceNumberService $invoiceNumberService,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Traite un paiement en avance pour plusieurs mois d'abonnement
     * 
     * @param int $subscriptionId ID de l'abonnement
     * @param int $numberOfMonths Nombre de mois à payer en avance
     * @param float $totalAmount Montant total payé
     * @param string $paymentProvider Fournisseur de paiement (espace, orange, airtel, telma, bank_transfer)
     * @param string|null $paymentReference Référence du paiement
     * @param array|null $metadata Métadonnées additionnelles
     * @return array{success: bool, subscription_id: int, prepaid_months: int, invoices_created: array, errors: array}
     */
    public function processPrepaidPayment(
        int $subscriptionId,
        int $numberOfMonths,
        float $totalAmount,
        string $paymentProvider,
        ?string $paymentReference = null,
        ?array $metadata = null
    ): array {
        $result = [
            'success' => false,
            'subscription_id' => $subscriptionId,
            'prepaid_months' => 0,
            'invoices_created' => [],
            'errors' => [],
        ];

        $connection = $this->entityManager->getConnection();

        try {
            $connection->beginTransaction();

            // Récupérer les informations de l'abonnement
            $subscription = $this->getSubscriptionInfo($subscriptionId);
            if (!$subscription) {
                $result['errors'][] = "Abonnement introuvable : {$subscriptionId}";
                return $result;
            }

            // Vérifier que l'abonnement est actif
            if ($subscription['statut'] !== 'active') {
                $result['errors'][] = "L'abonnement doit être actif pour effectuer un paiement en avance. Statut actuel : {$subscription['statut']}";
                return $result;
            }

            // Récupérer les informations du plan (incluant le niveau/type d'offre)
            $plan = $this->getPlanInfo($subscription['id_plan']);
            if (!$plan) {
                $result['errors'][] = "Plan d'abonnement introuvable ou inactif";
                return $result;
            }

            // Vérifier que le plan est toujours actif et correspond à l'abonnement
            if (!isset($plan['niveau']) || !in_array($plan['niveau'], ['basic', 'pro', 'enterprise'])) {
                $result['errors'][] = "Type d'offre (niveau) invalide pour le plan : " . ($plan['niveau'] ?? 'non défini');
                return $result;
            }

            // Calculer le montant attendu
            $expectedAmount = $this->calculateExpectedAmount($plan, $numberOfMonths);
            
            // Vérifier que le montant payé correspond (avec une tolérance de 0.01)
            if (abs($totalAmount - $expectedAmount) > 0.01) {
                $result['errors'][] = sprintf(
                    "Le montant payé (%.2f) ne correspond pas au montant attendu (%.2f) pour %d mois",
                    $totalAmount,
                    $expectedAmount,
                    $numberOfMonths
                );
                return $result;
            }

            // Récupérer l'utilisateur (organisateur)
            $user = $this->entityManager->getRepository(User::class)->find($subscription['id_utilisateur']);
            if (!$user) {
                $result['errors'][] = "Utilisateur introuvable pour l'abonnement";
                return $result;
            }

            // Créer les factures prépayées pour chaque mois
            $currentDate = new \DateTimeImmutable();
            $invoicesCreated = [];

            for ($i = 0; $i < $numberOfMonths; $i++) {
                // Calculer le mois de facturation (mois suivant + i mois)
                $billingMonth = $currentDate->modify("first day of +{$i} month");
                $monthStart = $billingMonth->format('Y-m-01');
                
                // Vérifier si une facture existe déjà pour ce mois
                $existingInvoice = $this->findExistingInvoice($subscriptionId, $monthStart);
                
                if ($existingInvoice) {
                    // Si la facture existe déjà et n'est pas prépayée, on la met à jour
                    if (!$existingInvoice['est_prepayee']) {
                        $this->updateInvoiceToPrepaid($existingInvoice['id'], $plan, $monthStart);
                        $invoicesCreated[] = [
                            'invoice_id' => $existingInvoice['id'],
                            'invoice_number' => $existingInvoice['numero_facture'],
                            'billing_month' => $monthStart,
                            'action' => 'updated',
                        ];
                    } else {
                        // Facture prépayée déjà existante, on la skip
                        $invoicesCreated[] = [
                            'invoice_id' => $existingInvoice['id'],
                            'invoice_number' => $existingInvoice['numero_facture'],
                            'billing_month' => $monthStart,
                            'action' => 'skipped',
                        ];
                    }
                } else {
                    // Créer une nouvelle facture prépayée
                    $invoice = $this->createPrepaidInvoice(
                        $subscriptionId,
                        $user,
                        $plan,
                        $monthStart,
                        $i + 1,
                        $numberOfMonths
                    );
                    
                    $this->entityManager->persist($invoice);
                    $this->entityManager->flush();
                    
                    $invoicesCreated[] = [
                        'invoice_id' => $invoice->getId(),
                        'invoice_number' => $invoice->getInvoiceNumber(),
                        'billing_month' => $monthStart,
                        'action' => 'created',
                    ];
                }
            }

            // Enregistrer le paiement
            $paymentId = $this->recordPayment(
                $subscriptionId,
                $totalAmount,
                $paymentProvider,
                $paymentReference,
                $metadata
            );

            // Mettre à jour le crédit prépayé dans l'abonnement
            $this->updatePrepaidCredit($subscriptionId, $numberOfMonths);

            $connection->commit();

            $result['success'] = true;
            $result['prepaid_months'] = $numberOfMonths;
            $result['invoices_created'] = $invoicesCreated;

            if ($this->logger) {
                $this->logger->info('Paiement prépayé traité avec succès', [
                    'subscription_id' => $subscriptionId,
                    'number_of_months' => $numberOfMonths,
                    'total_amount' => $totalAmount,
                    'payment_provider' => $paymentProvider,
                    'invoices_created' => count($invoicesCreated),
                ]);
            }

        } catch (\Exception $e) {
            $connection->rollBack();
            $result['errors'][] = "Erreur lors du traitement du paiement prépayé : " . $e->getMessage();
            
            if ($this->logger) {
                $this->logger->error('Erreur lors du traitement du paiement prépayé', [
                    'subscription_id' => $subscriptionId,
                    'exception' => $e,
                ]);
            }
        }

        return $result;
    }

    /**
     * Récupère les informations d'un abonnement
     */
    private function getSubscriptionInfo(int $subscriptionId): ?array
    {
        $connection = $this->entityManager->getConnection();
        
        $sql = "
            SELECT 
                os.id,
                os.id_profil_organisateur,
                os.statut,
                os.mois_prepayes_restants,
                os.id_plan,
                op.id_utilisateur
            FROM aiolia.abonnements_organisateurs os
            INNER JOIN aiolia.profils_organisateurs op ON op.id = os.id_profil_organisateur
            WHERE os.id = :subscription_id
        ";
        
        $result = $connection->fetchAssociative($sql, ['subscription_id' => $subscriptionId]);
        
        return $result ?: null;
    }

    /**
     * Récupère les informations d'un plan d'abonnement
     */
    private function getPlanInfo(int $planId): ?array
    {
        $connection = $this->entityManager->getConnection();
        
        $sql = "
            SELECT 
                sp.id,
                sp.code,
                sp.nom,
                sp.niveau,
                sp.prix,
                sp.taux_tva,
                sp.devise,
                sp.periode_facturation,
                sp.nombre_periodes
            FROM aiolia.plans_abonnements sp
            WHERE sp.id = :plan_id
                AND sp.est_actif = true
        ";
        
        $result = $connection->fetchAssociative($sql, ['plan_id' => $planId]);
        
        return $result ?: null;
    }

    /**
     * Calcule le montant attendu pour un nombre de mois donné
     * Prend en compte le type d'offre (niveau) et la période de facturation
     */
    private function calculateExpectedAmount(array $plan, int $numberOfMonths): float
    {
        $monthlyPrice = $plan['prix'];
        
        // Calculer le prix mensuel selon le type d'offre et la période de facturation
        switch ($plan['periode_facturation']) {
            case 'yearly':
                // Pour un abonnement annuel, diviser le prix par 12
                $monthlyPrice = $plan['prix'] / 12;
                break;
            case 'quarterly':
                // Pour un abonnement trimestriel, diviser le prix par 3
                $monthlyPrice = $plan['prix'] / 3;
                break;
            case 'monthly':
            default:
                // Abonnement mensuel : prix mensuel
                $monthlyPrice = $plan['prix'];
                break;
        }
        
        $vatRate = (float) ($plan['taux_tva'] ?? 20);
        $subtotal = $monthlyPrice * $numberOfMonths;
        $taxAmount = $subtotal * ($vatRate / 100);
        $total = $subtotal + $taxAmount;
        
        return (float) $total;
    }

    /**
     * Trouve une facture existante pour un mois donné
     */
    private function findExistingInvoice(int $subscriptionId, string $billingMonth): ?array
    {
        $connection = $this->entityManager->getConnection();
        
        $sql = "
            SELECT 
                id,
                numero_facture,
                est_prepayee,
                statut
            FROM aiolia.factures_abonnements
            WHERE id_abonnement = :subscription_id
                AND mois_facturation = :billing_month
        ";
        
        $result = $connection->fetchAssociative($sql, [
            'subscription_id' => $subscriptionId,
            'billing_month' => $billingMonth,
        ]);
        
        return $result ?: null;
    }

    /**
     * Met à jour une facture existante pour la marquer comme prépayée
     * Prend en compte le type d'offre (niveau) et la période de facturation
     */
    private function updateInvoiceToPrepaid(int $invoiceId, array $plan, string $billingMonth): void
    {
        $connection = $this->entityManager->getConnection();
        
        $vatRate = (float) ($plan['taux_tva'] ?? 20);
        $monthlyPrice = $plan['prix'];
        
        // Calculer le prix mensuel selon le type d'offre et la période de facturation
        switch ($plan['periode_facturation']) {
            case 'yearly':
                $monthlyPrice = $plan['prix'] / 12;
                break;
            case 'quarterly':
                $monthlyPrice = $plan['prix'] / 3;
                break;
            case 'lifetime':
                $monthlyPrice = 0;
                break;
            case 'monthly':
            default:
                $monthlyPrice = $plan['prix'];
                break;
        }
        
        $subtotal = $monthlyPrice;
        $taxAmount = $subtotal * ($vatRate / 100);
        $total = $subtotal + $taxAmount;
        
        $sql = "
            UPDATE aiolia.factures_abonnements
            SET est_prepayee = true,
                statut = 'pending',
                montant_sous_total = :subtotal,
                montant_tva = :tax_amount,
                montant_total = :total,
                montant_ht = :subtotal,
                montant_tva_detail = :tax_amount,
                montant_ttc = :total,
                modifie_le = now(),
                metadonnees = COALESCE(metadonnees, '{}'::jsonb) || jsonb_build_object(
                    'prepaid_at', now(),
                    'billing_month', :billing_month,
                    'plan_level', :plan_level,
                    'plan_code', :plan_code,
                    'plan_name', :plan_name
                )
            WHERE id = :invoice_id
        ";
        
        $connection->executeStatement($sql, [
            'invoice_id' => $invoiceId,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'billing_month' => $billingMonth,
            'plan_level' => $plan['niveau'] ?? 'basic',
            'plan_code' => $plan['code'] ?? null,
            'plan_name' => $plan['nom'] ?? null,
        ]);
    }

    /**
     * Crée une nouvelle facture prépayée
     * Prend en compte le type d'offre (niveau) et la période de facturation
     */
    private function createPrepaidInvoice(
        int $subscriptionId,
        User $user,
        array $plan,
        string $billingMonth,
        int $monthIndex,
        int $totalMonths
    ): SubscriptionInvoice {
        $vatRate = (float) ($plan['taux_tva'] ?? 20);
        $monthlyPrice = $plan['prix'];
        
        // Calculer le prix mensuel selon le type d'offre et la période de facturation
        switch ($plan['periode_facturation']) {
            case 'yearly':
                $monthlyPrice = $plan['prix'] / 12;
                break;
            case 'quarterly':
                $monthlyPrice = $plan['prix'] / 3;
                break;
            case 'lifetime':
                $monthlyPrice = 0;
                break;
            case 'monthly':
            default:
                $monthlyPrice = $plan['prix'];
                break;
        }
        
        $subtotal = $monthlyPrice;
        $taxAmount = $subtotal * ($vatRate / 100);
        $total = $subtotal + $taxAmount;
        
        $invoice = new SubscriptionInvoice();
        $invoice->setInvoiceNumber($this->invoiceNumberService->generateSubscriptionInvoiceNumber());
        $invoice->setSubscriptionId((string) $subscriptionId);
        $invoice->setCustomer($user);
        $invoice->setCurrency($plan['devise'] ?? 'MGA');
        $invoice->setSubtotalAmount((string) $subtotal);
        $invoice->setTaxAmount((string) $taxAmount);
        $invoice->setTotalAmount((string) $total);
        $invoice->setAmountHt((string) $subtotal);
        $invoice->setAmountTva((string) $taxAmount);
        $invoice->setAmountTtc((string) $total);
        $invoice->setBillingMonth(new \DateTimeImmutable($billingMonth));
        $invoice->setIsPrepaid(true);
        $invoice->setIsPauseMonth(false);
        $invoice->setStatus('pending'); // pending pour factures prépayées
        $invoice->setIssuedAt(new \DateTimeImmutable($billingMonth));
        $invoice->setDueAt(null); // Pas d'échéance pour les factures prépayées
        $invoice->setMetadata([
            'prepaid' => true,
            'prepaid_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'month_index' => $monthIndex,
            'total_prepaid_months' => $totalMonths,
            'billing_period' => $plan['periode_facturation'],
            'plan_level' => $plan['niveau'] ?? 'basic',
            'plan_code' => $plan['code'] ?? null,
            'plan_name' => $plan['nom'] ?? null,
        ]);
        
        return $invoice;
    }

    /**
     * Enregistre le paiement dans la base de données
     */
    private function recordPayment(
        int $subscriptionId,
        float $totalAmount,
        string $paymentProvider,
        ?string $paymentReference,
        ?array $metadata
    ): int {
        $connection = $this->entityManager->getConnection();
        
        // Récupérer l'ID de la première facture prépayée créée pour ce paiement
        $sql = "
            SELECT id
            FROM aiolia.factures_abonnements
            WHERE id_abonnement = :subscription_id
                AND est_prepayee = true
                AND statut = 'pending'
            ORDER BY mois_facturation ASC
            LIMIT 1
        ";
        
        $firstInvoiceId = $connection->fetchOne($sql, ['subscription_id' => $subscriptionId]);
        
        if (!$firstInvoiceId) {
            throw new \RuntimeException("Aucune facture prépayée trouvée pour enregistrer le paiement");
        }
        
        // Créer l'enregistrement de paiement
        $sql = "
            INSERT INTO aiolia.paiements_abonnements (
                id_facture,
                fournisseur,
                reference_fournisseur,
                statut,
                montant,
                devise,
                paye_le,
                metadonnees,
                cree_le,
                modifie_le
            ) VALUES (
                :invoice_id,
                :provider,
                :reference,
                'paid',
                :amount,
                'MGA',
                now(),
                :metadata,
                now(),
                now()
            )
            RETURNING id
        ";
        
        $paymentId = $connection->fetchOne($sql, [
            'invoice_id' => $firstInvoiceId,
            'provider' => $paymentProvider,
            'reference' => $paymentReference,
            'amount' => $totalAmount,
            'metadata' => json_encode($metadata ?? []),
        ]);
        
        // Créer l'entrée dans l'historique
        $sql = "
            INSERT INTO aiolia.historique_paiements_abonnements (
                id_paiement,
                statut_de,
                statut_vers,
                modifie_le,
                metadonnees
            ) VALUES (
                :payment_id,
                NULL,
                'paid',
                now(),
                jsonb_build_object('detail', 'Paiement prépayé pour plusieurs mois')
            )
        ";
        
        $connection->executeStatement($sql, ['payment_id' => $paymentId]);
        
        return (int) $paymentId;
    }

    /**
     * Met à jour le crédit prépayé dans l'abonnement
     */
    private function updatePrepaidCredit(int $subscriptionId, int $numberOfMonths): void
    {
        $connection = $this->entityManager->getConnection();
        
        $sql = "
            UPDATE aiolia.abonnements_organisateurs
            SET mois_prepayes_restants = mois_prepayes_restants + :months,
                modifie_le = now()
            WHERE id = :subscription_id
        ";
        
        $connection->executeStatement($sql, [
            'subscription_id' => $subscriptionId,
            'months' => $numberOfMonths,
        ]);
    }

    /**
     * Récupère le nombre de mois prépayés restants pour un abonnement
     */
    public function getRemainingPrepaidMonths(int $subscriptionId): int
    {
        $connection = $this->entityManager->getConnection();
        
        $sql = "
            SELECT mois_prepayes_restants
            FROM aiolia.abonnements_organisateurs
            WHERE id = :subscription_id
        ";
        
        $result = $connection->fetchOne($sql, ['subscription_id' => $subscriptionId]);
        
        return (int) ($result ?? 0);
    }

    /**
     * Récupère toutes les factures prépayées en attente pour un abonnement
     */
    public function getPendingPrepaidInvoices(int $subscriptionId): array
    {
        $connection = $this->entityManager->getConnection();
        
        $sql = "
            SELECT 
                id,
                numero_facture,
                mois_facturation,
                montant_total,
                statut,
                est_prepayee
            FROM aiolia.factures_abonnements
            WHERE id_abonnement = :subscription_id
                AND est_prepayee = true
                AND statut = 'pending'
            ORDER BY mois_facturation ASC
        ";
        
        return $connection->fetchAllAssociative($sql, ['subscription_id' => $subscriptionId]);
    }
}

