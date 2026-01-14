<?php

namespace App\Service\Organisateur;

use App\Entity\TicketInvoice;
use App\Entity\SubscriptionInvoice;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class InvoiceEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private InvoicePdfService $pdfService,
        private UrlGeneratorInterface $router,
        #[Autowire(env: 'MAIL_FROM_ADDRESS')]
        private string $fromAddress,
        #[Autowire(env: 'MAIL_FROM_NAME')]
        private ?string $fromName = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    
    public function sendTicketInvoice(TicketInvoice $invoice): bool
    {
        try {
            $customer = $invoice->getCustomer();
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromAddress, $this->fromName ?? 'Aiolia Event'))
                ->to($customer->getEmail())
                ->subject(sprintf('Facture %s - Aiolia Event', $invoice->getInvoiceNumber()))
                ->htmlTemplate('emails/invoice_ticket.html.twig')
                ->context([
                    'invoice' => $invoice,
                    'customer' => $customer,
                ]);

            
            
            
            

            $this->mailer->send($email);

            if ($this->logger) {
                $this->logger->info('Facture envoyée par email', [
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'customer_email' => $customer->getEmail(),
                ]);
            }

            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Erreur lors de l\'envoi de la facture par email', [
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'error' => $e->getMessage(),
                ]);
            }
            return false;
        }
    }

    
    public function sendSubscriptionInvoice(SubscriptionInvoice $invoice, bool $isOverdueNotification = false): bool
    {
        try {
            $customer = $invoice->getCustomer();
            
            $subject = $isOverdueNotification 
                ? sprintf('URGENT - Facture d\'abonnement %s en retard de paiement - Aiolia Event', $invoice->getInvoiceNumber())
                : sprintf('Facture d\'abonnement %s - Aiolia Event', $invoice->getInvoiceNumber());
            
            // Générer l'URL de téléchargement du PDF
            $pdfDownloadUrl = $this->router->generate(
                'organisateur_subscription_invoice_pdf',
                ['id' => $invoice->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $email = (new TemplatedEmail())
                ->from(new Address($this->fromAddress, $this->fromName ?? 'Aiolia Event'))
                ->to($customer->getEmail())
                ->subject($subject)
                ->htmlTemplate('emails/invoice_subscription.html.twig')
                ->context([
                    'invoice' => $invoice,
                    'customer' => $customer,
                    'isOverdueNotification' => $isOverdueNotification,
                    'pdf_download_url' => $pdfDownloadUrl,
                ]);

            
            
            
            

            $this->mailer->send($email);

            if ($this->logger) {
                $logMessage = $isOverdueNotification 
                    ? 'Notification de retard de paiement envoyée par email'
                    : 'Facture d\'abonnement envoyée par email';
                $this->logger->info($logMessage, [
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'customer_email' => $customer->getEmail(),
                    'invoice_status' => $invoice->getStatus(),
                ]);
            }

            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Erreur lors de l\'envoi de la facture d\'abonnement par email', [
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'error' => $e->getMessage(),
                ]);
            }
            return false;
        }
    }

    
    public function sendInvoiceAfterPayment(TicketInvoice|SubscriptionInvoice $invoice): bool
    {
        if ($invoice instanceof TicketInvoice) {
            return $this->sendTicketInvoice($invoice);
        } else {
            return $this->sendSubscriptionInvoice($invoice);
        }
    }
}

