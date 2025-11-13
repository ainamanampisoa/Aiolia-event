<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\CloudinaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('/test/cloudinary', name: 'app_profile_cloudinary_test', methods: ['GET'])]
    public function testCloudinary(CloudinaryService $cloudinaryService): Response
    {
        return $this->json($cloudinaryService->testConnection());
    }

    #[Route('', name: 'app_profile_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('profile/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $user->setFirstName($request->request->get('first_name'));
            $user->setLastName($request->request->get('last_name'));
            $user->setPhone($request->request->get('phone'));

            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('app_profile_index');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/password', name: 'app_profile_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');

            if ($passwordHasher->isPasswordValid($user, $currentPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPasswordHash($hashedPassword);
                $em->flush();

                $this->addFlash('success', 'Mot de passe changé avec succès !');
                return $this->redirectToRoute('app_profile_index');
            } else {
                $this->addFlash('error', 'Mot de passe actuel incorrect !');
            }
        }

        return $this->render('profile/password.html.twig');
    }

    #[Route('/photo', name: 'app_profile_photo', methods: ['POST'])]
    public function uploadPhoto(
        Request $request,
        EntityManagerInterface $em,
        CloudinaryService $cloudinaryService,
        SluggerInterface $slugger
    ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        if (!$this->isCsrfTokenValid('profile_photo_upload', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Le jeton CSRF est invalide.');
            return $this->redirectToRoute('app_profile_index');
        }

        /** @var User $user */
        $user = $this->getUser();
        $uploadedFile = $request->files->get('avatar');

        if (!$uploadedFile) {
            $this->addFlash('error', 'Aucun fichier sélectionné.');
            return $this->redirectToRoute('app_profile_index');
        }

        if (!$cloudinaryService->isValidImageType($uploadedFile)) {
            $this->addFlash('error', 'Format de fichier non supporté. Veuillez choisir une image JPG, PNG, GIF ou WEBP.');
            return $this->redirectToRoute('app_profile_index');
        }

        $displayName = trim(sprintf('%s %s', (string) $user->getFirstName(), (string) $user->getLastName()));
        if ($displayName === '') {
            $displayName = $user->getEmail() ?? 'profil';
        }

        $slug = $slugger->slug($displayName)->lower()->toString();
        $suffix = time();

        if (!$cloudinaryService->isConfigured()) {
            $this->addFlash('error', 'Cloudinary n\'est pas configuré. Veuillez ajouter vos identifiants.');
            return $this->redirectToRoute('app_profile_index');
        }

        $uploadResult = $cloudinaryService->uploadImage($uploadedFile, 'users/avatars', [
            'public_id' => sprintf('%s-%d', $slug, $suffix),
            'overwrite' => true,
            'invalidate' => true,
        ]);

        if (!$uploadResult['success']) {
            $this->addFlash('error', $uploadResult['error'] ?? 'Erreur lors de l\'envoi de l\'image.');
            return $this->redirectToRoute('app_profile_index');
        }

        $user->setAvatarUrl($uploadResult['url']);
        $em->flush();

        $this->addFlash('success', 'Photo mise à jour avec succès !');
        return $this->redirectToRoute('app_profile_index');
    }
}

