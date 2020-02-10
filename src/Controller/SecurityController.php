<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/security", name="security")
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function registration(UserPasswordEncoderInterface $passwordEncoder,EntityManagerInterface $entityManager, Request $request)
    {
        $participant = new  Participant();
        $form = $this->createForm(ParticipantType::class, $participant);

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $hash = $passwordEncoder->encodePassword($participant, $participant->getPassword());

            $participant->setPassword($hash);
            $participant->setRoles(['ROLE_USER']);
            $participant->setActif(true);

            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash("success", "Inscription OK !");

            return $this->redirectToRoute('app_login');

        }

        return $this->render('security/index.html.twig', [
            'form'=> $form->createView()
        ]);

    }

    /**
     * @Route("/login", name="app_login")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }
}