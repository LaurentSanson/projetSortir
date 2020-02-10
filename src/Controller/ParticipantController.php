<?php

namespace App\Controller;

use App\Form\ParticipantType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ParticipantController extends AbstractController
{
    /**
     * @Route("/participant", name="participant")
     */
    public function index()
    {
        return $this->render('participant/index.html.twig', [
            'controller_name' => 'ParticipantController',
        ]);
    }

    /**
     * @Route("/Profil", name="profil")
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function profil(EntityManagerInterface $entityManager, Request $request)
    {

        $participant = $this->getUser();

        return $this->render('participant/profil.html.twig', [
            'participant' => $participant,
        ]);
    }

    /**
     * @Route("/modifierProfil", name="modifierProfil")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param EntityManager $entityManager
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function modifierProfil(Request $request, UserPasswordEncoderInterface $passwordEncoder, EntityManager $entityManager)
    {

        $participant = $this->getUser();

        $form = $this->createForm(ParticipantType::class, $participant);

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $hash = $passwordEncoder->encodePassword($participant, $participant->getPassword());

            $participant->setPassword($hash);

            $entityManager->flush();

            $this->addFlash("success", "Inscription OK !");

            return $this->redirectToRoute('app_login');

        }

        return $this->render('security/index.html.twig', [
            'form'=> $form->createView()
        ]);


    }

}
