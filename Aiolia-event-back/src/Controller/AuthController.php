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
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, rediriger vers les statistiques
        if ($this->getUser()) {
            return $this->redirectToRoute('app_reports_statistiques');
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
        // Si l'utilisateur est déjà connecté, rediriger vers les statistiques
        if ($this->getUser()) {
            return $this->redirectToRoute('app_reports_statistiques');
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

            // Déterminer le statut du compte selon le rôle demandé
            if ($requestedRole === UserRoleEnum::ORGANIZER) {
                // Comptes organisateurs : rôle assigné mais compte en attente
                $user->setRole(UserRoleEnum::ORGANIZER);
                $user->setStatutCompte('pending_validation');

                $message = 'Votre demande d\'inscription en tant qu\'Organisateur a été envoyée. Vous recevrez une notification une fois que votre compte sera validé par un administrateur.';
            } else {
                // Utilisateur normal - compte actif immédiatement
                $user->setRole(UserRoleEnum::USER);
                $user->setStatutCompte('active');
                $message = 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.';
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
        // Si l'utilisateur est déjà connecté, rediriger vers les statistiques
        if ($this->getUser()) {
            return $this->redirectToRoute('app_reports_statistiques');
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
        // Si l'utilisateur est connecté, rediriger vers les statistiques
        if ($this->getUser()) {
            return $this->redirectToRoute('app_reports_statistiques');
        }

        // Sinon, rediriger vers la page de connexion
        return $this->redirectToRoute('app_login');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        // Rediriger vers les statistiques
        return $this->redirectToRoute('app_reports_statistiques');
    }

    /**
     * Découpe un identifiant "Prénom Nom" en deux parties.
     *
     * @return array{0: string, 1: string}
     */
}