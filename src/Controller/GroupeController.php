<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Entity\Participant;
use App\Form\GroupeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
     */
    public function nouveauGroupe(EntityManagerInterface $em, Request $request)
    {
        $groupe = new Groupe();
        $groupeForm = $this->createForm(GroupeType::class, $groupe);
        $groupeForm->handleRequest($request);
        if ($groupeForm->isSubmitted() && $groupeForm->isValid()){
            $em->persist($groupe);
            $em->flush();
            $this->addFlash("success", "Votre groupe a bien été ajouté !");
            return $this->redirectToRoute('detailGroupe', array('id'=>$groupe->getId()));
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
     * @Route("/chercheParticipant", name="chercheParticipant")
     * @param $id
     */
    public function ajouterParticipant(EntityManagerInterface $em,  Request $request, $id){
        $groupeRepository = $em->getRepository(Groupe::class);
        $groupe = $groupeRepository->find($id);
        foreach ($groupe->getParticipants() as $participant){
            $participant->getId();
        }
        $search = $request->get('search');
        $participantRepository = $em->getRepository(Participant::class);
        $participants = $participantRepository->search($search);
        return $this->render('groupe/ajouterParticipant.html.twig', [
            'participants' => $participants,
            'groupe' => $groupe
        ]);
    }
}
