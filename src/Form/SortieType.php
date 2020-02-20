<?php

namespace App\Form;

use App\Entity\Groupe;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Repository\GroupeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use http\Client\Curl\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $options['user'];

        $builder
            ->add('nom')
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text'])
            ->add('duree')
            ->add('dateCloture', DateType::class, [
                'widget' => 'single_text'])
            ->add('nbInscriptionsMax')
            ->add('descriptionInfos')
            ->add('groupe', EntityType::class,[
                'class' => Groupe::class,
                'query_builder' => function(EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('g')
                    ->andWhere('g.Createur = :user')
                    ->setParameter('user', $user);
                    return $qb;
                },
                'choice_label' => 'nom',
            ])
            ->add('lieu');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
            'user' => new Participant(),
        ]);
        $resolver ->setAllowedTypes('user', Participant::class);
    }

}
