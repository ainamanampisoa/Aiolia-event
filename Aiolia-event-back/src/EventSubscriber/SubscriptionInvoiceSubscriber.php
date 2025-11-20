<?php

namespace App\EventSubscriber;

use App\Entity\SubscriptionInvoice;
use App\Service\Organisateur\InvoiceEmailService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

/**
 * Écouteur d'événements Doctrine pour les factures d'abonnement.
 * Envoie automatiquement la facture par email lorsqu'une facture passe au statut "paid".
 */
class SubscriptionInvoiceSubscriber
{
    /**
     * Stocke les changements de statut par ID d'entité pour gérer plusieurs mises à jour simultanées
     * @var array<string, array{old: string, new: string}>
     */
    private array $statusChanges = [];

    public function __construct(
        private InvoiceEmailService $invoiceEmailService,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Capture le changement de statut avant la mise à jour
     */
    #[AsDoctrineListener(event: Events::preUpdate, priority: 500)]
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof SubscriptionInvoice) {
            return;
        }

        // Vérifier si le statut change
        if ($args->hasChangedField('status')) {
            $oldStatus = $args->getOldValue('status');
            $newStatus = $args->getNewValue('status');
            
            // Stocker le changement pour l'utiliser dans postUpdate, indexé par ID d'entité
            // Utiliser l'ID si disponible, sinon utiliser le hash de l'objet
            $entityId = $entity->getId() ? (string) $entity->getId() : spl_object_hash($entity);
            $this->statusChanges[$entityId] = [
                'old' => $oldStatus,
                'new' => $newStatus,
            ];
        }
    }

    /**
     * Déclenche l'envoi automatique de la facture après la mise à jour si le statut est passé à "paid"
     */
    #[AsDoctrineListener(event: Events::postUpdate, priority: 500)]
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof SubscriptionInvoice) {
            return;
        }

        // Utiliser l'ID si disponible, sinon utiliser le hash de l'objet
        $entityId = $entity->getId() ? (string) $entity->getId() : spl_object_hash($entity);
        $statusChange = $this->statusChanges[$entityId] ?? null;

        // Vérifier si le statut vient de passer à "paid"
        if ($statusChange !== null 
            && $statusChange['new'] === SubscriptionInvoice::STATUS_PAID 
            && $statusChange['old'] !== SubscriptionInvoice::STATUS_PAID
        ) {
            // Envoyer automatiquement la facture par email
            try {
                if ($this->invoiceEmailService->sendSubscriptionInvoice($entity)) {
                    if ($this->logger) {
                        $this->logger->info('Facture d\'abonnement envoyée automatiquement après paiement', [
                            'invoice_number' => $entity->getInvoiceNumber(),
                            'invoice_id' => $entity->getId(),
                            'customer_email' => $entity->getCustomer()->getEmail(),
                        ]);
                    }
                } else {
                    if ($this->logger) {
                        $this->logger->warning('Échec de l\'envoi automatique de la facture d\'abonnement après paiement', [
                            'invoice_number' => $entity->getInvoiceNumber(),
                            'invoice_id' => $entity->getId(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->error('Erreur lors de l\'envoi automatique de la facture d\'abonnement', [
                        'invoice_number' => $entity->getInvoiceNumber(),
                        'invoice_id' => $entity->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Nettoyer le changement de statut après traitement
        unset($this->statusChanges[$entityId]);
    }
}

