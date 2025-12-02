<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\Role as UserRoleEnum;
use App\Form\RegistrationFormType;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    /**
     * Redirige l'utilisateur connecté vers la page appropriée selon son rôle
     */
    private function redirectBasedOnRole(): Response
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }
        
        $role = $user->getRole();
        
        if ($role === UserRoleEnum::ADMIN) {
            return $this->redirectToRoute('app_reports_statistiques');
        }
        
        if ($role === UserRoleEnum::ORGANIZER) {
            return $this->redirectToRoute('organisateur_dashboard_statistiques');
        }
        
        // Pour les utilisateurs normaux, rediriger vers la liste des événements
        return $this->redirectToRoute('app_event_index');
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, rediriger selon son rôle
        if ($this->getUser()) {
            return $this->redirectBasedOnRole();
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
        AuditLogService $auditLogService
    ): Response {
        // Si l'utilisateur est déjà connecté, rediriger selon son rôle
        if ($this->getUser()) {
            return $this->redirectBasedOnRole();
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requestedRole = $form->get('requestedRole')->getData();
            $requestReason = $form->get('requestReason')->getData();

            // Encoder le mot de passe
            $user->setHashMotDePasse(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Exemple d'extrait dans la méthode register

            if ($requestedRole === UserRoleEnum::ORGANIZER) {
                $user->setRole(UserRoleEnum::ORGANIZER);
                $user->setStatutCompte('pending_validation');

                $message = 'Votre demande d\'inscription en tant qu\'organisateur est en cours de traitement. Vous serez informé dès la validation de votre compte.';
            } else {
                $user->setRole(UserRoleEnum::USER);
                $user->setStatutCompte('active');
                $message = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            }


            $user->setIdentifiantConnexion($user->getEmail());
            $user->setMethodeConnexion(User::AUTH_PROVIDER_PASSWORD);

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
                    'name' => $user->getNomComplet(),
                    'requested_role' => $requestedRole,
                    'request_reason' => $requestReason,
                    'account_status' => $user->getStatutCompte(),
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
        // Si l'utilisateur est déjà connecté, rediriger selon son rôle
        if ($this->getUser()) {
            return $this->redirectBasedOnRole();
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
        // Si l'utilisateur est connecté, rediriger selon son rôle
        if ($this->getUser()) {
            return $this->redirectBasedOnRole();
        }

        // Sinon, rediriger vers la page de connexion
        return $this->redirectToRoute('app_login');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        // Rediriger selon le rôle de l'utilisateur
        if ($this->getUser()) {
            return $this->redirectBasedOnRole();
        }
        
        // Si non connecté, rediriger vers la page de connexion
        return $this->redirectToRoute('app_login');
    }

    /**
     * Découpe un identifiant "Prénom Nom" en deux parties.
     *
     * @return array{0: string, 1: string}
     */
}