<?php

namespace App\DataFixtures;

use App\Entity\Participant;
use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture implements DependentFixtureInterface
{


    //construction de l'encodeur pour le mot de passe
    private $encoder;
    private $em;

    public function __construct(UserPasswordEncoderInterface $encoder, EntityManagerInterface $em)
    {
        $this->encoder = $encoder;
        $this->em = $em;
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
            $participant->setRoles(['ROLE_USER']);
            //$participant->setTelephone($faker->phoneNumber);
            $participant->setMail($faker->companyEmail);
            $participant->setSite($this->getReference('site_'.$i));
            $manager->persist($participant);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return array(
            SiteFixtures::class,
        );
    }




}
