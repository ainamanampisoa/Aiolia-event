<?php

namespace App\Controller\Organisateur;

use App\Service\Organisateur\PaiementAbonnementService;
use App\Service\Organisateur\MvolaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/paiement-abonnement')]
#[IsGranted('ROLE_ORGANIZER')]
class PaiementAbonnementController extends AbstractController
{
    public function __construct(
        private PaiementAbonnementService $paiementAbonnementService,
        private MvolaService $mvolaService
    ) {
    }
    
    #[Route('', name: 'organisateur_paiement_abonnement_index', methods: ['GET'])]
    public function index(): Response
    {
        $niveaux = $this->paiementAbonnementService->getAvailableNiveaux();
        $plansGroupedByNiveau = $this->paiementAbonnementService->getPlansGroupedByNiveau();

        return $this->render('Organisateur/paiementAbonnement/index.html.twig', [
            'niveaux' => $niveaux,
            'plansGroupedByNiveau' => $plansGroupedByNiveau,
        ]);
    }

    #[Route('/initiate', name: 'organisateur_paiement_abonnement_initiate', methods: ['POST'])]
    public function initiate(Request $request): Response
    {
        $planId = $request->request->get('plan_id');
        $customerPhone = $request->request->get('customer_phone');

        // Récupérer le plan sélectionné
        // Générer une référence unique
        $reference = 'SUB-' . uniqid();

        try {
            // Initier le paiement MVola
            $result = $this->mvolaService->initiatePayment(
                $customerPhone,
                1000, // Montant du plan
                $reference,
                'Paiement abonnement'
            );

            $this->addFlash('success', 'Paiement initié. Veuillez confirmer sur votre téléphone.');

            return $this->redirectToRoute('organisateur_paiement_abonnement_index');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            return $this->redirectToRoute('organisateur_paiement_abonnement_index');
        }
    }
}