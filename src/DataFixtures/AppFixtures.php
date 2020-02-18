<?php

namespace App\DataFixtures;

use App\Entity\Participant;
use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Faker\Factory;



class AppFixtures extends Fixture
{


    //construction de l'encodeur pour le mot de passe
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }


    public function load(ObjectManager $manager)
    {

        //on crée un faker en langue française
        $faker = Factory::create('fr_FR');

        //on implante 20 personnes
        for ($i = 0; $i < 20; $i++) {
            $participant = new Participant();
            $participant->setPseudo($faker->username);
            //password
            $password = $this->encoder->encodePassword($participant, '123');
            $participant->setPassword($password);
            $participant->setNom($faker->firstName);
            $participant->setPrenom($faker->lastName);
            $participant->setActif(True);
            //$participant->setTelephone($faker->phoneNumber);
            $participant->setMail($faker->companyEmail);
            //$participant->setSite(Site::'Nantes');
            $manager->persist($participant);
        }

        $manager->flush();
    }




}
