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
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);


        $data = json_decode($request->getContent(), true);
        $ville = new Ville();

//        $request->request->replace(is_array($data) ? $data : array());
        $villeForm = $this->createForm(VilleType::class, $ville);
//        $ville = $serializer->deserialize($data, Ville::class, 'xml');
        $villeForm->handleRequest($request);


        if ($villeForm->isSubmitted() && $villeForm->isValid()) {
            $ville->setNom($data["nom"]);
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
