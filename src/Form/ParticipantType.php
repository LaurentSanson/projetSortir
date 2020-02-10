<?php

namespace App\Form;

use App\Entity\Participant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('pseudo')
//            ->add('roles')
//            ->add('password', PasswordType::class)
            ->add('newPassword', PasswordType::class, [
                'label'=>'Password',
                'mapped'=> false
            ])
            ->add('newPassword2', PasswordType::class, [
                'label'=>'Confirmation',
                'mapped'=> false
            ])
            ->add('nom')
            ->add('prenom')
            ->add('telephone')
            ->add('mail')
//            ->add('actif')
//            ->add('sortie')
            ->add('site')
            ->add('Enregistrer', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
