<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SearchType as SymfonySearchType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', SymfonySearchType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Recherche...',
                    'class' => 'search-input'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchType::class,
            'csrf_protection' => true,
            'method' => 'GET'
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'search_type';
    }
}
