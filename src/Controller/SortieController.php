<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Groupe;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Form\AnnulerType;
use App\Form\SortieType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
     * @throws Exception
     */
    public function index(EntityManagerInterface $em, Request $request)
    {


        $dateDuJour = new \DateTime('now');

        $repo = $em->getRepository(Sortie::class);
        $sorties = $repo->findBy([
            'etat' => [1, 2]
        ]);

        $repo = $em->getRepository(Etat::class);
        $etatCloturee = $repo->find(3);
        $etatEnCours = $repo->find(4);
        $etatPassee = $repo->find(5);

        foreach ($sorties as $sortie) {

            $dateFin = clone $sortie->getDateDebut();
            $dure = $sortie->getDuree();

            $interval = 'PT' . $dure . 'M';
            $dateFin = $dateFin->add(new \DateInterval($interval));

            if ($sortie->getDateCloture() > $dateDuJour && $sortie->getDateDebut() < $dateDuJour) {
                $sortie->setEtat($etatCloturee);
                $em->flush();
            } elseif ($dateDuJour > $sortie->getDateDebut() && $dateDuJour < $dateFin) {
                $sortie->setEtat($etatEnCours);
                $em->flush();
            } elseif ($dateDuJour > $dateFin) {
                $sortie->setEtat($etatPassee);
                $em->flush();
            }
        }

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
        if ($checkbox2 && $checkbox3) {
            $this->addFlash('danger', 'Vous ne pouvez pas sélectionner les sorties auxquelles vous êtes inscrit et non inscrit...');
        }

        if (!$user) {
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }


        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
            'sites' => $sites,
            'user' => $user
        ]);

    }

    /**
     * @Route("/nouvelleSortie", name="nouvelleSortie")
     * @param EntityManagerInterface $em
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function nouvelleSortie(EntityManagerInterface $em, Request $request)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }

        $createur = $this->getUser();
        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieType::class, $sortie, array('user' => $createur));
        $sortieForm->handleRequest($request);

        $groupeRepository = $em->getRepository(Groupe::class);
        $groupes = $groupeRepository->findBy(['Createur' => $createur]);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {

            $user = $this->getUser();
            $site = $this->getUser()->getSite();


            if ( !in_array('sortieCheck', $_POST)){
                $sortie->setGroupe(null);
            }

            if (isset($_POST['enregistrer'])) {
                $etat = $em->getRepository(Etat::class)->find(1);
                $sortie->setEtat($etat);
            } elseif (isset($_POST['publier'])) {
                $etat = $em->getRepository(Etat::class)->find(2);
                $sortie->setEtat($etat);
            }
            $sortie->setOrganisateur($user);
            $sortie->setSite($site);
            $dateDuJour = new \DateTime('now');
            if ($sortie->getDateDebut() < $dateDuJour) {
                $this->addFlash('danger', "La date de l'évènement doit être supérieure à la date du jour");
            } elseif ($sortie->getDateCloture() > $sortie->getDateDebut()) {
                $this->addFlash('danger', 'La date de clôture ne peut pas être supérieure à la date de sortie');
            } else {
                $em->persist($sortie);
                $em->flush();
                $this->addFlash("success", "Votre sortie a bien été ajoutée !");

                return $this->redirectToRoute('sortie');
            }

        }


        return $this->render('sortie/ajouter.html.twig', [
            'sortieForm' => $sortieForm->createView(),
            'groupes' => $groupes
        ]);
    }


    /**
     * @Route("/publier/{id}", name="publier")
     * @param $id
     * @param EntityManagerInterface $em
     * @return RedirectResponse
     */
    public function publierSortie($id, EntityManagerInterface $em)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $repo = $em->getRepository(Sortie::class);
        $sortie = $repo->find($id);


        $etat = $em->getRepository(Etat::class)->find(2);
        $sortie->setEtat($etat);

        $em->flush();

        $this->addFlash("success", "sortie publiée!");
        return $this->redirectToRoute('detailSortie', ['id' => $id]);

    }

    /**
     * @Route("/detailSortie/{id}", name="detailSortie")
     * @param $id
     * @return Response
     * @throws Exception
     */
    public function detail($id)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $sortie = $this->getDoctrine()->getManager()
            ->getRepository(Sortie::class)
            ->find($id);

        //renvoyer sur page annulée si sortie annulée
        $etatId = $sortie->getEtat()->getId();
        if ($etatId == 6) {
            return $this->render("sortie/detailAnnulee.html.twig", ['sortie' => $sortie]);
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
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
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
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
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
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
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
     * @param $id
     * @param EntityManagerInterface $em
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function modifierSortie($id, EntityManagerInterface $em, Request $request)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $repo = $em->getRepository(Sortie::class);
        $sortie = $repo->find($id);

        $form = $this->createForm(SortieType::class, $sortie, array('user' => $this->getUser()));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            $this->addFlash("success", "modification effectuée");
            return $this->redirectToRoute('detailSortie', ['id' => $id]);
        }

        return $this->render('sortie/modifier.html.twig', [
            'sortieForm' => $form->createView(),
            'sortie' => $sortie,

        ]);
    }

    /**
     * @Route("/annulation/{id}", name="annulation")
     */
    public function annulerSortie($id, EntityManagerInterface $em, Request $request)
    {
        if (!$this->getUser()){
            $this->addFlash('danger', "Vous ne pouvez pas accéder à cette page si vous n'êtes pas connecté");
            return $this->redirectToRoute('main');
        }
        $repo = $em->getRepository(Sortie::class);
        $sortie = $repo->find($id);

        $sortieForm = $this->createForm(AnnulerType::class, $sortie);
        $sortieForm->handleRequest($request);

        $etatRepo = $em->getRepository(Etat::class);
        $etat = $etatRepo->find(6);


        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {

            //on change l'état à "annulée"
            $sortie->setEtat($etat);
            //on retire les participants inscrits
            $participants = $sortie->getParticipants();
            foreach ($participants as $participant) {
                $sortie->removeParticipant($participant);
            }

            $em->flush();
            $this->addFlash("success", "sortie annulée");
            return $this->redirectToRoute('sortie', ['id' => $id]);
        }

        return $this->render('sortie/annuler.html.twig', [
            'sortieForm' => $sortieForm->createView(),
            'sortie' => $sortie,
        ]);

    }

}
