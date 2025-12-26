<?php

namespace App\Controller\Api;

use App\Entity\Billet;
use App\Repository\Organisateur\BilletRepository;
use App\Repository\Organisateur\TicketInvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/tickets/api', name: 'organisateur_ticket_api_')]
#[IsGranted('ROLE_ORGANIZER')]
class TicketApiController extends AbstractController
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private BilletRepository $billetRepository,
        private TicketInvoiceRepository $ticketInvoiceRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Scanner un ticket par son code QR
     */
    #[Route('/scan', name: 'scan', methods: ['POST'])]
    public function scan(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $qrCode = $data['qrCode'] ?? null;

        if (!$qrCode) {
            return $this->json([
                'success' => false,
                'message' => 'Code QR manquant',
            ], 400);
        }

        // Trouver le billet par son code QR
        $billet = $this->billetRepository->findByCodeQr($qrCode);

        if (!$billet) {
            return $this->json([
                'success' => false,
                'message' => 'Billet non trouvé',
            ], 404);
        }

        // Vérifier que l'organisateur a accès à ce billet
        $user = $this->getUser();
        $organizerBillets = $this->billetRepository->findByOrganizer($user);
        $belongsToOrganizer = false;
        foreach ($organizerBillets as $orgBillet) {
            if ((string)$orgBillet->getId() === (string)$billet->getId()) {
                $belongsToOrganizer = true;
                break;
            }
        }

        if (!$belongsToOrganizer) {
            return $this->json([
                'success' => false,
                'message' => 'Vous n\'avez pas accès à ce billet',
            ], 403);
        }

        // Si le billet est valide (en attente), le marquer comme utilisé
        $statusChanged = false;
        if ($billet->getStatut() === Billet::STATUT_VALID) {
            $billet->setStatut(Billet::STATUT_USED);
            $this->entityManager->persist($billet);
            $this->entityManager->flush();
            $statusChanged = true;
        }

        // Récupérer la facture si le billet est lié à une commande
        $facture = null;
        if ($billet->getElementCommande() && $billet->getElementCommande()->getCommande()) {
            $commandeId = $billet->getElementCommande()->getCommande()->getId();
            $facture = $this->ticketInvoiceRepository->findOneBy(['orderId' => $commandeId]);
        }

        // Préparer les données du billet (avec le statut mis à jour)
        $billetData = [
            'id' => $billet->getId(),
            'codeQr' => $billet->getCodeQr(),
            'statut' => $billet->getStatut(),
            'emisLe' => $billet->getEmisLe()?->format(self::DATE_FORMAT),
        ];

        // Informations du type de billet
        if ($billet->getTypeBillet()) {
            $typeBillet = $billet->getTypeBillet();
            $billetData['typeBillet'] = [
                'id' => $typeBillet->getId(),
                'nom' => $typeBillet->getNom(),
                'prixDeBase' => $typeBillet->getPrixDeBase(),
                'devise' => $typeBillet->getDevise(),
            ];

            // Informations de l'événement
            if ($typeBillet->getEvenement()) {
                $event = $typeBillet->getEvenement();
                $billetData['evenement'] = [
                    'id' => $event->getId(),
                    'titre' => $event->getTitre(),
                ];
            }
        }

        // Informations du client
        if ($billet->getUtilisateurProprietaire()) {
            $client = $billet->getUtilisateurProprietaire();
            $billetData['client'] = [
                'id' => $client->getId(),
                'prenom' => $client->getPrenom(),
                'nom' => $client->getNom(),
                'email' => $client->getEmail(),
            ];
        }

        // Informations de la facture
        $factureData = null;
        if ($facture) {
            $factureData = [
                'id' => $facture->getId(),
                'numeroFacture' => $facture->getInvoiceNumber(),
                'statut' => $facture->getStatus(),
                'montantTotal' => $facture->getTotalAmount(),
                'montantSousTotal' => $facture->getSubtotalAmount(),
                'montantTva' => $facture->getTaxAmount(),
                'devise' => $facture->getCurrency(),
                'emiseLe' => $facture->getIssuedAt()?->format(self::DATE_FORMAT),
                'echeanceLe' => $facture->getDueAt()?->format(self::DATE_FORMAT),
                'payeeLe' => $facture->getPaidAt()?->format(self::DATE_FORMAT),
            ];

            // Informations de la commande
            if ($billet->getElementCommande() && $billet->getElementCommande()->getCommande()) {
                $commande = $billet->getElementCommande()->getCommande();
                $factureData['commande'] = [
                    'id' => $commande->getId(),
                    'codePromotion' => $commande->getCodePromotion(),
                    'montantRemise' => $commande->getMontantRemise(),
                    'devise' => $commande->getDevise(),
                ];
            }
        }

        return $this->json([
            'success' => true,
            'billet' => $billetData,
            'facture' => $factureData,
            'statusChanged' => $statusChanged,
        ]);
    }
}

