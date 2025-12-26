<?php

namespace App\Controller\Admin;

use App\Entity\TicketInvoice;
use App\Entity\SubscriptionInvoice;
use App\Repository\Organisateur\TicketInvoiceRepository;
use App\Repository\Admin\SubscriptionInvoiceRepository;
use App\Service\Admin\BillingInvoiceDetailService;
use App\Service\Organisateur\InvoicePdfService;
use App\Service\Organisateur\InvoiceEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/billing')]
#[IsGranted('ROLE_ADMIN')]
class BillingController extends AbstractController
{
    public function __construct(
        private TicketInvoiceRepository $ticketInvoiceRepository,
        private SubscriptionInvoiceRepository $subscriptionInvoiceRepository,
        private InvoicePdfService $pdfService,
        private InvoiceEmailService $emailService,
        private BillingInvoiceDetailService $invoiceDetailService,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/invoices', name: 'admin_billing_invoices')]
    public function invoices(Request $request): Response
    {
        $status = $request->query->get('status');
        $search = $request->query->get('search');
        $month = $request->query->getInt('month', 0);
        $year = $request->query->getInt('year', 0);
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 7;

        $month = $this->validateMonth($month);
        $year = $this->validateYear($year);

        // Augmenter la limite pour récupérer suffisamment de factures pour le filtrage
        $fetchLimit = 10000;
        $monthFilter = $month > 0 ? $month : null;
        $yearFilter = $year > 0 ? $year : null;

        // Récupérer uniquement les factures d'abonnements
        // Les factures de tickets sont gérées séparément et ne doivent pas être mélangées
        // avec les factures d'abonnements pour éviter les factures fantômes
        $subscriptionInvoices = $this->subscriptionInvoiceRepository->findAllWithFilters(
            $status,
            $search,
            $monthFilter,
            $yearFilter,
            $fetchLimit,
            0
        );

        // Utiliser uniquement les factures d'abonnements
        $allInvoices = $subscriptionInvoices;
        
        // Filtrer les factures d'abonnement pour exclure les mois avant le premier mois disponible
        $allInvoices = $this->filterInvoicesByFirstAvailableMonth($allInvoices, $month, $year);
        
        // Trier par date d'émission décroissante (les plus récentes en premier)
        usort($allInvoices, function($a, $b) {
            // Utiliser issuedAt pour les deux types de factures (date d'émission)
            $dateA = $a->getIssuedAt();
            $dateB = $b->getIssuedAt();
            return $dateB <=> $dateA; // Ordre décroissant : les plus récentes en premier
        });
        
        // Recalculer le total après filtrage
        $totalInvoices = count($allInvoices);
        
        // Pagination
        $allInvoices = array_slice($allInvoices, ($page - 1) * $perPage, $perPage);

        // Identifier les types de factures et récupérer les informations
        $invoiceInfo = $this->processInvoicesInfo($allInvoices);

        // Statistiques
        $stats = $this->calculateStats($status, $search, $monthFilter, $yearFilter);

        return $this->render('@Admin/billing/invoices.html.twig', [
            'allInvoices' => $allInvoices,
            'planInfos' => $invoiceInfo['planInfos'],
            'invoiceTypes' => $invoiceInfo['types'],
            'billingDates' => $invoiceInfo['dates'],
            'stats' => $stats,
            'currentStatus' => $status,
            'currentSearch' => $search,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($totalInvoices / $perPage)),
            'totalInvoices' => $totalInvoices,
        ]);
    }

    private function validateMonth(int $month): int
    {
        return ($month >= 1 && $month <= 12) ? $month : 0;
    }

    private function validateYear(int $year): int
    {
        // Accepter 0 pour "Toutes les années"
        if ($year === 0) {
            return 0;
        }
        return ($year >= 2020 && $year <= 2100) ? $year : 0;
    }

    /**
     * Filtre les factures pour exclure celles avant le premier mois disponible
     * pour les abonnements et les tickets
     */
    private function filterInvoicesByFirstAvailableMonth(array $invoices, int $month, int $year): array
    {
        // Si aucune année n'est sélectionnée (0 = toutes les années), ne pas filtrer par année
        if ($year === 0) {
            // Si un mois spécifique est sélectionné, on ne peut pas filtrer sans année
            // Dans ce cas, retourner toutes les factures
            return $invoices;
        }

        // Toujours appliquer le filtre, même si un mois spécifique est sélectionné
        // car le repository peut retourner des résultats incorrects si le mois est invalide

        // Obtenir le premier mois disponible pour les factures d'abonnements
        $firstAvailableMonth = $this->subscriptionInvoiceRepository->getFirstAvailableMonthForYear($year);
        
        // Forcer le premier mois à juin 2025 si c'est l'année 2025
        // Cela garantit qu'aucune facture de mai n'apparaît
        if ($year === 2025 && $firstAvailableMonth < 6) {
            $firstAvailableMonth = 6;
        }
        
        // Si le premier mois est janvier (1), pas besoin de filtrer (sauf pour 2025)
        if ($firstAvailableMonth <= 1 && $year !== 2025) {
            return $invoices;
        }

        // Si un mois spécifique est sélectionné, vérifier qu'il n'est pas avant le premier mois disponible
        if ($month > 0 && $month < $firstAvailableMonth) {
            // Retourner un tableau vide si le mois demandé est invalide
            return [];
        }

        // Filtrer uniquement les factures d'abonnements (on n'a plus de factures de tickets)
        return array_filter($invoices, function($invoice) use ($firstAvailableMonth, $year, $month) {
            // Ne traiter que les factures d'abonnements
            if (!($invoice instanceof SubscriptionInvoice)) {
                return false;
            }
            
            $invoiceYear = null;
            $invoiceMonth = null;
            
            // Récupérer la date de facturation pour les factures d'abonnement
            $billingMonth = $invoice->getBillingMonth();
            if ($billingMonth instanceof \DateTimeInterface) {
                $invoiceYear = (int) $billingMonth->format('Y');
                $invoiceMonth = (int) $billingMonth->format('n');
            }
            
            // Exclure toutes les factures de mai 2025
            // C'est une règle métier : aucun service n'était actif avant juin 2025
            if ($invoiceYear === 2025 && $invoiceMonth === 5) {
                return false;
            }
            
            // Si l'année correspond et le mois est avant le premier mois disponible, exclure
            if ($invoiceYear === $year && $invoiceMonth !== null && $invoiceMonth < $firstAvailableMonth) {
                return false;
            }
            
            // Double vérification : si l'année est 2025 et le premier mois disponible est juin,
            // exclure explicitement tout ce qui est avant juin
            if ($year === 2025 && $firstAvailableMonth >= 6 && $invoiceYear === 2025 && $invoiceMonth !== null && $invoiceMonth < 6) {
                return false;
            }
            
            // Si un mois spécifique est sélectionné, ne garder que ce mois
            if ($month > 0 && $invoiceMonth !== $month) {
                return false;
            }
            
            // Garder toutes les autres factures d'abonnement
            return true;
        });
    }

    private function processInvoicesInfo(array $invoices): array
    {
        $invoiceIds = array_map(fn($inv) => $inv->getId(), $invoices);
        $connection = $this->entityManager->getConnection();

        // Identifier les factures d'abonnement via les transactions
        $subscriptionInvoiceIds = [];
        if (!empty($invoiceIds)) {
            $sql = "
                SELECT DISTINCT id_facture 
                FROM aiolia.transactions_paiement_mobile 
                WHERE id_facture IN (:invoice_ids)
            ";
            $results = $connection->fetchAllAssociative(
                $sql,
                ['invoice_ids' => $invoiceIds],
                ['invoice_ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
            );
            foreach ($results as $row) {
                $subscriptionInvoiceIds[(string) $row['id_facture']] = true;
            }
        }

        $types = [];
        $dates = [];
        $subscriptionInvoices = [];

        foreach ($invoices as $invoice) {
            $invoiceId = (string) $invoice->getId();
            
            // Déterminer le type
            if ($invoice instanceof SubscriptionInvoice || isset($subscriptionInvoiceIds[$invoiceId])) {
                $types[$invoiceId] = 'subscription';
                if ($invoice instanceof SubscriptionInvoice) {
                    $subscriptionInvoices[] = $invoice;
                    // Normaliser la date de facturation pour les factures d'abonnement
                    $billingMonth = $invoice->getBillingMonth();
                    if ($billingMonth instanceof \DateTimeInterface) {
                        $normalizedDate = \DateTimeImmutable::createFromInterface($billingMonth)
                            ->modify('first day of this month')
                            ->setTime(0, 0, 0);
                        $dates[$invoiceId] = $normalizedDate;
                    } else {
                        $dates[$invoiceId] = $invoice->getIssuedAt();
                    }
                } else {
                    // Si c'est identifié via les transactions mais pas une instance, utiliser issuedAt
                    $dates[$invoiceId] = $invoice->getIssuedAt();
                }
            } else {
                $types[$invoiceId] = 'ticket';
                $dates[$invoiceId] = $invoice->getIssuedAt();
            }
        }

        // Récupérer les informations de plan
        $planInfos = [];
        if (!empty($subscriptionInvoices)) {
            // Récupérer les informations de plan pour toutes les factures d'abonnement
            $planInfos = $this->subscriptionInvoiceRepository->getPlanInfosForInvoices($subscriptionInvoices);
            
            // Compléter avec les informations manquantes pour chaque facture d'abonnement
            foreach ($subscriptionInvoices as $invoice) {
                $invoiceId = (string) $invoice->getId();
                // Vérifier si la facture n'a pas de planInfo ou si le planInfo est vide/invalide
                if (!isset($planInfos[$invoiceId]) || 
                    !is_array($planInfos[$invoiceId]) || 
                    !isset($planInfos[$invoiceId]['niveau']) ||
                    $planInfos[$invoiceId]['niveau'] === null ||
                    $planInfos[$invoiceId]['niveau'] === '') {
                    // Essayer de récupérer individuellement
                    $planInfo = $this->subscriptionInvoiceRepository->getPlanInfoForInvoice($invoice);
                    if ($planInfo && is_array($planInfo) && isset($planInfo['niveau']) && 
                        $planInfo['niveau'] !== null && $planInfo['niveau'] !== '') {
                        $planInfos[$invoiceId] = $planInfo;
                    }
                }
            }
        }
        
        // Debug temporaire - à supprimer après résolution
        // Compter les factures par type pour comprendre le problème
        $debugSubscriptionCount = count($subscriptionInvoices);
        $debugTotalCount = count($invoices);
        $debugPlanInfosCount = count($planInfos);
        // Logger ou utiliser dd() pour débugger
        // $this->logger->info('BillingController Debug', [
        //     'total_invoices' => $debugTotalCount,
        //     'subscription_invoices' => $debugSubscriptionCount,
        //     'plan_infos_found' => $debugPlanInfosCount,
        //     'types' => $types,
        // ]);

        return [
            'types' => $types,
            'dates' => $dates,
            'planInfos' => $planInfos
        ];
    }

    private function calculateStats(?string $status, ?string $search, ?int $month, ?int $year): array
    {
        return [
            'total' => $this->countByStatus(null, $search, $month, $year),
            'paid' => $this->countByStatus('paid', $search, $month, $year),
            'pending' => $this->countByStatus('issued', $search, $month, $year)
                + $this->countByStatus('draft', $search, $month, $year)
                + $this->countByStatus('overdue', $search, $month, $year),
            'cancelled' => $this->countByStatus('void', $search, $month, $year) 
                + $this->countByStatus('refunded', $search, $month, $year),
        ];
    }

    private function countByStatus(?string $status, ?string $search, ?int $month, ?int $year): int
    {
        // Compter uniquement les factures d'abonnements
        // Les factures de tickets sont exclues pour éviter les factures fantômes
        return $this->subscriptionInvoiceRepository->countWithFilters($status, $search, $month, $year);
    }

    #[Route('/ticket-invoice/{id}', name: 'admin_billing_ticket_invoice_show', requirements: ['id' => '\d+'])]
    public function showTicketInvoice(string $id): Response
    {
        $invoice = $this->ticketInvoiceRepository->find($id);

        if (!$invoice instanceof TicketInvoice) {
            $this->addFlash('error', 'Facture introuvable');
            return $this->redirectToRoute('admin_billing_invoices');
        }

        return $this->render('@Admin/billing/invoice_show.html.twig', [
            'invoice' => $invoice,
            'type' => 'ticket',
            'invoiceItems' => [],
        ]);
    }

    #[Route('/subscription-invoice/{id}', name: 'admin_billing_subscription_invoice_show', requirements: ['id' => '\d+'])]
    public function showSubscriptionInvoice(string $id): Response
    {
        $invoice = $this->subscriptionInvoiceRepository->find($id);

        if (!$invoice instanceof SubscriptionInvoice) {
            $this->addFlash('error', 'Facture introuvable');
            return $this->redirectToRoute('admin_billing_invoices');
        }

        // Récupérer les informations de plan
        $planInfo = $this->invoiceDetailService->getPlanInfo($invoice);
        // Si planInfo est null ou vide, essayer de récupérer via le repository directement
        if (!$planInfo || (is_array($planInfo) && empty($planInfo))) {
            $planInfo = $this->subscriptionInvoiceRepository->getPlanInfoForInvoice($invoice);
        }
        // S'assurer que planInfo est null si vide (pas un tableau vide) pour que le template puisse vérifier correctement
        if (is_array($planInfo) && empty($planInfo)) {
            $planInfo = null;
        }
        $planTier = $planInfo && isset($planInfo['niveau']) ? $planInfo['niveau'] : null;

        // Organisateur et historique
        $organizer = $invoice->getCustomer();
        $previousInvoices = $this->getPreviousInvoices($invoice, $organizer);
        $previousInvoicesWithPlan = $this->enrichInvoicesWithPlanInfo($previousInvoices);

        $planChanged = $this->checkPlanChange($planInfo, $previousInvoicesWithPlan);

        // Récupérer le mode de paiement
        $paymentMethod = $this->invoiceDetailService->getPaymentMethod($invoice);
        
        return $this->render('@Admin/billing/invoice_show.html.twig', [
            'invoice' => $invoice,
            'type' => 'subscription',
            'planTier' => $planTier,
            'planInfo' => $planInfo,
            'organizer' => $organizer,
            'previousInvoices' => $previousInvoicesWithPlan,
            'planChanged' => $planChanged,
            'invoiceItems' => $this->invoiceDetailService->getInvoiceItems($invoice),
            'paymentMethod' => $paymentMethod,
            'billingDate' => $invoice->getBillingMonth(),
        ]);
    }

    private function getPreviousInvoices(SubscriptionInvoice $currentInvoice, $organizer): array
    {
        return $this->subscriptionInvoiceRepository->createQueryBuilder('si')
            ->where('si.customer = :customer')
            ->andWhere('si.id != :currentId')
            ->andWhere('si.createdAt < :currentDate')
            ->setParameter('customer', $organizer)
            ->setParameter('currentId', $currentInvoice->getId())
            ->setParameter('currentDate', $currentInvoice->getCreatedAt())
            ->orderBy('si.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    private function enrichInvoicesWithPlanInfo(array $invoices): array
    {
        $result = [];
        foreach ($invoices as $invoice) {
            $planInfo = $this->subscriptionInvoiceRepository->getPlanInfoForInvoice($invoice);
            $result[] = [
                'invoice' => $invoice,
                'planInfo' => $planInfo,
                'planTier' => $planInfo['niveau'] ?? null,
            ];
        }
        return $result;
    }

    private function checkPlanChange(?array $currentPlanInfo, array $previousInvoices): bool
    {
        if (empty($previousInvoices) || empty($currentPlanInfo)) {
            return false;
        }

        $previousPlanInfo = $previousInvoices[0]['planInfo'] ?? null;
        if (!$previousPlanInfo) {
            return false;
        }

        return ($previousPlanInfo['niveau'] ?? null) !== ($currentPlanInfo['niveau'] ?? null)
            || ($previousPlanInfo['periode_facturation'] ?? null) !== ($currentPlanInfo['periode_facturation'] ?? null);
    }

    #[Route('/ticket-invoice/{id}/pdf', name: 'admin_billing_ticket_invoice_pdf', requirements: ['id' => '\d+'])]
    public function downloadTicketInvoicePdf(string $id): Response
    {
        $invoice = $this->ticketInvoiceRepository->find($id);

        if (!$invoice instanceof TicketInvoice) {
            $this->addFlash('error', 'Facture introuvable');
            return $this->redirectToRoute('admin_billing_invoices');
        }

        return $this->pdfService->generateTicketInvoicePdf($invoice);
    }

    #[Route('/subscription-invoice/{id}/pdf', name: 'admin_billing_subscription_invoice_pdf', requirements: ['id' => '\d+'])]
    public function downloadSubscriptionInvoicePdf(string $id): Response
    {
        $invoice = $this->subscriptionInvoiceRepository->find($id);

        if (!$invoice instanceof SubscriptionInvoice) {
            $this->addFlash('error', 'Facture introuvable');
            return $this->redirectToRoute('admin_billing_invoices');
        }

        return $this->pdfService->generateSubscriptionInvoicePdf($invoice, $this->getUser());
    }

    #[Route('/ticket-invoice/{id}/resend', name: 'admin_billing_ticket_invoice_resend', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function resendTicketInvoice(string $id): Response
    {
        $invoice = $this->ticketInvoiceRepository->find($id);

        if (!$invoice instanceof TicketInvoice) {
            $this->addFlash('error', 'Facture introuvable');
            return $this->redirectToRoute('admin_billing_invoices');
        }

        $this->sendEmailAndNotify($invoice, 'ticket');

        return $this->redirectToRoute('admin_billing_ticket_invoice_show', ['id' => $id]);
    }

    #[Route('/subscription-invoice/{id}/resend', name: 'admin_billing_subscription_invoice_resend', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function resendSubscriptionInvoice(string $id): Response
    {
        $invoice = $this->subscriptionInvoiceRepository->find($id);

        if (!$invoice instanceof SubscriptionInvoice) {
            $this->addFlash('error', 'Facture introuvable');
            return $this->redirectToRoute('admin_billing_invoices');
        }

        $this->sendEmailAndNotify($invoice, 'subscription');

        return $this->redirectToRoute('admin_billing_subscription_invoice_show', ['id' => $id]);
    }

    #[Route('/subscription-invoice/{id}/notify', name: 'admin_billing_subscription_invoice_notify', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function notifyOrganizerAboutOverdueInvoice(string $id): Response
    {
        $invoice = $this->subscriptionInvoiceRepository->find($id);

        if (!$invoice instanceof SubscriptionInvoice) {
            $this->addFlash('error', 'Facture introuvable');
            return $this->redirectToRoute('admin_billing_invoices');
        }

        $success = $this->emailService->sendSubscriptionInvoice($invoice, true);
        $this->handleNotificationResult($success, $invoice);

        return $this->redirectToRoute('admin_billing_subscription_invoice_show', ['id' => $id]);
    }

    private function sendEmailAndNotify($invoice, string $type): void
    {
        $method = $type === 'ticket' ? 'sendTicketInvoice' : 'sendSubscriptionInvoice';
        $success = $this->emailService->$method($invoice);

        if ($success) {
            $this->addFlash('success', sprintf('Facture %s envoyée avec succès', $invoice->getInvoiceNumber()));
        } else {
            $this->addFlash('error', 'Erreur lors de l\'envoi de la facture');
        }
    }

    private function handleNotificationResult(bool $success, SubscriptionInvoice $invoice): void
    {
        if ($success) {
            $daysOverdue = $invoice->getDaysOverdue();
            $message = $daysOverdue !== null
                ? sprintf('Signalement de retard envoyé à l\'organisateur pour la facture %s (%d jour(s) de retard)', 
                    $invoice->getInvoiceNumber(), $daysOverdue)
                : sprintf('Signalement de retard envoyé à l\'organisateur pour la facture %s', 
                    $invoice->getInvoiceNumber());
            $this->addFlash('success', $message);
        } else {
            $this->addFlash('error', 'Erreur lors de l\'envoi du signalement de retard');
        }
    }
}