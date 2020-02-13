<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ParticipantController extends AbstractController
{
    /**
     * @Route("/participant", name="participant")
     */
    public function index()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('participant/index.html.twig', [
            'controller_name' => 'ParticipantController',
        ]);
    }

    /**
     * @Route("/Profil", name="profil")
     * @Route("/Profil/{id}", name="profil")
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function profil(EntityManagerInterface $entityManager, Request $request, $id = null)
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($id == null) {
            $participant = $this->getUser();
        } else {
            $repo = $entityManager->getRepository(Participant::class);
            $participant = $repo->find($id);
        }

        return $this->render('participant/profil.html.twig', [
            'participant' => $participant,
        ]);
    }

    /**
     * @Route("/modifierProfil", name="modifierProfil")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse|Response
     */
    public function modifierProfil(Request $request, UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $entityManager)
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $participant = $this->getUser();

        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $password = $form->get('newPassword')->getViewData();
            $password2 = $form->get('newPassword2')->getViewData();
            $photo = $form->get('photo')->getData();
            if ($photo) {
                $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photo->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $photo->move(
                        $this->getParameter('photo_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $participant->setPhoto($newFilename);

                $testPseudo = $participant->getPseudo();

                //recherche si pseudo identique dans la BD
                $repository = $entityManager->getRepository(Participant::class);
                $testParticipant = $repository->findOneBy(
                    ['pseudo' => $testPseudo]
                );


                if ($testParticipant->getId() == $participant->getId()) {
                    $testParticipant = null;
                }

                if ($testParticipant != null) {
                    $this->addFlash("alert-danger", "Pseudo déja utilisé");
                } else {
                    if ($password == $password2) {

                        $participant->setPassword($password);

                        $hash = $passwordEncoder->encodePassword($participant, $participant->getPassword());

                        $participant->setPassword($hash);

                        $entityManager->flush();

                        $this->addFlash("success", "Inscription OK !");

                        return $this->redirectToRoute('profil');
                    } else {
                        $this->addFlash("alert-danger", "Mot de passe pas identique !");
                    }
                }

            }
        }

        return $this->render('security/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/gestion/liste", name = "liste")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function listerParticipants(EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $participantsRepo = $em->getRepository(Participant::class);
        $participants = $participantsRepo->findAll();

        return $this->render("participant/liste.html.twig", [
            'participants'=> $participants,
        ]);
    }


    /**
     * @Route("/gestion/liste/{id}", name = "desactiver")
     * @param $id
     * @param EntityManagerInterface $em
     */
    public function desactiver($id, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $participantsRepo = $em->getRepository(Participant::class);
        $participant = $participantsRepo->find($id);

        $participant->setActif(false);

        $em->flush();

        return $this->redirectToRoute('liste', ['id'=>$id]);
    }

    /**
     * @Route("/gestion/liste/{id}", name = "activer")
     * @param $id
     * @param EntityManagerInterface $em
     */
    public function activer($id, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $participantsRepo = $em->getRepository(Participant::class);
        $participant = $participantsRepo->find($id);

        $participant->setActif(true);

        
        $em->flush();

        return $this->redirectToRoute('liste', ['id'=>$id]);
    }
}

