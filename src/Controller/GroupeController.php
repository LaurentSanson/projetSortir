<?php

namespace App\Controller;


use App\Entity\Groupe;
use App\Entity\Participant;
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
        $groupe = new Groupe();
        $groupeForm = $this->createForm(GroupeType::class, $groupe);
        $groupeForm->handleRequest($request);
        if ($groupeForm->isSubmitted() && $groupeForm->isValid()) {
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
     * @Route("/effacer/{id}", name="effacer")
     */
    public function effacer(EntityManagerInterface $em, $id)
    {
        $groupe = $this->getDoctrine()->getManager()
            ->getRepository(Groupe::class)
            ->find($id);

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
        $groupeRepository = $em->getRepository(Groupe::class);
        $groupe = $groupeRepository->find($id);
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
        $repo = $entityManager->getRepository(Groupe::class);
        $groupe = $repo->find($idGroupe);
        $user = $entityManager->getRepository(Participant::class)->find($idUser);
        $user->addGroupe($groupe);
        $entityManager->flush();
        $this->addFlash("success", "inscription OK");

        return $this->redirectToRoute('detailGroupe', ['id' => $idGroupe]);

    }
}
