<?php


namespace App\Security;


use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{


    /**
     * @inheritDoc
     */
    public function checkPreAuth(UserInterface $user)
    {
        // TODO: Implement checkPreAuth() method.
    }

    /**
     * @inheritDoc
     */
    public function checkPostAuth(UserInterface $user)
    {

        if ($user->getActif() == FALSE) {
            throw new AccountExpiredException('Votre compte a été désactivé, merci de contacter votre administrateur');
        }
    }
}