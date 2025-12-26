<?php

namespace App\Controller\Organisateur;

use App\Entity\Venue;
use App\Repository\Organisateur\OrganizerProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/venues')]
#[IsGranted('ROLE_ORGANIZER')]
class VenueController extends AbstractController
{
    public function __construct(
        private readonly OrganizerProfileRepository $organizerProfileRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/new', name: 'organisateur_venues_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        $organizerProfile = $this->organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur non trouvé.');
        }

        $venue = new Venue();
        $venue->setOrganizerProfile($organizerProfile);

        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $description = $request->request->get('description');
            $ligneAdresse1 = $request->request->get('ligne_adresse_1');
            $ligneAdresse2 = $request->request->get('ligne_adresse_2');
            $ville = $request->request->get('ville');
            $region = $request->request->get('region');
            $codePostal = $request->request->get('code_postal');
            $codePays = $request->request->get('code_pays', 'MG');
            $emailContact = $request->request->get('email_contact');
            $telephoneContact = $request->request->get('telephone_contact');
            $capacite = $request->request->get('capacite');

            if (empty($nom)) {
                $this->addFlash('error', 'Le nom du lieu est obligatoire.');
            } else {
                $venue->setNom($nom);
                $venue->setDescription($description);
                $venue->setLigneAdresse1($ligneAdresse1);
                $venue->setLigneAdresse2($ligneAdresse2);
                $venue->setVille($ville);
                $venue->setRegion($region);
                $venue->setCodePostal($codePostal);
                $venue->setCodePays($codePays);
                $venue->setEmailContact($emailContact);
                $venue->setTelephoneContact($telephoneContact);
                if ($capacite) {
                    $venue->setCapacite((int) $capacite);
                }
                $venue->setEstActif(true);

                $this->entityManager->persist($venue);
                $this->entityManager->flush();

                $this->addFlash('success', 'Le lieu "' . $venue->getNom() . '" a été créé avec succès.');

                // Rediriger vers la page de création d'événement avec le lieu pré-sélectionné
                $returnTo = $request->query->get('return_to') ?? $request->request->get('return_to');
                if ($returnTo === 'events_new') {
                    return $this->redirectToRoute('app_event_new', [
                        'venue_id' => $venue->getId()
                    ]);
                }

                // Rediriger vers la page de création d'événement si on vient de là
                $referer = $request->headers->get('referer');
                if ($referer && str_contains($referer, '/organisateur/events/new')) {
                    return $this->redirect($referer . '?venue_id=' . $venue->getId());
                }

                return $this->redirectToRoute('app_event_new', [
                    'venue_id' => $venue->getId()
                ]);
            }
        }

        return $this->render('Organisateur/venues/new.html.twig', [
            'venue' => $venue,
        ]);
    }
}

