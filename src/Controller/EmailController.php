<?php

namespace App\Controller;

use App\Form\MotDePasseOublieType;
use App\Form\ResetPasswordType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class EmailController extends AbstractController
{
    /**
     * @Route("/motDePasseOublie", name="motDePasseOublie")
     */
    public function sendEmail(MailerInterface $mailer)
    {
        $form = $this->createForm(MotDePasseOublieType::class);
        $userEmail = $form->get('email')->getViewData();
        $email = (new Email())
            ->from('laurent.sanson29@gmail.com')
            ->to($userEmail)
            ->subject('Réinitialisez votre mot de passe')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');

        /** @var Symfony\Component\Mailer\SentMessage $sentEmail */
        $sentEmail = $mailer->send($email);
        // $messageId = $sentEmail->getMessageId();

        // ...
    }
}
