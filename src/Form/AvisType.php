<?php
// src/Form/AvisType.php
namespace App\Form;

use App\Entity\Avis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AvisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Votre pseudo',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Entrez votre pseudo',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre email',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Entrez votre email',
                ],
            ])
            ->add('showEmail', CheckboxType::class, [
                'label' => 'Afficher mon email publiquement',
                'required' => false,
                'data' => true, // coche la case par défaut
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Votre commentaire',
                'required' => true,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Rédigez votre avis...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Avis::class,
        ]);
    }
}
