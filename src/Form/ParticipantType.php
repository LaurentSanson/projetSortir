<?php

namespace App\Form;

use App\Entity\Participant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

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
            ->add('mail', EmailType::class)
//            ->add('actif')
//            ->add('sortie')
            ->add('site')
            ->add('photo', FileType::class, [
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' =>'1024k',
                    ])
                ]
            ])
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
