<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserValidationRequest;
use App\Form\RegistrationFormType;
use App\Repository\RoleRepository;
use App\Service\AuditLogService;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, rediriger vers le dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Récupérer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        
        $lastIdentifier = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_identifier' => $lastIdentifier,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        AuditLogService $auditLogService,
        RoleRepository $roleRepository
    ): Response {
        // Si l'utilisateur est déjà connecté, rediriger vers le dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requestedRole = $form->get('requestedRole')->getData();
            $requestReason = $form->get('requestReason')->getData();

            // Encoder le mot de passe
            $user->setPasswordHash(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $defaultRole = $roleRepository->findOneByCode('user');

            if (!$defaultRole) {
                throw new \RuntimeException('Le rôle "user" est introuvable. Veuillez vérifier la configuration des rôles.');
            }

            // Déterminer le statut du compte selon le rôle demandé
            if (in_array($requestedRole, ['organizer', 'co_organizer'])) {
                // Si l'utilisateur demande à être organisateur ou co-organisateur
                // Le compte est en attente de validation
                $user->setRole($defaultRole); // Rôle temporaire
                $user->setAccountStatus('pending_validation');
                
                // Créer une demande de validation
                $validationRequest = new UserValidationRequest();
                $validationRequest->setUser($user);
                $validationRequest->setRequestedRole($requestedRole);
                $validationRequest->setReason($requestReason);
                $validationRequest->setStatus('pending');
                
                $entityManager->persist($validationRequest);
                
                $message = 'Votre demande d\'inscription en tant que ' . 
                    ($requestedRole === 'organizer' ? 'Organisateur' : 'Co-organisateur') . 
                    ' a été envoyée. Vous recevrez une notification une fois que votre compte sera validé par un administrateur.';
            } else {
                // Utilisateur normal - compte actif immédiatement
                $user->setRole($defaultRole);
                $user->setAccountStatus('active');
                $message = 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.';
            }

            // Sauvegarder l'utilisateur
            $entityManager->persist($user);
            $entityManager->flush();

            // Logger la création de l'utilisateur
            $auditLogService->log(
                AuditLogService::ACTION_USER_CREATED,
                'User',
                $user->getId(),
                [
                    'email' => $user->getEmail(),
                    'name' => $user->getFullName(),
                    'requested_role' => $requestedRole,
                    'account_status' => $user->getAccountStatus(),
                ],
                null
            );

            // Message de succès
            $this->addFlash('success', $message);

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/forgot-password', name: 'app_forgot_password_request')]
    public function forgotPasswordRequest(Request $request): Response
    {
        // Si l'utilisateur est déjà connecté, rediriger vers le dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            
            // TODO: Implémenter la logique d'envoi d'email de réinitialisation
            // Pour l'instant, on affiche juste un message
            
            $this->addFlash('success', 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation.');
            
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Cette méthode peut rester vide - elle sera interceptée par la clé logout dans votre firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        // Si l'utilisateur est connecté, rediriger vers le dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Sinon, rediriger vers la page de connexion
        return $this->redirectToRoute('app_login');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(UserStatsService $userStatsService): Response
    {
        // Cette route nécessite une authentification (accepte aussi remember-me)
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        // Récupérer les statistiques calculées dynamiquement
        $stats = $userStatsService->getUserStatistics($this->getUser());

        return $this->render('dashboard/index.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats,
        ]);
    }

    /**
     * Découpe un identifiant "Prénom Nom" en deux parties.
     *
     * @return array{0: string, 1: string}
     */
}