<?php

namespace App\Controller;


use App\Entity\Groupe;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\GroupeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GroupeController extends AbstractController
{
    /**
     * @Route("/groupe", name="groupe")
     */
    public function index(EntityManagerInterface $em)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $groupeRepository = $em->getRepository(Groupe::class);
        $groupes = $groupeRepository->findAll();
        return $this->render('groupe/index.html.twig', [
            'groupes' => $groupes,
        ]);
    }

    /**
     * @Route("/nouveauGroupe", name="nouveauGroupe")
     * @param EntityManagerInterface $em
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function nouveauGroupe(EntityManagerInterface $em, Request $request)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $user = $this->getUser();
        $groupe = new Groupe();
        $groupeForm = $this->createForm(GroupeType::class, $groupe);
        $groupeForm->handleRequest($request);
        if ($groupeForm->isSubmitted() && $groupeForm->isValid()) {
            // On set le créateur au User
            $groupe->setCreateur($user);
            // On ajoute automatiquement le User au groupe
            $groupe->addParticipant($user);
            $em->persist($groupe);
            $em->flush();
            $this->addFlash("success", "Votre groupe a bien été ajouté !");
            return $this->redirectToRoute('detailGroupe', array('id' => $groupe->getId()));
        }
        return $this->render('groupe/ajouter.html.twig', [
            'groupeForm' => $groupeForm->createView()
        ]);
    }

    /**
     * @Route("/detailGroupe/{id}", name="detailGroupe")
     */
    public function detail($id)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $groupe = $this->getDoctrine()->getManager()
            ->getRepository(Groupe::class)
            ->find($id);

        return $this->render("groupe/detail.html.twig",
            [
                "groupe" => $groupe
            ]
        );
    }

    /**
     *
     * @Route("/effacerGroupe/{id}", name="effacerGroupe")
     */
    public function effacer(EntityManagerInterface $em, $id)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $groupe =$em->getRepository(Groupe::class)->find($id);
        // On vient récupérer les participants inscrits au groupe puis on les supprime du groupe
        $participantsGroupe = $groupe->getParticipants();
        foreach ($participantsGroupe as $participant) {
            $groupe->removeParticipant($participant);
        }
        // On vient récupérer les sorties rattachées au groupe puis on les supprime la relation
        // La sortie est donc publique
        $sorties = $em->getRepository(Sortie::class)->findByGroupe($groupe);
        foreach ($sorties as $sortie){
            $groupe->removeSortie($sortie);
        }

        $em->remove($groupe);
        $em->flush();
        $this->addFlash("success", "Votre groupe a bien été effacé !");
        $groupeRepository = $em->getRepository(Groupe::class);
        $groupes = $groupeRepository->findAll();

        return $this->render("groupe/index.html.twig",
            [
                "groupes" => $groupes
            ]
        );
    }

    /**
     * @Route("/ajouterParticipant/{id}", name="ajouterParticipant")
     * @Route("/chercheParticipant/{id}", name="chercheParticipant", methods={"GET"})
     * @param $id
     * @return Response

     */
    public function ajouterParticipant(EntityManagerInterface $em, Request $request, $id=0)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        // On cherche le groupe
        $groupeRepository = $em->getRepository(Groupe::class);
        $groupe = $groupeRepository->find($id);
        // On liste les participants selon les caractères de la barre de recherche
        $search = $request->get('search');
        $participantRepository = $em->getRepository(Participant::class);
        $participants = $participantRepository->search($search);

        return $this->render('groupe/ajouterParticipant.html.twig', [
            'groupe' => $groupe,
            'participants' => $participants
        ]);
    }

    /**
     * @Route("/inscriptionGroupe/{idGroupe}/{idUser}", name="inscriptionGroupe")
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function inscriptionGroupe($idGroupe, $idUser, EntityManagerInterface $entityManager)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $repo = $entityManager->getRepository(Groupe::class);
        $groupe = $repo->find($idGroupe);
        $user = $entityManager->getRepository(Participant::class)->find($idUser);
        $user->addGroupe($groupe);
        $entityManager->flush();
        $this->addFlash("success", "inscription OK");

        return $this->redirectToRoute('detailGroupe', ['id' => $idGroupe]);

    }
}
