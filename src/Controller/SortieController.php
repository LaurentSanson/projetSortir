<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\SortieType;
use Doctrine\ORM\EntityManagerInterface;
use http\Client\Curl\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SortieController extends AbstractController
{
    /**
     * @Route("/sortie", name="sortie")
     */
    public function index(EntityManagerInterface $em)
    {
        $sortieRepository = $em->getRepository(Sortie::class);
        $sorties = $sortieRepository->findAll();
        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
        ]);
    }

    /**
     * @Route("/nouvelleSortie", name="nouvelleSortie")
     */
    public function nouvelleSortie(EntityManagerInterface $em, Request $request)
    {
        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($request);
        if ($sortieForm->isSubmitted() && $sortieForm->isValid()){
            $user = $this->getUser();
            $sortie->setOrganisateur($user);
            $site = $this->getUser();;
            $sortie->setSite($site);
            $em->persist($sortie);
            $em->flush();
            $this->addFlash("success", "Votre sortie a bien été ajoutée !");
            return $this->redirectToRoute('detailSortie', array('id'=>$sortie->getId()));
        }
        return $this->render('sortie/ajouter.html.twig', [
            'sortieForm' => $sortieForm->createView()
        ]);
    }

    /**
     * @Route("/detailSortie/{id}", name="detailSortie")
     */
    public function detail($id)
    {
        $sortie = $this->getDoctrine()->getManager()
            ->getRepository(Sortie::class)
            ->find($id);


        return $this->render("sortie/detail.html.twig",
            [
                "sortie" => $sortie
            ]
        );
    }

    /**
     *
     * @Route("/effacer/{id}", name="effacer")
     */
    public function delete(EntityManagerInterface $em, $id)
    {
        $sortie = $this->getDoctrine()->getManager()
            ->getRepository(Sortie::class)
            ->find($id);

        $em->remove($sortie);
        $em->flush();
        $this->addFlash("success", "Votre sortie a bien été effacé !");
        $sortieRepository = $em->getRepository(Sortie::class);
        $sorties = $sortieRepository->findAll();

        return $this->render("sortie/index.html.twig",
            [
                "sorties" => $sorties
            ]
        );
    }
}
