<?php

namespace App\Controller;

use App\Entity\Site;
use App\Form\SiteType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{
    /**
     * @Route("/site", name="site")
     */
    public function index(EntityManagerInterface $em)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $siteRepository = $em->getRepository(Site::class);
        $sites = $siteRepository->findAll();
        return $this->render('site/index.html.twig', [
            'sites' => $sites,
        ]);
    }

    /**
     * @Route("/nouveauSite", name="nouveauSite")
     */
    public function nouveauSite(EntityManagerInterface $em, Request $request)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $site = new Site();
        $siteForm = $this->createForm(SiteType::class, $site);
        $siteForm->handleRequest($request);
        if ($siteForm->isSubmitted() && $siteForm->isValid()){
            $em->persist($site);
            $em->flush();
            $this->addFlash("success", "Votre site a bien été ajouté !");
            return $this->redirectToRoute('detailSite', array('id'=>$site->getId()));
        }
        return $this->render('site/ajouter.html.twig', [
            'siteForm' => $siteForm->createView()
        ]);
    }

    /**
     * @Route("/detailSite/{id}", name="detailSite")
     */
    public function detail($id)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $site = $this->getDoctrine()->getManager()
            ->getRepository(Site::class)
            ->find($id);


        return $this->render("site/detail.html.twig",
            [
                "site" => $site
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
        $site = $this->getDoctrine()->getManager()
            ->getRepository(Site::class)
            ->find($id);

        $em->remove($site);
        $em->flush();
        $this->addFlash("success", "Votre site a bien été effacé !");
        $siteRepository = $em->getRepository(Site::class);
        $sites = $siteRepository->findAll();

        return $this->render("site/index.html.twig",
            [
                "sites" => $sites
            ]
        );
    }

}
