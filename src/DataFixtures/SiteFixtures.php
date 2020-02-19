<?php

namespace App\DataFixtures;

use App\Entity\Participant;
use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SiteFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {

        //on crée un faker en langue française
        $faker = Factory::create('fr_FR');

        //on implante 20 personnes
        for ($i = 0; $i < 20; $i++) {
            $site = new Site();
            $site->setNom($faker->city);

            $this->addReference('site_'.$i, $site);
            $manager->persist($site);
        }

        $manager->flush();
    }
}
