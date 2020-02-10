<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Form\LieuType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LieuController extends AbstractController
{
    /**
     * @Route("/lieu", name="lieu")
     */
    public function index(EntityManagerInterface $em)
    {
        $lieuRepository = $em->getRepository(Lieu::class);
        $lieux = $lieuRepository->findAll();
        return $this->render('lieu/index.html.twig', [
            'lieux' => $lieux,
        ]);
    }

    /**
     * @Route("/nouveauLieu", name="nouveauLieu")
     */
    public function nouvelleSortie(EntityManagerInterface $em, Request $request)
    {
        $lieu = new Lieu();
        $lieuForm = $this->createForm(LieuType::class, $lieu);
        $lieuForm->handleRequest($request);
        if ($lieuForm->isSubmitted() && $lieuForm->isValid()){
            $em->persist($lieu);
            $em->flush();
            $this->addFlash("success", "Votre lieu a bien été ajouté !");
            return $this->redirectToRoute('nouvelleSortie', array('id'=>$lieu->getId()));
        }
        return $this->render('lieu/ajouter.html.twig', [
            'lieuForm' => $lieuForm->createView()
        ]);
    }

    /**
     * @Route("/detailLieu/{id}", name="detailLieu")
     */
    public function detail($id)
    {
        $lieu = $this->getDoctrine()->getManager()
            ->getRepository(Lieu::class)
            ->find($id);


        return $this->render("lieu/detail.html.twig",
            [
                "lieu" => $lieu
            ]
        );
    }

    /**
     *
     * @Route("/effacer/{id}", name="effacer")
     */
    public function effacer(EntityManagerInterface $em, $id)
    {
        $lieu = $this->getDoctrine()->getManager()
            ->getRepository(Lieu::class)
            ->find($id);

        $em->remove($lieu);
        $em->flush();
        $this->addFlash("success", "Votre lieu a bien été effacé !");
        $lieuRepository = $em->getRepository(Lieu::class);
        $lieux = $lieuRepository->findAll();

        return $this->render("lieu/index.html.twig",
            [
                "lieux" => $lieux
            ]
        );
    }
}
