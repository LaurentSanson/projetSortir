<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class VilleController extends AbstractController
{
    /**
     * @Route("/ville", name="ville")
     */
    public function index(EntityManagerInterface $em)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $villeRepository = $em->getRepository(Ville::class);
        $villes = $villeRepository->findAll();
        return $this->render('ville/index.html.twig', [
            'villes' => $villes,
        ]);
    }

    /**
     * @Route("/nouvelleVille", name="nouvelleVille")
     */
    public function nouvelleVille(EntityManagerInterface $em, Request $request)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $ville = new Ville();
        $villeForm = $this->createForm(VilleType::class, $ville);
        $villeForm->handleRequest($request);


        if ($villeForm->isSubmitted()) {
            $ville->setNom($_POST['ville']['nom']);
            $em->persist($ville);
            $em->flush();
            $this->addFlash("success", "Votre ville a bien été ajoutée !");
            return $this->redirectToRoute('nouveauLieu', array('id' => $ville->getId()));
        }
        return $this->render('ville/ajouter.html.twig', [
            'villeForm' => $villeForm->createView()
        ]);
    }

    /**
     * @Route("/detailVille/{id}", name="detailVille")
     */
    public function detail($id)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $ville = $this->getDoctrine()->getManager()
            ->getRepository(Ville::class)
            ->find($id);


        return $this->render("ville/detail.html.twig",
            [
                "ville" => $ville
            ]
        );
    }

    /**
     *
     * @Route("/effacer/{id}", name="effacer")
     */
    public function delete(EntityManagerInterface $em, $id)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $ville = $this->getDoctrine()->getManager()
            ->getRepository(Ville::class)
            ->find($id);

        $em->remove($ville);
        $em->flush();
        $this->addFlash("success", "Votre ville a bien été effacée !");
        $villeRepository = $em->getRepository(Ville::class);
        $villes = $villeRepository->findAll();

        return $this->render("ville/index.html.twig",
            [
                "villes" => $villes
            ]
        );
    }
}
