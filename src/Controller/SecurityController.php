<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use Doctrine\ORM\EntityManagerInterface;
use Mailjet\Resources;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
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
    public function registration(UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $entityManager, Request $request)
    {
        // Uniquement un user 'ADMIN' peut accéder à cette page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $participant = new  Participant();
        $form = $this->createForm(ParticipantType::class, $participant);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $password = $form->get('newPassword')->getViewData();
            $password2 = $form->get('newPassword2')->getViewData();

            if ($password == $password2) {

                $participant->setPassword($password);

                $hash = $passwordEncoder->encodePassword($participant, $participant->getPassword());

                $participant->setPassword($hash);
                $participant->setRoles(['ROLE_USER']);
                $participant->setActif(true);

                $entityManager->persist($participant);
                $entityManager->flush();

                $this->addFlash("success", "Inscription OK !");

                return $this->redirectToRoute('app_login');

            } else {
                $this->addFlash("alert-danger", "Mot de passe pas identique !");
            }
        }

        return $this->render('security/index.html.twig', [
            'form' => $form->createView()
        ]);


    }

    /**
     * @Route("/login", name="app_login")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public
    function login(AuthenticationUtils $authenticationUtils): Response
    {

//         if ($this->getUser()) {
//             return $this->redirectToRoute('target_path');
//         }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public
    function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    /**
     * @Route("/forgotten_password", name="app_forgotten_password")
     */
    public function forgottenPassword(Request $request, TokenGeneratorInterface $tokenGenerator): Response
    {

        if ($request->isMethod('POST')) {

            $email = $request->request->get('mail');

            $entityManager = $this->getDoctrine()->getManager();
            $user = $entityManager->getRepository(Participant::class)->findByEmail($email);
            /* @var $user Participant */

            if ($user === null) {
                $this->addFlash('danger', 'Email Inconnu');
                return $this->redirectToRoute('app_forgotten_password');
            }
            $token = $tokenGenerator->generateToken();

            try {
                $user->setResetToken($token);
                $entityManager->flush();
            } catch (\Exception $e) {
                $this->addFlash('warning', $e->getMessage());
                return $this->redirectToRoute('app_forgotten_password');
            }

            $url = $this->generateUrl('app_reset_password', array('token' => $token), UrlGeneratorInterface::ABSOLUTE_URL);

            $apikey = '8c03245d267c35ccec9553f2d8493fdd';
            $apisecret = '5e3155380d4127de9cf7c9a34af71e1d';

            $mj = new \Mailjet\Client($apikey, $apisecret, true, ['version' => 'v3.1', 'timeout'=>20]);

            $body = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => "contact@sortir.yj.fr",
                            'Name' => "Me"
                        ],
                        'To' => [
                            [
                                'Email' => $user->getMail(),
                                'Name' => "You"
                            ]
                        ],
                        'Subject' => "Réinitialisation de votre mot de passe",
                        'TextPart' => "Merci de cliquer sur le lien suivant pour réinitialiser votre mot de passe : " . $url,
                    ]
                ]
            ];

            $response = $mj->get(Resources::$Email);
            if ($response->success())
                var_dump($response->getData());
            else
                var_dump($response->getStatus());

            $response = $mj->post(Resources::$Email, ['body'=>$body]);
            $response->success() && var_dump($response->getData());

//            $message = (new \Swift_Message('Réinitialisation de votre mot de passe'))
//                ->setFrom('contact@sortir.yj.fr')
//                ->setTo($user->getMail())
//                ->setBody(
//                    "Merci de cliquer sur le lien suivant pour réinitialiser votre mot de passe : " . $url,
//                    'text/html'
//                );
//
//
//            $mailer->send($message);


            $this->addFlash('notice', 'Mail envoyé');

            return $this->redirectToRoute('main');
        }

        return $this->render('security/forgotten_password.html.twig');
    }

    /**
     * @Route("/reset_password/{token}", name="app_reset_password")
     */
    public function resetPassword(Request $request, string $token, UserPasswordEncoderInterface $passwordEncoder)
    {

        if ($request->isMethod('POST')) {
            $entityManager = $this->getDoctrine()->getManager();

            $user = $entityManager->getRepository(Participant::class)->findOneByResetToken($token);
            /* @var $user User */

            if ($user === null) {
                $this->addFlash('danger', 'Token Inconnu');
                return $this->redirectToRoute('main');
            }

            $user->setResetToken(null);
            $user->setPassword($passwordEncoder->encodePassword($user, $request->request->get('password')));
            $entityManager->flush();

            $this->addFlash('notice', 'Mot de passe mis à jour');

            return $this->redirectToRoute('app_login');
        } else {

            return $this->render('security/reset_password.html.twig', ['token' => $token]);
        }

    }

}
