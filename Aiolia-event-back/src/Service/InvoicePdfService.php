<?php

namespace App\Service;

use App\Entity\TicketInvoice;
use App\Entity\SubscriptionInvoice;
use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoicePdfService
{
    public function __construct(
        private Environment $twig,
        private BillingInvoiceDetailService $invoiceDetailService
    ) {
    }

    /**
     * Génère le HTML de la facture pour les billets
     */
    public function generateTicketInvoiceHtml(TicketInvoice $invoice): string
    {
        return $this->twig->render('admin/billing/invoice_ticket.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Génère le HTML de la facture pour les abonnements
     */
    public function generateSubscriptionInvoiceHtml(SubscriptionInvoice $invoice): string
    {
        $planInfo = $this->invoiceDetailService->getPlanInfo($invoice);
        $invoiceItems = $this->invoiceDetailService->getInvoiceItems($invoice);
        $planTier = $planInfo['niveau'] ?? null;

        return $this->twig->render('admin/billing/invoice_subscription.html.twig', [
            'invoice' => $invoice,
            'planInfo' => $planInfo,
            'planTier' => $planTier,
            'invoiceItems' => $invoiceItems,
        ]);
    }

    /**
     * Génère une réponse PDF pour une facture de billet
     */
    public function generateTicketInvoicePdf(TicketInvoice $invoice): Response
    {
        $html = $this->generateTicketInvoiceHtml($invoice);
        return $this->generatePdfResponse($html, 'facture-billet-' . $invoice->getInvoiceNumber() . '.pdf');
    }

    /**
     * Génère une réponse PDF pour une facture d'abonnement
     */
    public function generateSubscriptionInvoicePdf(SubscriptionInvoice $invoice): Response
    {
        $html = $this->generateSubscriptionInvoiceHtml($invoice);
        return $this->generatePdfResponse($html, 'facture-abonnement-' . $invoice->getInvoiceNumber() . '.pdf');
    }

    /**
     * Génère un PDF à partir du HTML et retourne une réponse HTTP
     */
    private function generatePdfResponse(string $html, string $filename): Response
    {
        // Configuration de Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Générer le PDF
        $output = $dompdf->output();
        
        // Créer la réponse avec le PDF
        $response = new Response($output);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Content-Length', strlen($output));
        
        return $response;
    }
}

