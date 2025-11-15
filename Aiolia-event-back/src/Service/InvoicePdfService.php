<?php

namespace App\Service;

use App\Entity\TicketInvoice;
use App\Entity\SubscriptionInvoice;
use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfService
{
    public function __construct(
        private Environment $twig
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
        return $this->twig->render('admin/billing/invoice_subscription.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Génère une réponse PDF pour une facture de billet
     * 
     * Note: Pour générer un vrai PDF, vous devrez installer une bibliothèque comme:
     * - dompdf/dompdf
     * - knplabs/knp-snappy
     * 
     * Pour l'instant, cette méthode retourne le HTML qui peut être converti en PDF
     */
    public function generateTicketInvoicePdf(TicketInvoice $invoice): Response
    {
        $html = $this->generateTicketInvoiceHtml($invoice);
        
        // Pour l'instant, on retourne le HTML
        // TODO: Ajouter la conversion en PDF avec une bibliothèque comme Dompdf
        $response = new Response($html);
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');
        
        return $response;
    }

    /**
     * Génère une réponse PDF pour une facture d'abonnement
     */
    public function generateSubscriptionInvoicePdf(SubscriptionInvoice $invoice): Response
    {
        $html = $this->generateSubscriptionInvoiceHtml($invoice);
        
        // Pour l'instant, on retourne le HTML
        // TODO: Ajouter la conversion en PDF avec une bibliothèque comme Dompdf
        $response = new Response($html);
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');
        
        return $response;
    }
}

