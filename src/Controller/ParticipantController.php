<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Site;
use App\Essai\Util;
use App\Form\FichierCsvType;
use App\Form\ParticipantType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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
            'participants' => $participants,
        ]);
    }


    /**
     * @Route("/enregistrer/fichier", name ="enregistrerFichier")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return RedirectResponse
     */
    public function extraireFichierCsv(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = new Participant();

        $form = $this->createForm(FichierCsvType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $brochure = $form->get('fichierCsv')->getViewData();

            $handle = fopen($brochure, "r");
            while (($data = fgetcsv($handle, 100, ',')) !== false) {

                $repo = $entityManager->getRepository(Participant::class);
                $userBDD = $repo->findOneBy([
                    'pseudo' => trim($data[0])
                ]);

                if (trim($data[0]) != 'pseudo' && $userBDD == null) {
                    $user->setPseudo(trim($data[0]));
                    $user->setPassword(trim($data[1]));
                    $user->setNom(trim($data[2]));
                    $user->setPrenom(trim($data[3]));
                    $user->setTelephone(trim($data[4]));
                    $user->setMail(trim($data[5]));

                    dump($user);

                    // recherche site en BDD
                    $repo = $entityManager->getRepository(Site::class);
                    $siteBDD = $repo->findOneBy([
                        'nom' => $data[6]
                    ]);

                    if ($siteBDD != null) {
                        $user->setSite($siteBDD);
                    } else {
                        $newSite = new Site();
                        $newSite->setNom(trim($data[6]));
                        $entityManager->persist($newSite);
                        $user->setSite($newSite);
                    }

                    $hash = $passwordEncoder->encodePassword($user, $user->getPassword());
                    $user->setPassword($hash);
                    $user->setActif(true);

                    $entityManager->persist($user);
                    $entityManager->flush();

                    $this->addFlash("success", " Participant inscrit !");

                } else {
                    if ($data[0] != 'pseudo' ){
                        $this->addFlash("danger", " $data[0] déja inscrit !");
                    }
                }
            }
        }

        return $this->render("participant/enregistrerFichier.html.twig", [
            'form' => $form->createView(),
        ]);


        // code pour encoder
//        $encoders = [new CsvEncoder(), new JsonEncoder(), new XmlEncoder()];
//        $normalizers = [new ObjectNormalizer()];
//        $serializer = new Serializer($normalizers, $encoders);
//
//        $userCsv = $serializer->serialize($listUser, 'csv');
//        dump($userCsv);
//
//        $Users = $serializer->decode($listUser, 'csv');
//
//        foreach ($Users as $user) {
//            $userObj = $serializer->denormalize($user, Util::class);
//            $partcipant = new Participant();
//            $partcipant->setPseudo($userObj->getNom());
//            $partcipant->setPrenom($userObj->getPrenom());
//            $partcipant->setMail($userObj->getEmail());
//        }
    }


    /**
     * @Route("/gestion/liste/desactiver/{id}", name = "desactiver")
     * @param $id
     * @param EntityManagerInterface $em
     * @return RedirectResponse
     */
    public function desactiver($id, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $participantsRepo = $em->getRepository(Participant::class);
        $participant = $participantsRepo->find($id);

        $participant->setActif(false);

        $em->flush();

        $this->addFlash("warning", "Le participant est maintenant inactif");
        return $this->redirectToRoute('liste', ['id' => $id]);
    }


    /**
     * @Route("/gestion/liste/activer/{id}", name = "activer")
     * @param $id
     * @param EntityManagerInterface $em
     * @return RedirectResponse
     */
    public function activer($id, EntityManagerInterface $em)
    {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $participantsRepo = $em->getRepository(Participant::class);
        $participant = $participantsRepo->find($id);

        $participant->setActif(true);

        $em->flush();

        return $this->redirectToRoute('liste', ['id' => $id]);
    }


    /**
     * @Route("/gestion/participant/supprimer/{id}", name = "supprimer")
     * @param $id
     * @param EntityManagerInterface $em
     * @return RedirectResponse
     */
    public function supprimerParticipant($id, EntityManagerInterface $em)
    {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $participant = $em->getRepository(Participant::class)->find($id);

        if ($participant->getSortie()->count() == 0 && $participant->getOrganisateurSortie()->count() == 0) {
            $em->remove($participant);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé');
            return $this->redirectToRoute('liste');
        } else {
            $this->addFlash('warning', 'l\'utilisateur a encore des activités en cours');
        }

        return $this->redirectToRoute('liste');

    }
}

