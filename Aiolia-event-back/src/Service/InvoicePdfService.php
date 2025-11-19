<?php

namespace App\Service;

use App\Entity\TicketInvoice;
use App\Entity\SubscriptionInvoice;
use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoicePdfService
{
    public function __construct(
        private Environment $twig,
        private BillingInvoiceDetailService $invoiceDetailService,
        private KernelInterface $kernel
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
    public function generateSubscriptionInvoiceHtml(SubscriptionInvoice $invoice, ?\App\Entity\User $admin = null): string
    {
        $planInfo = $this->invoiceDetailService->getPlanInfo($invoice);
        $invoiceItems = $this->invoiceDetailService->getInvoiceItems($invoice);
        $planTier = $planInfo['niveau'] ?? null;
        $paymentMethod = $this->invoiceDetailService->getPaymentMethod($invoice);

        // Chemin absolu du logo pour Dompdf
        $logoPath = $this->kernel->getProjectDir() . '/public/images/aiolia-logo.svg';
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoContent = file_get_contents($logoPath);
            $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode($logoContent);
        }

        return $this->twig->render('admin/billing/invoice_subscription.html.twig', [
            'invoice' => $invoice,
            'planInfo' => $planInfo,
            'planTier' => $planTier,
            'invoiceItems' => $invoiceItems,
            'admin' => $admin,
            'logoBase64' => $logoBase64,
            'paymentMethod' => $paymentMethod,
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
    public function generateSubscriptionInvoicePdf(SubscriptionInvoice $invoice, ?\App\Entity\User $admin = null): Response
    {
        $html = $this->generateSubscriptionInvoiceHtml($invoice, $admin);
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

