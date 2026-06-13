<?php

namespace App\Form;

use App\DTO\ContactDTO;
use Symfony\Component\Form\AbstractType;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom *',
                'required' => true,
                'empty_data' => ''
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email *',
                'required' => true,
                'empty_data' => ''
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de demande *',
                'required' => true,
                'choices' => [
                    'Acquisition' => 'acquisition',
                    'Demande de renseignement' => 'renseignement',
                    'Message / remarque' => 'message',
                ],
                'placeholder' => 'Sélectionnez le type de demande'
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message *',
                'required' => true,
                'attr' => ['rows' => 5],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => ['class' => 'btn btn-secondary btn-sm']
            ])
            ->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3([
                    'message' => 'Captcha invalide',
                    'score_threshold' => 0.3,
                ]),
                'action_name' => 'contact',
                'locale' => 'fr',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactDTO::class,
        ]);
    }
}
