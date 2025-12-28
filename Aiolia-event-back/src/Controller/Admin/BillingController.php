<?php

namespace App\Controller\Admin;

use App\Entity\TicketInvoice;
use App\Entity\SubscriptionInvoice;
use App\Repository\Organisateur\TicketInvoiceRepository;
use App\Repository\Admin\SubscriptionInvoiceRepository;
use App\Service\Admin\BillingService;
use App\Service\Admin\BillingInvoiceDetailService;
use App\Service\Organisateur\InvoicePdfService;
use App\Service\Organisateur\InvoiceEmailService;
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
        private BillingService $billingService
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

        $monthFilter = $month > 0 ? $month : null;
        $yearFilter = $year > 0 ? $year : null;

        $result = $this->billingService->getInvoicesWithFilters(
            $status,
            $search,
            $month,
            $year,
            $page,
            $perPage
        );

        $stats = $this->billingService->calculateStats($status, $search, $monthFilter, $yearFilter);

        return $this->render('@Admin/billing/invoices.html.twig', [
            'allInvoices' => $result['invoices'],
            'planInfos' => $result['invoiceInfo']['planInfos'],
            'invoiceTypes' => $result['invoiceInfo']['types'],
            'billingDates' => $result['invoiceInfo']['dates'],
            'stats' => $stats,
            'currentStatus' => $status,
            'currentSearch' => $search,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($result['totalInvoices'] / $perPage)),
            'totalInvoices' => $result['totalInvoices'],
        ]);
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
        $previousInvoices = $this->billingService->getPreviousInvoices($invoice, $organizer);
        $previousInvoicesWithPlan = $this->billingService->enrichInvoicesWithPlanInfo($previousInvoices);

        $planChanged = $this->billingService->checkPlanChange($planInfo, $previousInvoicesWithPlan);

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