<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Form\SortieType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieController extends AbstractController
{
    /**
     * @Route("/sortieTriee", name="sortieTriee")
     * @Route("/sortie", name="sortie")
     * @param EntityManagerInterface $em
     * @param Request $request
     * @return Response
     */
    public function index(EntityManagerInterface $em, Request $request)
    {
        $user = $this->getUser();
        $site = $request->get('site');
        $search = $request->get('search');
        $checkbox1 = $request->get('checkbox1');
        $checkbox2 = $request->get('checkbox2');
        $checkbox3 = $request->get('checkbox3');
        $checkbox4 = $request->get('checkbox4');
        $dateDebut = $request->get('dateDebut');
        $dateFin = $request->get('dateFin');

        $sorties = $em->getRepository(Sortie::class)->search($site, $search, $dateDebut, $dateFin, $checkbox1, $checkbox2, $checkbox3, $checkbox4, $user);
        $sites = $em->getRepository(Site::class)->findAll();

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
            'sites' => $sites,
            'user' => $user,
        ]);
    }

    /**
     * @Route("/nouvelleSortie", name="nouvelleSortie")
     * @param EntityManagerInterface $em
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function nouvelleSortie(EntityManagerInterface $em, Request $request)
    {
        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            $user = $this->getUser();
            $site = $this->getUser()->getSite();
            $etat = $em->getRepository(Etat::class)->find(2);
            $sortie->setOrganisateur($user);
            $sortie->setSite($site);
            $sortie->setEtat($etat);
            $em->persist($sortie);
            $em->flush();
            $this->addFlash("success", "Votre sortie a bien été ajoutée !");
            return $this->redirectToRoute('detailSortie', array('id' => $sortie->getId()));
        }
        return $this->render('sortie/ajouter.html.twig', [
            'sortieForm' => $sortieForm->createView(),
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

        //renvoyer sur page annulée si sortie annulée
        $etatId = $sortie->getEtat()->getId();
        if($etatId == 6){
            return $this->render("sortie/detailAnnulee.html.twig", [ 'sortie'=> $sortie]);
        }

        //affichage des options participant sur la page détail sortie
        $miseEnForme = 0;

        if ($sortie->getDateCloture() < new \DateTime('now')) {
            $miseEnForme = 1;
        } else {
            foreach ($sortie->getParticipants() as $participant) {
                if ($participant->getId() == $this->getUser()->getId()) {
                    $miseEnForme = 2;
                }
            }
            if ($sortie->getNbInscriptionsMax() == 0 && $miseEnForme != 2) {
                $miseEnForme = 1;
            }
        }

        return $this->render("sortie/detail.html.twig",
            [
                "sortie" => $sortie,
                "miseEnForme" => $miseEnForme,
            ]
        );
    }

    /**
     *
     * @Route("/effacer/{id}", name="effacerSortie")
     * @param EntityManagerInterface $em
     * @param $id
     * @return Response
     */
    public
    function delete(EntityManagerInterface $em, $id)
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

    /**
     * @Route("/inscription/{id}", name="inscription")
     * @param $id
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function inscriptionSortie($id, EntityManagerInterface $entityManager)
    {

        $user = $this->getUser();

        $repo = $entityManager->getRepository(Sortie::class);
        $sortie = $repo->find($id);

        $nbParticipant = $sortie->getParticipants()->count();

        if ($sortie->getNbInscriptionsMax() == $nbParticipant) {
            return $this->redirectToRoute('detailSortie', ['id' => $id]);
        } else {
//            $sortie->setNbInscriptionsMax($sortie->getNbInscriptionsMax() - 1);

            $user->addSortie($sortie);

            $entityManager->flush();

            $this->addFlash("success", "inscription OK");

            return $this->redirectToRoute('detailSortie', ['id' => $id]);
        }
    }

    /**
     * @Route("/desinscription/{id}", name="desinscription")
     * @param $id
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function desinscription($id, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        $repo = $entityManager->getRepository(Sortie::class);
        $sortie = $repo->find($id);

//        $nbParticipant = $sortie->getParticipants()->count();

//        $sortie->setNbInscriptionsMax($sortie->getNbInscriptionsMax() + 1);

        $user->removeSortie($sortie);

        $entityManager->flush();

        $this->addFlash("success", "désinscription OK");

        return $this->redirectToRoute('detailSortie', ['id' => $id]);

    }


    /**
     * @Route("/modification/{id}", name="modification")
     */
    public function modifierSortie($id, EntityManagerInterface $em, Request $request)
    {
        $repo = $em->getRepository(Sortie::class);
        $sortie = $repo->find($id);

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){

            $em->flush();

            $this->addFlash("success", "modification effectuée");
            return $this->redirectToRoute('detailSortie', ['id' => $id]);
        }

        return $this->render('sortie/modifier.html.twig',[
            'sortieForm'=> $form->createView(),
            'sortie'=> $sortie,

        ]);
    }

    /**
     * @Route("/annulation/{id}", name="annulation")
     */
    public function annulerSortie($id, EntityManagerInterface $em, Request $request)
    {
        $repo = $em->getRepository(Sortie::class);
        $sortie = $repo->find($id);

        $etatRepo = $em->getRepository(Etat::class);
        $etat = $etatRepo->find(6);


        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()){
            //on change l'état à "annulée"
            $sortie->setEtat($etat);
            //on retire les participants inscrits
            $participants = $sortie->getParticipants();
            foreach($participants as $participant){
                $sortie->removeParticipant($participant);
            }

            $em->flush();
            $this->addFlash("success", "sortie annulée");
            return $this->redirectToRoute('sortie', ['id'=> $id]);
        }

        return $this->render('sortie/annuler.html.twig', [
            'sortieForm'=> $sortieForm->createView(),
            'sortie'=>$sortie,
        ]);

    }

}
