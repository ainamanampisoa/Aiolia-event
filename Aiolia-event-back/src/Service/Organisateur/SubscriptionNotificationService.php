<?php

namespace App\Service\Organisateur;

use App\Entity\SubscriptionInvoice;
use App\Entity\OrganizerSubscription;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service centralisé pour gérer toutes les notifications liées aux abonnements
 */
class SubscriptionNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private InvoicePdfService $pdfService,
        #[Autowire(env: 'MAIL_FROM_ADDRESS')]
        private string $fromAddress,
        #[Autowire(env: 'MAIL_FROM_NAME')]
        private ?string $fromName = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Envoie un rappel pré-facturation (7 jours avant la fin du mois)
     */
    public function sendPreBillingReminder(
        User $customer,
        OrganizerSubscription $subscription,
        float $amount,
        string $currency = 'MGA',
        \DateTimeInterface $billingDate
    ): bool {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromAddress, $this->fromName ?? 'Aiolia Event'))
                ->to($customer->getEmail())
                ->subject('Votre prochain paiement approche - Aiolia Event')
                ->htmlTemplate('emails/subscription/pre_billing_reminder.html.twig')
                ->context([
                    'customer' => $customer,
                    'subscription' => $subscription,
                    'amount' => $amount,
                    'currency' => $currency,
                    'billingDate' => $billingDate,
                ]);

            $this->mailer->send($email);

            if ($this->logger) {
                $this->logger->info('Rappel pré-facturation envoyé', [
                    'customer_email' => $customer->getEmail(),
                    'subscription_id' => $subscription->getId(),
                    'billing_date' => $billingDate->format('Y-m-d'),
                ]);
            }

            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Erreur lors de l\'envoi du rappel pré-facturation', [
                    'customer_email' => $customer->getEmail(),
                    'error' => $e->getMessage(),
                ]);
            }
            return false;
        }
    }

    /**
     * Envoie une notification de nouvelle facture générée
     */
    public function sendInvoiceGenerated(SubscriptionInvoice $invoice): bool
    {
        try {
            $customer = $invoice->getCustomer();
            
            // Générer le PDF de la facture
            $pdfPath = null;
            try {
                $pdfResponse = $this->pdfService->generateSubscriptionInvoicePdf($invoice);
                $pdfContent = $pdfResponse->getContent();
                
                // Sauvegarder dans un fichier temporaire
                $tempDir = sys_get_temp_dir();
                $pdfPath = $tempDir . '/facture_' . $invoice->getInvoiceNumber() . '_' . uniqid() . '.pdf';
                file_put_contents($pdfPath, $pdfContent);
            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->warning('Impossible de générer le PDF de la facture', [
                        'invoice_number' => $invoice->getInvoiceNumber(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $email = (new TemplatedEmail())
                ->from(new Address($this->fromAddress, $this->fromName ?? 'Aiolia Event'))
                ->to($customer->getEmail())
                ->subject(sprintf('Nouvelle facture disponible - %s - Aiolia Event', $invoice->getInvoiceNumber()))
                ->htmlTemplate('emails/subscription/invoice_generated.html.twig')
                ->context([
                    'invoice' => $invoice,
                    'customer' => $customer,
                ]);

            // Attacher le PDF si disponible
            if ($pdfPath && file_exists($pdfPath)) {
                $email->attachFromPath($pdfPath, sprintf('facture_%s.pdf', $invoice->getInvoiceNumber()));
            }

            $this->mailer->send($email);

            // Nettoyer le fichier PDF temporaire
            if ($pdfPath && file_exists($pdfPath)) {
                @unlink($pdfPath);
            }

            if ($this->logger) {
                $this->logger->info('Notification de facture générée envoyée', [
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'customer_email' => $customer->getEmail(),
                ]);
            }

            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Erreur lors de l\'envoi de la notification de facture générée', [
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'error' => $e->getMessage(),
                ]);
            }
            return false;
        }
    }

    /**
     * Envoie un avertissement pour facture impayée (J+4)
     */
    public function sendOverdueWarning(SubscriptionInvoice $invoice): bool
    {
        try {
            $customer = $invoice->getCustomer();
            $daysOverdue = $invoice->getDaysOverdue() ?? 4;

            $email = (new TemplatedEmail())
                ->from(new Address($this->fromAddress, $this->fromName ?? 'Aiolia Event'))
                ->to($customer->getEmail())
                ->subject(sprintf('⚠️ Facture impayée - %s - Aiolia Event', $invoice->getInvoiceNumber()))
                ->htmlTemplate('emails/subscription/overdue_warning.html.twig')
                ->context([
                    'invoice' => $invoice,
                    'customer' => $customer,
                    'daysOverdue' => $daysOverdue,
                ]);

            $this->mailer->send($email);

            if ($this->logger) {
                $this->logger->info('Avertissement facture impayée envoyé', [
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'customer_email' => $customer->getEmail(),
                    'days_overdue' => $daysOverdue,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Erreur lors de l\'envoi de l\'avertissement facture impayée', [
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'error' => $e->getMessage(),
                ]);
            }
            return false;
        }
    }

    /**
     * Envoie le dernier rappel avant suspension (J+7)
     */
    public function sendFinalReminder(SubscriptionInvoice $invoice): bool
    {
        try {
            $customer = $invoice->getCustomer();
            $daysOverdue = $invoice->getDaysOverdue() ?? 7;

            $email = (new TemplatedEmail())
                ->from(new Address($this->fromAddress, $this->fromName ?? 'Aiolia Event'))
                ->to($customer->getEmail())
                ->subject(sprintf('🚨 Action requise : Paiement en retard - %s - Aiolia Event', $invoice->getInvoiceNumber()))
                ->htmlTemplate('emails/subscription/final_reminder.html.twig')
                ->context([
                    'invoice' => $invoice,
                    'customer' => $customer,
                    'daysOverdue' => $daysOverdue,
                ]);

            $this->mailer->send($email);

            if ($this->logger) {
                $this->logger->info('Dernier rappel avant suspension envoyé', [
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'customer_email' => $customer->getEmail(),
                    'days_overdue' => $daysOverdue,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Erreur lors de l\'envoi du dernier rappel', [
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'error' => $e->getMessage(),
                ]);
            }
            return false;
        }
    }

    /**
     * Envoie une notification de suspension de compte (J+10)
     */
    public function sendSuspensionNotification(
        User $customer,
        OrganizerSubscription $subscription,
        SubscriptionInvoice $invoice
    ): bool {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromAddress, $this->fromName ?? 'Aiolia Event'))
                ->to($customer->getEmail())
                ->subject('Compte suspendu - Action requise - Aiolia Event')
                ->htmlTemplate('emails/subscription/suspension_notification.html.twig')
                ->context([
                    'customer' => $customer,
                    'subscription' => $subscription,
                    'invoice' => $invoice,
                ]);

            $this->mailer->send($email);

            if ($this->logger) {
                $this->logger->info('Notification de suspension envoyée', [
                    'customer_email' => $customer->getEmail(),
                    'subscription_id' => $subscription->getId(),
                    'invoice_number' => $invoice->getInvoiceNumber(),
                ]);
            }

            return true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Erreur lors de l\'envoi de la notification de suspension', [
                    'customer_email' => $customer->getEmail(),
                    'error' => $e->getMessage(),
                ]);
            }
            return false;
        }
    }
}

