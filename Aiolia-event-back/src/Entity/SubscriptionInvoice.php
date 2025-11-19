<?php

namespace App\Entity;

use App\Repository\SubscriptionInvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionInvoiceRepository::class)]
#[ORM\Table(name: 'factures_abonnements', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class SubscriptionInvoice
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_VOID = 'void';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_OVERDUE = 'overdue';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(name: 'numero_facture', type: Types::STRING, length: 255, unique: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(name: 'id_abonnement', type: Types::BIGINT, nullable: false)]
    private string $subscriptionId;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_client', referencedColumnName: 'id', nullable: false)]
    private User $customer;

    #[ORM\Column(name: 'devise', type: Types::STRING, length: 3, options: ['default' => 'MGA'])]
    private string $currency = 'MGA';

    #[ORM\Column(name: 'montant_sous_total', type: Types::DECIMAL, precision: 12, scale: 2, options: ['default' => 0])]
    private string $subtotalAmount = '0';

    #[ORM\Column(name: 'montant_tva', type: Types::DECIMAL, precision: 12, scale: 2, options: ['default' => 0])]
    private string $taxAmount = '0';

    #[ORM\Column(name: 'montant_total', type: Types::DECIMAL, precision: 12, scale: 2, options: ['default' => 0])]
    private string $totalAmount = '0';

    #[ORM\Column(name: 'montant_ht', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0])]
    private string $amountHt = '0';

    #[ORM\Column(name: 'montant_tva_detail', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0])]
    private string $amountTva = '0';

    #[ORM\Column(name: 'montant_ttc', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0])]
    private string $amountTtc = '0';

    #[ORM\Column(name: 'mois_facturation', type: Types::DATE_MUTABLE, nullable: false)]
    private \DateTimeInterface $billingMonth;

    #[ORM\Column(name: 'est_mois_pause', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isPauseMonth = false;

    #[ORM\Column(name: 'est_prepayee', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isPrepaid = false;

    #[ORM\Column(name: 'statut', type: Types::STRING, length: 20, options: ['default' => self::STATUS_DRAFT])]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(name: 'emise_le', type: Types::DATETIMETZ_MUTABLE)]
    private \DateTimeInterface $issuedAt;

    #[ORM\Column(name: 'echeance_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dueAt = null;

    #[ORM\Column(name: 'payee_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paidAt = null;

    #[ORM\Column(name: 'metadonnees', type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->issuedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->billingMonth = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;
        return $this;
    }

    public function getSubscriptionId(): string
    {
        return $this->subscriptionId;
    }

    public function setSubscriptionId(string $subscriptionId): self
    {
        $this->subscriptionId = $subscriptionId;
        return $this;
    }

    public function getCustomer(): User
    {
        return $this->customer;
    }

    public function setCustomer(User $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getSubtotalAmount(): string
    {
        return $this->subtotalAmount;
    }

    public function setSubtotalAmount(string $subtotalAmount): self
    {
        $this->subtotalAmount = $subtotalAmount;
        return $this;
    }

    public function getTaxAmount(): string
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(string $taxAmount): self
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getAmountHt(): string
    {
        return $this->amountHt;
    }

    public function setAmountHt(string $amountHt): self
    {
        $this->amountHt = $amountHt;
        return $this;
    }

    public function getAmountTva(): string
    {
        return $this->amountTva;
    }

    public function setAmountTva(string $amountTva): self
    {
        $this->amountTva = $amountTva;
        return $this;
    }

    public function getAmountTtc(): string
    {
        return $this->amountTtc;
    }

    public function setAmountTtc(string $amountTtc): self
    {
        $this->amountTtc = $amountTtc;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getIssuedAt(): \DateTimeInterface
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(\DateTimeInterface $issuedAt): self
    {
        $this->issuedAt = $issuedAt;
        return $this;
    }

    public function getDueAt(): ?\DateTimeInterface
    {
        return $this->dueAt;
    }

    public function setDueAt(?\DateTimeInterface $dueAt): self
    {
        $this->dueAt = $dueAt;
        return $this;
    }

    public function getPaidAt(): ?\DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeInterface $paidAt): self
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_ISSUED], true);
    }

    public function isCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_VOID, self::STATUS_REFUNDED], true);
    }

    public function getInvoiceType(): string
    {
        return 'subscription';
    }

    /**
     * Émet la facture (passe de draft à issued) si elle est prête à être envoyée
     * Une facture est prête si elle a un numéro, un client, un montant et une date d'échéance
     */
    public function issue(): self
    {
        if ($this->status === self::STATUS_DRAFT) {
            // Vérifier que la facture est complète avant de l'émettre
            if ($this->invoiceNumber 
                && $this->customer 
                && (float) $this->totalAmount > 0
                && $this->dueAt !== null
            ) {
                $this->status = self::STATUS_ISSUED;
                $this->issuedAt = new \DateTimeImmutable();
            }
        }
        
        return $this;
    }

    /**
     * Marque la facture comme payée et met à jour la date de paiement
     */
    public function markAsPaid(): self
    {
        if ($this->status !== self::STATUS_PAID) {
            $this->status = self::STATUS_PAID;
            $this->paidAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    /**
     * Calcule le nombre de jours de retard d'une facture
     * Le retard est calculé à partir du 10ème jour du mois (date limite de paiement)
     * Retourne null si la facture n'est pas en retard ou si elle est payée
     * 
     * Règles:
     * - Le 10ème jour du mois = date limite de paiement
     * - Le retard est calculé à partir du 10ème jour (pas de la date d'échéance)
     */
    public function getDaysOverdue(?\DateTimeInterface $currentDate = null): ?int
    {
        // Si la facture est payée, elle n'est pas en retard
        if ($this->isPaid()) {
            return null;
        }

        // Si la facture n'est pas en retard, retourner null
        if ($this->status !== self::STATUS_OVERDUE) {
            return null;
        }

        $now = $currentDate ?? new \DateTimeImmutable();
        
        // Calculer le 10ème jour du mois de la facture (date limite de paiement)
        $invoiceMonth = (int) $this->issuedAt->format('n');
        $invoiceYear = (int) $this->issuedAt->format('Y');
        $paymentDeadline = new \DateTimeImmutable(sprintf('%d-%d-10 23:59:59', $invoiceYear, $invoiceMonth));
        
        // Calculer les jours de retard uniquement si on est après le 10ème jour du mois
        // et que nous sommes encore dans le même mois
        $currentMonth = (int) $now->format('n');
        $currentYear = (int) $now->format('Y');
        $currentDay = (int) $now->format('d');
        
        // Vérifier qu'on est dans le même mois et après le 10ème jour
        if ($currentMonth === $invoiceMonth && $currentYear === $invoiceYear && $currentDay > 10) {
            // Le retard est calculé à partir du 10ème jour
            // Si on est le 12ème jour et non payé, retard = 12 - 10 = 2 jours
            $daysOverdue = $currentDay - 10;
            return max(0, $daysOverdue);
        }
        
        // Si on est dans un mois suivant ou avant le 10ème jour, retourner null
        return null;
    }

    /**
     * Vérifie si la facture est en retard
     */
    public function isOverdue(?\DateTimeInterface $currentDate = null): bool
    {
        return $this->getDaysOverdue($currentDate) !== null;
    }

    public function getBillingMonth(): \DateTimeInterface
    {
        return $this->billingMonth;
    }

    public function setBillingMonth(\DateTimeInterface $billingMonth): self
    {
        $this->billingMonth = $billingMonth;
        return $this;
    }

    public function isPauseMonth(): bool
    {
        return $this->isPauseMonth;
    }

    public function setIsPauseMonth(bool $isPauseMonth): self
    {
        $this->isPauseMonth = $isPauseMonth;
        return $this;
    }

    public function isPrepaid(): bool
    {
        return $this->isPrepaid;
    }

    public function setIsPrepaid(bool $isPrepaid): self
    {
        $this->isPrepaid = $isPrepaid;
        return $this;
    }
}

