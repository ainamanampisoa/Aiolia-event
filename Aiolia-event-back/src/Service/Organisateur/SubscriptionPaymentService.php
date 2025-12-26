<?php

namespace App\Service\Organisateur;

use App\Entity\SubscriptionInvoice;
use App\Entity\SubscriptionPlan;
use App\Entity\OrganizerProfile;
use App\Entity\OrganizerSubscription;
use App\Entity\User;
use App\Repository\Admin\SubscriptionInvoiceRepository;
use App\Repository\Admin\OrganizerSubscriptionRepository;
use App\Repository\Organisateur\PaiementAbonnementRepository;
use App\Service\Organisateur\InvoiceNumberService;
use App\Service\Organisateur\InvoiceEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SubscriptionPaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvoiceNumberService $invoiceNumberService,
        private SubscriptionInvoiceRepository $invoiceRepository,
        private PaiementAbonnementRepository $planRepository,
        private InvoiceEmailService $invoiceEmailService,
        private OrganizerSubscriptionRepository $subscriptionRepository,
        private ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Traite un paiement réussi : envoie l'email puis crée la facture et l'abonnement
     */
    public function processPaymentSuccess(
        User $user,
        SubscriptionPlan $plan,
        float $amount,
        string $paymentMethod = 'mvola',
        ?string $transactionReference = null,
        ?string $serverCorrelationId = null
    ): array {
        try {
            // Récupérer le profil organisateur
            $organizerProfile = $this->entityManager->getRepository(OrganizerProfile::class)
                ->findOneBy(['utilisateur' => $user]);

            if (!$organizerProfile) {
                return [
                    'success' => false,
                    'email_sent' => false,
                    'error' => 'Profil organisateur non trouvé pour cet utilisateur.'
                ];
            }

            // 1. Créer ou mettre à jour l'abonnement (sans toucher aux factures existantes)
            $subscription = $this->createOrUpdateSubscription($organizerProfile, $plan);

            // 2. Déterminer le mois de départ pour les NOUVELLES factures
            $startBillingMonth = $this->getNextAvailableBillingMonth($subscription->getId(), $plan->getPeriodeFacturation());
            
            error_log('[SubscriptionPaymentService] Prochain mois disponible pour facturation: ' . $startBillingMonth->format('Y-m-d'));

            // 3. Calculer les montants avec TVA
            $vatRate = (float) $plan->getTauxTva();
            $subtotal = $amount / (1 + ($vatRate / 100));
            $taxAmount = $amount - $subtotal;

            // 4. Déterminer le nombre de mois selon la période
            $billingPeriod = $plan->getPeriodeFacturation();
            $numberOfMonths = match($billingPeriod) {
                'monthly' => 1,
                'quarterly' => 3,
                'yearly' => 12,
                default => 1
            };

            // 5. Vérifier qu'il n'y a pas déjà des factures pour ces mois
            $existingMonths = $this->getExistingInvoiceMonths($subscription->getId(), $startBillingMonth, $numberOfMonths);
            
            if (count($existingMonths) > 0) {
                error_log('[SubscriptionPaymentService] Factures existantes trouvées pour les mois suivants: ' . implode(', ', $existingMonths));
                // On ne devrait pas arriver ici car getNextAvailableBillingMonth devrait trouver un mois disponible
                // Mais si c'est le cas, on ajuste le mois de départ
                $startBillingMonth->modify('+' . $numberOfMonths . ' months');
                $startBillingMonth->modify('first day of this month');
                error_log('[SubscriptionPaymentService] Nouveau mois de départ après ajustement: ' . $startBillingMonth->format('Y-m-d'));
            }

            // 6. Calculer le montant par mois
            $amountPerMonth = round($amount / $numberOfMonths, 2);
            $subtotalPerMonth = round($subtotal / $numberOfMonths, 2);
            $taxAmountPerMonth = round($taxAmount / $numberOfMonths, 2);
            
            // Ajustements pour la dernière facture
            $totalAmountCalculated = $amountPerMonth * $numberOfMonths;
            $totalSubtotalCalculated = $subtotalPerMonth * $numberOfMonths;
            $totalTaxCalculated = $taxAmountPerMonth * $numberOfMonths;
            
            $amountDifference = $amount - $totalAmountCalculated;
            $subtotalDifference = $subtotal - $totalSubtotalCalculated;
            $taxDifference = $taxAmount - $totalTaxCalculated;

            // 7. Générer tous les numéros de facture
            $invoiceNumbers = $this->invoiceNumberService->generateMultipleSubscriptionInvoiceNumbers($numberOfMonths);

            // 8. Créer les NOUVELLES factures
            $invoices = [];
            $firstInvoice = null;
            $allEmailsSent = true;

            for ($i = 0; $i < $numberOfMonths; $i++) {
                // Calculer le mois de facturation pour cette facture
                $billingMonth = clone $startBillingMonth;
                $billingMonth->modify("+{$i} months");
                $billingMonthFormatted = $billingMonth->format('Y-m-01');
                
                // Vérifier à nouveau qu'il n'y a pas déjà une facture pour ce mois
                $existingInvoice = $this->subscriptionRepository->findInvoiceForMonth(
                    $subscription->getId(),
                    $billingMonthFormatted
                );
                
                if ($existingInvoice) {
                    error_log('[SubscriptionPaymentService] Facture déjà existante pour ' . $billingMonthFormatted . ' - Skipping');
                    continue; // Passer au mois suivant
                }

                // Utiliser le numéro de facture pré-généré
                $invoiceNumber = $invoiceNumbers[$i];

                // Calculer les montants finaux (avec ajustement pour la dernière facture)
                $finalAmount = $amountPerMonth;
                $finalSubtotal = $subtotalPerMonth;
                $finalTax = $taxAmountPerMonth;
                
                if ($i === $numberOfMonths - 1) {
                    $finalAmount += $amountDifference;
                    $finalSubtotal += $subtotalDifference;
                    $finalTax += $taxDifference;
                }

                // Créer la facture
                $invoice = $this->createInvoice(
                    $subscription,
                    $user,
                    $plan,
                    $finalAmount,
                    $finalSubtotal,
                    $finalTax,
                    $paymentMethod,
                    $transactionReference,
                    $serverCorrelationId,
                    $invoiceNumber,
                    $billingMonth
                );

                // Pour la première facture, envoyer l'email
                if ($i === 0) {
                    $emailSent = $this->invoiceEmailService->sendSubscriptionInvoice($invoice);
                    if (!$emailSent) {
                        if ($this->logger) {
                            $this->logger->error('Échec de l\'envoi de l\'email de facture', [
                                'invoice_number' => $invoiceNumber,
                                'user_id' => $user->getId(),
                                'plan_id' => $plan->getId(),
                            ]);
                        }
                        $allEmailsSent = false;
                    }
                }

                // Marquer comme payée
                $invoice->markAsPaid();
                $paidAt = \DateTime::createFromImmutable(new \DateTimeImmutable());
                $invoice->setPaidAt($paidAt);
                $invoice->setStatus(SubscriptionInvoice::STATUS_PAID);

                // Sauvegarder la facture
                $this->entityManager->persist($invoice);
                $invoices[] = $invoice;

                if ($i === 0) {
                    $firstInvoice = $invoice;
                }
                
                error_log('[SubscriptionPaymentService] Facture créée pour ' . $billingMonthFormatted . ' - Numéro: ' . $invoiceNumber);
            }

            // 9. Flush toutes les nouvelles factures
            $this->entityManager->flush();

            // 10. Mettre à jour les dates de l'abonnement
            $this->updateSubscriptionDates($subscription, $startBillingMonth, $numberOfMonths);

            if (empty($invoices)) {
                return [
                    'success' => false,
                    'email_sent' => false,
                    'error' => 'Aucune nouvelle facture créée. Tous les mois avaient déjà des factures.',
                ];
            }

            if (!$allEmailsSent) {
                return [
                    'success' => false,
                    'email_sent' => false,
                    'error' => 'L\'email n\'a pas pu être envoyé. Les factures ont été créées.',
                    'invoice_number' => $firstInvoice ? $firstInvoice->getInvoiceNumber() : null,
                ];
            }

            if ($this->logger) {
                $this->logger->info('Nouvelles factures d\'abonnement créées', [
                    'invoice_count' => count($invoices),
                    'first_invoice_id' => $firstInvoice ? $firstInvoice->getId() : null,
                    'first_invoice_number' => $firstInvoice ? $firstInvoice->getInvoiceNumber() : null,
                    'subscription_id' => $subscription->getId(),
                    'total_amount' => $amount,
                    'billing_period' => $billingPeriod,
                    'start_billing_month' => $startBillingMonth->format('Y-m-d'),
                    'user_id' => $user->getId(),
                    'email_sent' => true,
                ]);
            }

            return [
                'success' => true,
                'email_sent' => true,
                'subscription' => $subscription,
                'invoice' => $firstInvoice,
                'invoices' => $invoices,
                'invoice_number' => $firstInvoice ? $firstInvoice->getInvoiceNumber() : null,
                'start_billing_month' => $startBillingMonth->format('Y-m-d'),
            ];

        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Erreur lors du traitement du paiement d\'abonnement', [
                    'user_id' => $user->getId(),
                    'plan_id' => $plan->getId(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return [
                'success' => false,
                'email_sent' => false,
                'error' => 'Erreur lors du traitement du paiement: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crée ou met à jour un abonnement SANS toucher aux factures existantes
     */
    private function createOrUpdateSubscription(
        OrganizerProfile $organizerProfile,
        SubscriptionPlan $plan
    ): OrganizerSubscription {
        // Chercher un abonnement existant pour ce profil
        $existingSubscription = $this->entityManager->getRepository(OrganizerSubscription::class)
            ->findOneBy(['organizerProfile' => $organizerProfile]);

        if ($existingSubscription) {
            // NE PAS modifier le plan de l'abonnement existant pour ne pas affecter les factures existantes
            // Les nouvelles factures seront créées avec le nouveau plan, mais l'abonnement existant
            // garde son plan d'origine pour préserver l'historique
            
            // Si l'abonnement était en pause, le reprendre
            if ($existingSubscription->getStatut() === OrganizerSubscription::STATUS_PAUSED) {
                $existingSubscription->setStatut(OrganizerSubscription::STATUS_ACTIVE);
                $reprisLe = \DateTime::createFromImmutable(new \DateTimeImmutable());
                $existingSubscription->setReprisLe($reprisLe);
                $this->entityManager->flush();
            }
            
            // Retourner l'abonnement existant sans modifier son plan
            // Les nouvelles factures seront créées avec le nouveau plan passé en paramètre
            return $existingSubscription;
        }

        // Créer un nouvel abonnement
        $subscription = new OrganizerSubscription();
        $subscription->setOrganizerProfile($organizerProfile);
        $subscription->setPlan($plan);
        $subscription->setStatut(OrganizerSubscription::STATUS_ACTIVE);
        
        $commenceLe = \DateTime::createFromImmutable(new \DateTimeImmutable());
        $subscription->setCommenceLe($commenceLe);
        
        // Les dates de période seront mises à jour plus tard lors de la création des factures
        $subscription->setDebutPeriodeCourante($commenceLe);
        
        // La fin de période sera calculée après la création des factures
        $finPeriode = clone $commenceLe;
        $subscription->setFinPeriodeCourante($finPeriode);

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return $subscription;
    }

    /**
     * Trouve le prochain mois disponible pour la facturation
     */
    private function getNextAvailableBillingMonth(int $subscriptionId, string $period): \DateTime
    {
        // 1. Chercher le prochain mois de facturation depuis le service
        $nextBillingMonth = new \DateTime('first day of next month');
        
        // 2. Vérifier s'il y a déjà une facture pour ce mois
        $existingInvoice = $this->subscriptionRepository->findInvoiceForMonth(
            $subscriptionId,
            $nextBillingMonth->format('Y-m-01')
        );
        
        // 3. Si une facture existe, trouver le premier mois disponible
        if ($existingInvoice) {
            error_log('[getNextAvailableBillingMonth] Facture existante pour ' . $nextBillingMonth->format('Y-m-d') . ', recherche du prochain mois disponible...');
            
            $maxIterations = 36; // 3 ans maximum
            $iterations = 0;
            
            do {
                $nextBillingMonth->modify('+1 month');
                $nextBillingMonth->modify('first day of this month');
                
                $existingInvoice = $this->subscriptionRepository->findInvoiceForMonth(
                    $subscriptionId,
                    $nextBillingMonth->format('Y-m-01')
                );
                
                $iterations++;
                
                if ($iterations >= $maxIterations) {
                    error_log('[getNextAvailableBillingMonth] Limite de sécurité atteinte');
                    break;
                }
                
            } while ($existingInvoice);
            
            error_log('[getNextAvailableBillingMonth] Prochain mois disponible trouvé: ' . $nextBillingMonth->format('Y-m-d') . ' (après ' . $iterations . ' itérations)');
        } else {
            error_log('[getNextAvailableBillingMonth] Pas de facture pour ' . $nextBillingMonth->format('Y-m-d') . ' - Mois disponible');
        }
        
        return $nextBillingMonth;
    }

    /**
     * Récupère les mois pour lesquels des factures existent déjà
     */
    private function getExistingInvoiceMonths(int $subscriptionId, \DateTime $startMonth, int $numberOfMonths): array
    {
        $existingMonths = [];
        
        for ($i = 0; $i < $numberOfMonths; $i++) {
            $checkMonth = clone $startMonth;
            $checkMonth->modify("+{$i} months");
            $checkMonthFormatted = $checkMonth->format('Y-m-01');
            
            $existingInvoice = $this->subscriptionRepository->findInvoiceForMonth(
                $subscriptionId,
                $checkMonthFormatted
            );
            
            if ($existingInvoice) {
                $existingMonths[] = $checkMonthFormatted;
            }
        }
        
        return $existingMonths;
    }

    /**
     * Crée une nouvelle facture
     */
    private function createInvoice(
        OrganizerSubscription $subscription,
        User $customer,
        SubscriptionPlan $plan,
        float $totalAmount,
        float $subtotal,
        float $taxAmount,
        string $paymentMethod,
        ?string $transactionReference = null,
        ?string $serverCorrelationId = null,
        string $invoiceNumber,
        ?\DateTime $billingMonth = null
    ): SubscriptionInvoice {
        // Créer la facture
        $invoice = new SubscriptionInvoice();
        $invoice->setInvoiceNumber($invoiceNumber);
        $invoice->setSubscriptionId((string) $subscription->getId());
        $invoice->setCustomer($customer);
        $invoice->setCurrency($plan->getDevise());
        $invoice->setSubtotalAmount((string) $subtotal);
        $invoice->setTaxAmount((string) $taxAmount);
        $invoice->setTotalAmount((string) $totalAmount);
        $invoice->setAmountHt((string) $subtotal);
        $invoice->setAmountTva((string) $taxAmount);
        $invoice->setAmountTtc((string) $totalAmount);
        
        // Mois de facturation
        if ($billingMonth === null) {
            $billingMonth = new \DateTime('first day of next month');
        } else {
            $billingMonth = clone $billingMonth;
            $billingMonth->modify('first day of this month');
        }
        $invoice->setBillingMonth($billingMonth);
        
        // Date d'échéance = 10ème jour du mois de facturation
        $dueDate = clone $billingMonth;
        $dueDate->modify('+9 days')->setTime(23, 59, 59);
        $invoice->setDueAt($dueDate);
        
        $issuedAt = \DateTime::createFromImmutable(new \DateTimeImmutable());
        $invoice->setIssuedAt($issuedAt);
        $invoice->setStatus(SubscriptionInvoice::STATUS_ISSUED);
        $invoice->setIsPrepaid(false);

        // Métadonnées
        $metadata = [
            'payment_method' => $paymentMethod,
            'plan_code' => $plan->getCode(),
            'plan_niveau' => $plan->getNiveau(),
            'plan_periode' => $plan->getPeriodeFacturation(),
            'transaction_reference' => $transactionReference,
            'server_correlation_id' => $serverCorrelationId,
            'paid_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        $invoice->setMetadata($metadata);

        return $invoice;
    }

    /**
     * Met à jour les dates de l'abonnement après création des factures
     */
    private function updateSubscriptionDates(
        OrganizerSubscription $subscription,
        \DateTime $startBillingMonth,
        int $numberOfMonths
    ): void {
        // Date de début de période = premier jour du premier mois de facturation
        $debutPeriode = clone $startBillingMonth;
        $debutPeriode->modify('first day of this month');
        
        // Date de fin de période = dernier jour du dernier mois de facturation
        $finPeriode = clone $startBillingMonth;
        $finPeriode->modify('+' . ($numberOfMonths - 1) . ' months');
        $finPeriode->modify('last day of this month')->setTime(23, 59, 59);
        
        $subscription->setDebutPeriodeCourante($debutPeriode);
        $subscription->setFinPeriodeCourante($finPeriode);
        
        $this->entityManager->flush();
        
        error_log('[updateSubscriptionDates] Abonnement mis à jour:');
        error_log('  - Début période: ' . $debutPeriode->format('Y-m-d H:i:s'));
        error_log('  - Fin période: ' . $finPeriode->format('Y-m-d H:i:s'));
    }

    /**
     * Calcule le prochain mois de facturation pour un utilisateur
     * (Cette méthode est utilisée par le contrôleur pour afficher le mois suivant)
     */
    public function getNextBillingMonth(User $user): \DateTime
    {
        try {
            // Récupérer la dernière facture de l'utilisateur pour trouver le dernier mois de facturation
            $lastInvoice = $this->invoiceRepository->findLastInvoiceByUser($user);

            if ($lastInvoice) {
                $lastBillingMonth = $lastInvoice->getBillingMonth();
                $nextBillingMonth = clone $lastBillingMonth;
                $nextBillingMonth->modify('first day of this month')->setTime(0, 0, 0);
                // Ajouter 1 mois au dernier mois de facturation
                $nextBillingMonth->modify('+1 month');
                
                error_log('[SubscriptionPaymentService::getNextBillingMonth] Prochain mois calculé: ' . $nextBillingMonth->format('Y-m-d') . ' (dernier mois de facturation: ' . $lastBillingMonth->format('Y-m-d') . ')');
                return $nextBillingMonth;
            }
            
            // Si aucune facture trouvée, retourner le mois suivant
            $nextMonth = new \DateTime('first day of next month');
            $nextMonth->setTime(0, 0, 0);
            error_log('[SubscriptionPaymentService::getNextBillingMonth] Aucune facture trouvée, mois suivant par défaut: ' . $nextMonth->format('Y-m-d'));
            return $nextMonth;
            
        } catch (\Exception $e) {
            error_log('[SubscriptionPaymentService::getNextBillingMonth] Erreur: ' . $e->getMessage());
            // En cas d'erreur, retourner le mois suivant par défaut
            $nextMonth = new \DateTime('first day of next month');
            $nextMonth->setTime(0, 0, 0);
            return $nextMonth;
        }
    }
}