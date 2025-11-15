<?php

namespace App\Controller\Admin;

use App\Entity\TicketInvoice;
use App\Entity\SubscriptionInvoice;
use App\Repository\TicketInvoiceRepository;
use App\Repository\SubscriptionInvoiceRepository;
use App\Service\InvoicePdfService;
use App\Service\InvoiceEmailService;
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
        private InvoiceEmailService $emailService
    ) {
    }

    /**
     * Liste toutes les factures (billets et abonnements)
     */
    #[Route('/invoices', name: 'admin_billing_invoices')]
    public function invoices(Request $request): Response
    {
        $status = $request->query->get('status');
        $search = $request->query->get('search');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 7;

        // Convertir les dates string en DateTime
        $dateFromObj = null;
        $dateToObj = null;
        
        if ($dateFrom) {
            try {
                $dateFromObj = new \DateTime($dateFrom);
            } catch (\Exception $e) {
                $dateFromObj = null;
            }
        }
        
        if ($dateTo) {
            try {
                $dateToObj = new \DateTime($dateTo);
            } catch (\Exception $e) {
                $dateToObj = null;
            }
        }

        // Récupérer toutes les factures (sans pagination) pour pouvoir les fusionner et trier
        // Puis appliquer la pagination sur le résultat final
        $fetchLimit = 100; // Récupérer un nombre suffisant pour la pagination
        
        $ticketInvoices = $this->ticketInvoiceRepository->findAllWithFilters(
            $status,
            $search,
            $dateFromObj,
            $dateToObj,
            $fetchLimit,
            0
        );
        $ticketTotal = $this->ticketInvoiceRepository->countWithFilters($status, $search, $dateFromObj, $dateToObj);

        $subscriptionInvoices = $this->subscriptionInvoiceRepository->findAllWithFilters(
            $status,
            $search,
            $dateFromObj,
            $dateToObj,
            $fetchLimit,
            0
        );
        $subscriptionTotal = $this->subscriptionInvoiceRepository->countWithFilters($status, $search, $dateFromObj, $dateToObj);

        // Fusionner et trier par date
        $allInvoices = array_merge($ticketInvoices, $subscriptionInvoices);
        usort($allInvoices, function ($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        
        // Récupérer les types d'organisateurs pour les factures d'abonnement
        $organizerTypes = [];
        $subscriptionInvoicesOnly = array_filter($allInvoices, fn($inv) => $inv instanceof SubscriptionInvoice);
        if (!empty($subscriptionInvoicesOnly)) {
            $organizerTypes = $this->subscriptionInvoiceRepository->getOrganizerTypesForInvoices($subscriptionInvoicesOnly);
        }
        
        // Appliquer la pagination : limiter à 7 résultats par page
        $totalInvoices = count($allInvoices);
        $allInvoices = array_slice($allInvoices, ($page - 1) * $perPage, $perPage);

        // Statistiques basées sur les filtres appliqués
        $stats = [
            'total' => $ticketTotal + $subscriptionTotal,
            'paid' => $this->countByStatus('paid', $search, $dateFromObj, $dateToObj),
            'pending' => $this->countByStatus('issued', $search, $dateFromObj, $dateToObj) 
                + $this->countByStatus('draft', $search, $dateFromObj, $dateToObj) 
                + $this->countByStatus('overdue', $search, $dateFromObj, $dateToObj),
            'cancelled' => $this->countByStatus('void', $search, $dateFromObj, $dateToObj) + $this->countByStatus('refunded', $search, $dateFromObj, $dateToObj),
        ];

        return $this->render('admin/billing/invoices.html.twig', [
            'ticketInvoices' => $ticketInvoices,
            'subscriptionInvoices' => $subscriptionInvoices,
            'allInvoices' => $allInvoices,
            'organizerTypes' => $organizerTypes,
            'stats' => $stats,
            'currentStatus' => $status,
            'currentSearch' => $search,
            'currentDateFrom' => $dateFrom,
            'currentDateTo' => $dateTo,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($totalInvoices / $perPage)),
        ]);
    }

    /**
     * Détails d'une facture de billet
     */
    #[Route('/ticket-invoice/{id}', name: 'admin_billing_ticket_invoice_show', requirements: ['id' => '\d+'])]
    public function showTicketInvoice(string $id): Response
    {
        $invoice = $this->ticketInvoiceRepository->find($id);

        if (!$invoice instanceof TicketInvoice) {
            $this->addFlash('error', 'Facture introuvable');
            return $this->redirectToRoute('admin_billing_invoices');
        }

        return $this->render('admin/billing/invoice_show.html.twig', [
            'invoice' => $invoice,
            'type' => 'ticket',
        ]);
    }

    /**
     * Détails d'une facture d'abonnement
     */
    #[Route('/subscription-invoice/{id}', name: 'admin_billing_subscription_invoice_show', requirements: ['id' => '\d+'])]
    public function showSubscriptionInvoice(string $id): Response
    {
        $invoice = $this->subscriptionInvoiceRepository->find($id);

        if (!$invoice instanceof SubscriptionInvoice) {
            $this->addFlash('error', 'Facture introuvable');
            return $this->redirectToRoute('admin_billing_invoices');
        }

        // Récupérer le type d'organisateur
        $organizerType = $this->subscriptionInvoiceRepository->getOrganizerTypeForInvoice($invoice);

        return $this->render('admin/billing/invoice_show.html.twig', [
            'invoice' => $invoice,
            'type' => 'subscription',
            'organizerType' => $organizerType,
        ]);
    }

    /**
     * Télécharger le PDF d'une facture de billet
     */
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

    /**
     * Télécharger le PDF d'une facture d'abonnement
     */
    #[Route('/subscription-invoice/{id}/pdf', name: 'admin_billing_subscription_invoice_pdf', requirements: ['id' => '\d+'])]
    public function downloadSubscriptionInvoicePdf(string $id): Response
    {
        $invoice = $this->subscriptionInvoiceRepository->find($id);

        if (!$invoice instanceof SubscriptionInvoice) {
            $this->addFlash('error', 'Facture introuvable');
            return $this->redirectToRoute('admin_billing_invoices');
        }

        return $this->pdfService->generateSubscriptionInvoicePdf($invoice);
    }

    /**
     * Renvoyer une facture par email
     */
    #[Route('/ticket-invoice/{id}/resend', name: 'admin_billing_ticket_invoice_resend', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function resendTicketInvoice(string $id): Response
    {
        $invoice = $this->ticketInvoiceRepository->find($id);

        if (!$invoice instanceof TicketInvoice) {
            $this->addFlash('error', 'Facture introuvable');
            return $this->redirectToRoute('admin_billing_invoices');
        }

        if ($this->emailService->sendTicketInvoice($invoice)) {
            $this->addFlash('success', sprintf('Facture %s envoyée avec succès', $invoice->getInvoiceNumber()));
        } else {
            $this->addFlash('error', 'Erreur lors de l\'envoi de la facture');
        }

        return $this->redirectToRoute('admin_billing_ticket_invoice_show', ['id' => $id]);
    }

    /**
     * Renvoyer une facture d'abonnement par email
     */
    #[Route('/subscription-invoice/{id}/resend', name: 'admin_billing_subscription_invoice_resend', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function resendSubscriptionInvoice(string $id): Response
    {
        $invoice = $this->subscriptionInvoiceRepository->find($id);

        if (!$invoice instanceof SubscriptionInvoice) {
            $this->addFlash('error', 'Facture introuvable');
            return $this->redirectToRoute('admin_billing_invoices');
        }

        if ($this->emailService->sendSubscriptionInvoice($invoice)) {
            $this->addFlash('success', sprintf('Facture %s envoyée avec succès', $invoice->getInvoiceNumber()));
        } else {
            $this->addFlash('error', 'Erreur lors de l\'envoi de la facture');
        }

        return $this->redirectToRoute('admin_billing_subscription_invoice_show', ['id' => $id]);
    }

    /**
     * Envoyer un email de notification à l'organisateur pour une facture en retard
     */
    #[Route('/subscription-invoice/{id}/notify', name: 'admin_billing_subscription_invoice_notify', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function notifyOrganizerAboutOverdueInvoice(string $id): Response
    {
        $invoice = $this->subscriptionInvoiceRepository->find($id);

        if (!$invoice instanceof SubscriptionInvoice) {
            $this->addFlash('error', 'Facture introuvable');
            return $this->redirectToRoute('admin_billing_invoices');
        }

        // Envoyer un email de notification spécial pour les factures en retard
        if ($this->emailService->sendSubscriptionInvoice($invoice, true)) {
            $daysOverdue = $invoice->getDaysOverdue();
            $message = $daysOverdue !== null 
                ? sprintf('Signalement de retard envoyé à l\'organisateur pour la facture %s (%d jour(s) de retard)', $invoice->getInvoiceNumber(), $daysOverdue)
                : sprintf('Signalement de retard envoyé à l\'organisateur pour la facture %s', $invoice->getInvoiceNumber());
            $this->addFlash('success', $message);
        } else {
            $this->addFlash('error', 'Erreur lors de l\'envoi du signalement de retard');
        }

        return $this->redirectToRoute('admin_billing_subscription_invoice_show', ['id' => $id]);
    }

    /**
     * Compte les factures par statut
     */
    private function countByStatus(string $status, ?string $search = null, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null): int
    {
        $count = 0;
        $count += $this->ticketInvoiceRepository->countWithFilters($status, $search, $dateFrom, $dateTo);
        $count += $this->subscriptionInvoiceRepository->countWithFilters($status, $search, $dateFrom, $dateTo);
        return $count;
    }
}

