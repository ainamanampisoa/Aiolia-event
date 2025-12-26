<?php

namespace App\Controller\Organisateur;

use App\Service\Organisateur\PaiementAbonnementService;
use App\Service\Organisateur\MvolaPaymentClientService;
use App\Service\Organisateur\SubscriptionPaymentService;
use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/paiement-abonnement')]
#[IsGranted('ROLE_ORGANIZER')]
class PaiementAbonnementController extends AbstractController
{
    public function __construct(
        private PaiementAbonnementService $paiementAbonnementService,
        private MvolaPaymentClientService $mvolaClient,
        private SubscriptionPaymentService $subscriptionPaymentService,
        private OrganizerProfileRepository $organizerProfileRepository
    ) {
    }
    
    #[Route('', name: 'organisateur_paiement_abonnement_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $organizerProfile = null;
        $mvolaNumber = null;
        $mvolaNumberMasked = null;

        // Récupérer le numéro depuis le profil organisateur
        if ($user) {
            $organizerProfile = $this->organizerProfileRepository->findByUser($user);
            if ($organizerProfile && $organizerProfile->getTelephoneSupport()) {
                $rawPhone = $organizerProfile->getTelephoneSupport();
                error_log('[Paiement Abonnement Index] Numéro customer depuis profil: ' . $rawPhone);
                
                // Normaliser et valider le numéro (rejette les emails et formats invalides)
                $mvolaNumber = $this->normalizePhoneNumber($rawPhone);
                
                if ($mvolaNumber === null) {
                    error_log('[Paiement Abonnement Index] ATTENTION: Le numéro dans le profil n\'est pas valide (email ou format incorrect): ' . $rawPhone);
                    // Ne pas passer mvolaNumber au template si c'est invalide
                    $mvolaNumber = null;
                } else {
                    error_log('[Paiement Abonnement Index] Numéro customer après normalisation: ' . $mvolaNumber);
                }
            }
        }
        
        // Masquer le numéro pour l'affichage seulement si c'est un numéro valide
        if ($mvolaNumber) {
            $mvolaNumberMasked = $this->maskPhoneNumber($mvolaNumber);
        }

        $niveaux = $this->paiementAbonnementService->getAvailableNiveaux();
        $plansGroupedByNiveau = $this->paiementAbonnementService->getPlansGroupedByNiveau();

        // Récupérer le prochain mois de paiement depuis la base de données
        $nextBillingMonth = $user ? $this->subscriptionPaymentService->getNextBillingMonth($user) : new \DateTime('first day of next month');

        // Préparer les données des plans pour JavaScript
        $plansForJs = [];
        foreach ($plansGroupedByNiveau as $niveau => $plans) {
            $plansForJs[$niveau] = [];
            foreach ($plans as $plan) {
                $plansForJs[$niveau][] = [
                    'id' => $plan->getId(),
                    'code' => $plan->getCode(),
                    'nom' => $plan->getNom(),
                    'niveau' => $plan->getNiveau(),
                    'periode_facturation' => $plan->getPeriodeFacturation(),
                    'prix' => (float) $plan->getPrix(),
                    'devise' => $plan->getDevise(),
                ];
            }
        }

        return $this->render('Organisateur/paiementAbonnement/index.html.twig', [
            'niveaux' => $niveaux,
            'plansGroupedByNiveau' => $plansGroupedByNiveau,
            'plansForJs' => $plansForJs,
            'mvolaNumber' => $mvolaNumber,
            'mvolaNumberMasked' => $mvolaNumberMasked,
            'nextBillingMonth' => $nextBillingMonth,
        ]);
    }


    /**
     * Normalise un numéro de téléphone au format MVola (034xxxxxxx)
     * Rejette explicitement les emails et autres formats invalides
     */
    private function normalizePhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }
        
        $phoneStr = trim($phone);
        
        // Vérification explicite : ne doit pas contenir d'email (@)
        if (strpos($phoneStr, '@') !== false) {
            error_log('[Paiement Abonnement] ERREUR: Le champ contient un email au lieu d\'un numéro: ' . $phoneStr);
            return null;
        }
        
        // Vérification : ne doit pas contenir de lettres (sauf + au début)
        if (preg_match('/[a-zA-Z]/', $phoneStr)) {
            error_log('[Paiement Abonnement] ERREUR: Le champ contient des lettres: ' . $phoneStr);
            return null;
        }
        
        // Supprimer tous les caractères non numériques sauf +
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneStr);
        
        // Si après nettoyage, il ne reste presque rien, c'est invalide
        if (strlen($cleaned) < 9) {
            error_log('[Paiement Abonnement] ERREUR: Numéro trop court après nettoyage: ' . $cleaned . ' (original: ' . $phoneStr . ')');
            return null;
        }
        
        // Si le numéro commence par +261, le remplacer par 0
        if (strpos($cleaned, '+261') === 0) {
            $cleaned = '0' . substr($cleaned, 4);
        }
        
        // Si le numéro commence par 261, le remplacer par 0
        if (strpos($cleaned, '261') === 0 && strlen($cleaned) > 9) {
            $cleaned = '0' . substr($cleaned, 3);
        }
        
        // S'assurer que le numéro commence par 0
        if (!str_starts_with($cleaned, '0') && strlen($cleaned) >= 9) {
            $cleaned = '0' . $cleaned;
        }
        
        // Vérifier que le format est correct (10 chiffres commençant par 0)
        if (strlen($cleaned) < 10 || !str_starts_with($cleaned, '0')) {
            error_log('[Paiement Abonnement] ATTENTION: Format de numéro suspect après normalisation: ' . $cleaned . ' (original: ' . $phoneStr . ')');
            return null;
        }
        
        return $cleaned;
    }

    /**
     * Masque un numéro de téléphone pour l'affichage sécurisé
     */
    private function maskPhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Nettoyer le numéro
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        if (strpos($cleaned, '261') === 0 && strlen($cleaned) > 9) {
            $cleaned = '0' . substr($cleaned, 3);
        }

        // Masquer le milieu du numéro
        if (strlen($cleaned) >= 10) {
            $start = substr($cleaned, 0, 3);
            $end = substr($cleaned, -4);
            return $start . '****' . $end;
        }

        if (strlen($cleaned) >= 6) {
            $start = substr($cleaned, 0, 3);
            $end = substr($cleaned, -2);
            return $start . '***' . $end;
        }

        return $phone;
    }

    /**
     * Valide strictement un numéro de téléphone
     * Rejette les emails et autres formats invalides
     */
    private function validatePhoneNumberStrictly(?string $phone): bool
    {
        if (!$phone || trim($phone) === '') {
            return false;
        }
        
        $phoneStr = trim($phone);
        
        // 1. Ne doit pas contenir d'email (@)
        if (strpos($phoneStr, '@') !== false) {
            error_log('[Validation] Numéro contient un email: ' . $phoneStr);
            return false;
        }
        
        // 2. Ne doit pas contenir de lettres
        if (preg_match('/[a-zA-Z]/', $phoneStr)) {
            error_log('[Validation] Numéro contient des lettres: ' . $phoneStr);
            return false;
        }
        
        // 3. Doit contenir uniquement des caractères autorisés
        $validPattern = '/^[\+]?[0-9\s\-\(\)]{10,20}$/';
        if (!preg_match($validPattern, $phoneStr)) {
            error_log('[Validation] Format invalide: ' . $phoneStr);
            return false;
        }
        
        // 4. Doit contenir au moins 9 chiffres
        $digitCount = preg_match_all('/\d/', $phoneStr);
        if ($digitCount < 9) {
            error_log('[Validation] Pas assez de chiffres: ' . $phoneStr . ' Chiffres: ' . $digitCount);
            return false;
        }
        
        return true;
    }

    #[Route('/initiate', name: 'organisateur_paiement_abonnement_initiate', methods: ['POST'])]
    public function initiate(Request $request): Response
    {
        // Récupérer les données
        $data = [];
        $contentType = $request->headers->get('Content-Type', '');
        $rawContent = $request->getContent();
        
        error_log('[Paiement Abonnement Initiate] Raw request content: ' . substr($rawContent, 0, 500));
        
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode($rawContent, true) ?? [];
        } else {
            $data = $request->request->all();
        }
        
        $planId = $data['plan_id'] ?? null;
        $customerPhoneRaw = $data['customer_phone'] ?? '';
        $customerPhone = (string) $customerPhoneRaw;
        $niveau = $data['niveau'] ?? null;
        $periode = $data['periode'] ?? null;
        $montant = $data['montant'] ?? null;
        $modePaiement = $data['mode_paiement'] ?? 'mvola';
        $pin = $data['pin'] ?? null;
        
        error_log('[Paiement Abonnement Initiate] Données extraites:');
        error_log('  - customer_phone: ' . $customerPhone);
        error_log('  - customer_phone length: ' . strlen($customerPhone));
        error_log('  - pin fourni: ' . ($pin ? 'Oui (masqué)' : 'Non'));

        // Validation stricte du numéro de téléphone
        if (!$this->validatePhoneNumberStrictly($customerPhone)) {
            $errorMsg = 'Le numéro de téléphone ne peut pas contenir d\'email. Veuillez entrer uniquement des chiffres. Exemple: 0341234567';
            
            if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $this->json([
                    'success' => false,
                    'error' => $errorMsg,
                    'console_logs' => [
                        'error' => '❌ Numéro de téléphone invalide',
                        'received_phone' => $customerPhone,
                        'is_email' => strpos($customerPhone, '@') !== false,
                        'contains_letters' => preg_match('/[a-zA-Z]/', $customerPhone) ? 'true' : 'false'
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', $errorMsg);
            return $this->redirectToRoute('organisateur_paiement_abonnement_index');
        }

        // Si le numéro est vide, essayer de récupérer depuis le profil
        if (empty($customerPhone)) {
            $user = $this->getUser();
            if ($user) {
                $organizerProfile = $this->organizerProfileRepository->findByUser($user);
                if ($organizerProfile && $organizerProfile->getTelephoneSupport()) {
                    $rawPhone = $organizerProfile->getTelephoneSupport();
                    error_log('[Paiement Abonnement Initiate] Numéro customer récupéré depuis profil: ' . $rawPhone);
                    
                    // Normaliser et valider (rejette les emails)
                    $normalized = $this->normalizePhoneNumber($rawPhone);
                    if ($normalized === null) {
                        error_log('[Paiement Abonnement Initiate] ERREUR: Le numéro dans le profil est invalide (email ou format incorrect): ' . $rawPhone);
                        $errorMsg = 'Le numéro de téléphone enregistré dans votre profil n\'est pas valide. Veuillez le corriger dans vos paramètres de compte.';
                        
                        if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                            return $this->json([
                                'success' => false,
                                'error' => $errorMsg,
                            ], Response::HTTP_BAD_REQUEST);
                        }
                        $this->addFlash('error', $errorMsg);
                        return $this->redirectToRoute('organisateur_paiement_abonnement_index');
                    }
                    $customerPhone = $normalized;
                }
            }
        }
        
        // Normaliser le numéro (la validation a déjà été faite ci-dessus si récupéré du profil)
        $originalPhone = $customerPhone;
        $customerPhone = $this->normalizePhoneNumber($customerPhone);
        
        if (empty($customerPhone)) {
            $errorMsg = 'Le numéro de téléphone est requis ou invalide. ' .
                       'Veuillez vérifier le format (0341234567 ou +261341234567)';
            
            if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $this->json([
                    'success' => false,
                    'error' => $errorMsg,
                    'console_logs' => [
                        'error' => '❌ Numéro de téléphone manquant ou invalide',
                        'received_phone' => $originalPhone ?? 'NULL',
                        'normalized_phone' => $customerPhone ?? 'NULL'
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', $errorMsg);
            return $this->redirectToRoute('organisateur_paiement_abonnement_index');
        }
        
        error_log('[Paiement Abonnement] Numéro validé et prêt: ' . $customerPhone);

        // Récupérer le plan
        $plan = null;
        if ($planId && is_numeric($planId)) {
            $plan = $this->paiementAbonnementService->getAllPlans();
            $plan = array_filter($plan, fn($p) => $p->getId() == $planId);
            $plan = !empty($plan) ? reset($plan) : null;
        }

        // Si le plan n'est pas trouvé par ID, essayer de le trouver par niveau et période
        if (!$plan && $niveau && $periode) {
            $plan = $this->paiementAbonnementService->getPlansByNiveau($niveau);
            $plan = array_filter($plan, fn($p) => $p->getPeriodeFacturation() === $periode);
            $plan = !empty($plan) ? reset($plan) : null;
        }

        // Déterminer le montant
        $amount = 0;
        if ($plan) {
            $amount = (float) $plan->getPrix();
        } elseif ($montant && is_numeric($montant)) {
            $amount = (float) $montant;
        } else {
            $this->addFlash('error', 'Impossible de déterminer le montant du paiement.');
            return $this->redirectToRoute('organisateur_paiement_abonnement_index');
        }

        // Validation du montant
        if ($amount <= 0) {
            $this->addFlash('error', 'Le montant doit être supérieur à zéro.');
            return $this->redirectToRoute('organisateur_paiement_abonnement_index');
        }

        // Vérifier que le plan existe
        if (!$plan) {
            $this->addFlash('error', 'Plan d\'abonnement introuvable.');
            return $this->redirectToRoute('organisateur_paiement_abonnement_index');
        }
        
        // Générer une référence unique
        $reference = 'SUB-' . uniqid() . '-' . $plan->getId();
        
        // Description de la transaction
        $description = $plan->getNom() . ' - ' . $plan->getNiveau();

        try {
            $user = $this->getUser();
            if (!$user) {
                $this->addFlash('error', 'Vous devez être connecté pour effectuer un paiement.');
                return $this->redirectToRoute('organisateur_paiement_abonnement_index');
            }

            // Initier le paiement avec MVola
            if ($modePaiement === 'mvola') {
                // Si un PIN est fourni, l'utiliser pour le paiement
                $pinToUse = null;
                if (!empty($pin) && is_string($pin)) {
                    $pinToUse = trim($pin);
                    // Valider que le PIN contient uniquement des chiffres et a la bonne longueur
                    if (preg_match('/^[0-9]{4,6}$/', $pinToUse)) {
                        error_log('[Paiement Abonnement] PIN validé et sera utilisé pour la transaction');
                    } else {
                        error_log('[Paiement Abonnement] PIN invalide, transaction sans PIN');
                        $pinToUse = null;
                    }
                }
                
                $result = $this->mvolaClient->initiateTransaction(
                    $amount,
                    $customerPhone,
                    $reference,
                    $description,
                    $pinToUse
                );

                if ($result['success']) {
                    $serverCorrelationId = $result['serverCorrelationId'] ?? null;
                    
                    // Si c'est une requête AJAX
                    if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                        return $this->json([
                            'success' => true,
                            'message' => 'Transaction initiée avec succès',
                            'transaction_reference' => $reference,
                            'server_correlation_id' => $serverCorrelationId,
                            'console_logs' => [
                                'transaction_initiated' => '✅ Transaction MVola initiée avec succès',
                                'reference' => $reference,
                                'server_correlation_id' => $serverCorrelationId,
                            ]
                        ]);
                    }
                    
                    // Traiter le paiement réussi
                    $paymentResult = $this->subscriptionPaymentService->processPaymentSuccess(
                        $user,
                        $plan,
                        $amount,
                        $modePaiement,
                        $reference,
                        $serverCorrelationId
                    );

                    if ($paymentResult['success'] && $paymentResult['email_sent']) {
                        $invoice = $paymentResult['invoice'];
                        $this->addFlash('success', 
                            'Transaction initiée avec succès ! ' .
                            'Votre facture ' . $invoice->getInvoiceNumber() . 
                            ' a été générée et envoyée par email.'
                        );
                    } else {
                        $errorMsg = $paymentResult['error'] ?? 'Erreur inconnue';
                        $this->addFlash('warning', 
                            'Transaction initiée mais erreur lors de l\'envoi de l\'email ou de la génération de la facture: ' . 
                            $errorMsg .
                            '. Veuillez contacter le support avec la référence: ' . $reference
                        );
                    }
                    
                } else {
                    $errorMsg = $result['error'] ?? 'Erreur inconnue';
                    
                    // Si c'est une requête AJAX
                    if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                        return $this->json([
                            'success' => false,
                            'error' => $errorMsg,
                            'console_logs' => [
                                'transaction_failed' => '❌ Échec de l\'initiation de la transaction MVola',
                                'error' => $errorMsg,
                            ]
                        ], Response::HTTP_BAD_REQUEST);
                    }
                    
                    $this->addFlash('error', 'Erreur lors du paiement: ' . $errorMsg);
                }
            } else {
                $errorMsg = 'Le mode de paiement ' . $modePaiement . ' n\'est pas encore implémenté.';
                
                // Si c'est une requête AJAX
                if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                    return $this->json([
                        'success' => false,
                        'error' => $errorMsg,
                    ], Response::HTTP_BAD_REQUEST);
                }
                
                $this->addFlash('error', $errorMsg);
            }

            return $this->redirectToRoute('organisateur_paiement_abonnement_index');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur système: ' . $e->getMessage());
            error_log('[Paiement Abonnement] Erreur: ' . $e->getMessage());
            error_log('[Paiement Abonnement] Trace: ' . $e->getTraceAsString());
            return $this->redirectToRoute('organisateur_paiement_abonnement_index');
        }
    }

    /**
     * Endpoint pour finaliser le paiement
     */
    #[Route('/finalize', name: 'organisateur_paiement_abonnement_finalize', methods: ['POST'])]
    public function finalize(Request $request): JsonResponse
    {
        // Récupérer les données
        $data = [];
        $contentType = $request->headers->get('Content-Type', '');
        
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode($request->getContent(), true) ?? [];
        } else {
            $data = $request->request->all();
        }
        
        $transactionReference = $data['transaction_reference'] ?? null;
        $serverCorrelationId = $data['server_correlation_id'] ?? null;
        $planId = $data['plan_id'] ?? null;
        $niveau = $data['niveau'] ?? null;
        $periode = $data['periode'] ?? null;
        $montant = $data['montant'] ?? null;
        $modePaiement = $data['mode_paiement'] ?? 'mvola';

        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'error' => 'Vous devez être connecté pour finaliser le paiement.',
                    'console_logs' => [
                        'error' => '❌ Utilisateur non authentifié'
                    ]
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Récupérer le plan
            $plan = null;
            if ($planId && is_numeric($planId)) {
                $allPlans = $this->paiementAbonnementService->getAllPlans();
                $plan = array_filter($allPlans, fn($p) => $p->getId() == $planId);
                $plan = !empty($plan) ? reset($plan) : null;
            }

            if (!$plan && $niveau && $periode) {
                $plansForNiveau = $this->paiementAbonnementService->getPlansByNiveau($niveau);
                $plan = array_filter($plansForNiveau, fn($p) => $p->getPeriodeFacturation() === $periode);
                $plan = !empty($plan) ? reset($plan) : null;
            }

            if (!$plan) {
                return $this->json([
                    'success' => false,
                    'error' => 'Plan d\'abonnement introuvable.',
                    'console_logs' => [
                        'error' => '❌ Plan introuvable'
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }

            $amount = (float) ($montant ?: $plan->getPrix());

            // Traiter le paiement
            $paymentResult = $this->subscriptionPaymentService->processPaymentSuccess(
                $user,
                $plan,
                $amount,
                $modePaiement,
                $transactionReference,
                $serverCorrelationId
            );

            if ($paymentResult['success'] && $paymentResult['email_sent']) {
                $invoice = $paymentResult['invoice'];
                $customer = $invoice->getCustomer();
                
                return $this->json([
                    'success' => true,
                    'email_sent' => true,
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'invoice_id' => $invoice->getId(),
                    'customer_email' => $customer->getEmail(),
                    'console_logs' => [
                        'email_sent' => '✅ Email envoyé avec succès à ' . $customer->getEmail(),
                        'invoice_created' => '✅ Facture créée et enregistrée dans la base de données',
                        'invoice_number' => $invoice->getInvoiceNumber(),
                        'invoice_id' => $invoice->getId()
                    ]
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'email_sent' => $paymentResult['email_sent'] ?? false,
                    'error' => $paymentResult['error'] ?? 'Erreur lors de la finalisation du paiement',
                    'invoice_number' => $paymentResult['invoice_number'] ?? null,
                    'console_logs' => [
                        'email_sent' => $paymentResult['email_sent'] ? '✅ Email envoyé' : '❌ Échec de l\'envoi de l\'email',
                        'error' => $paymentResult['error'] ?? 'Erreur inconnue'
                    ]
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            error_log('[Paiement Abonnement Finalize] Erreur: ' . $e->getMessage());
            error_log('[Paiement Abonnement Finalize] Trace: ' . $e->getTraceAsString());
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur système: ' . $e->getMessage(),
                'console_logs' => [
                    'error' => '❌ Erreur système: ' . $e->getMessage()
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Endpoint API pour vérifier le statut d'un paiement
     */
    #[Route('/status/{serverCorrelationId}', name: 'organisateur_paiement_abonnement_status', methods: ['GET'])]
    public function checkStatus(string $serverCorrelationId): JsonResponse
    {
        try {
            $result = $this->mvolaClient->getTransactionStatus($serverCorrelationId);
            
            return $this->json([
                'success' => $result['success'],
                'status' => $result['status'] ?? 'unknown',
                'transaction' => $result['transaction'] ?? null,
                'error' => $result['error'] ?? null,
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Endpoint pour recevoir les callbacks MVola
     */
    #[Route('/callback', name: 'organisateur_paiement_abonnement_callback', methods: ['POST'])]
    public function callback(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            error_log('[MVola Callback] Données reçues: ' . json_encode($data));
            
            $serverCorrelationId = $data['serverCorrelationId'] ?? null;
            $status = $data['status'] ?? null;
            $transactionReference = $data['transactionReference'] ?? null;
            
            if ($serverCorrelationId && $status) {
                error_log("[MVola Callback] Transaction $serverCorrelationId mise à jour: $status");
            }
            
            return $this->json([
                'success' => true,
                'message' => 'Callback traité',
            ]);
            
        } catch (\Exception $e) {
            error_log('[MVola Callback] Erreur: ' . $e->getMessage());
            
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}