<?php

namespace App\Form;

use App\Data\SearchData;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface as FormFormBuilderInterface;

class SearchForm extends AbstractType
{
    public function buildForm(FormFormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('q', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Rechercher']
            ])
            ->add('categories', EntityType::class, [
                'label' => false,
                'required' => false,
                'class' => Category::class,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('orientation', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' => [
                    'Tout' => null,
                    'Paysage' => 'paysage',
                    'Portrait' => 'portrait',
                    'Carré' => 'carré',
                ],
                'expanded' => true,
                'multiple' => false,
                'placeholder' => false,
            ])
            ->add('year', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' => $this->generateYears(1946),
                'placeholder' => 'Toutes',
            ])
            ->add('forsale', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
            ])
            ->add('order', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' => [
                    'Plus récent au plus ancien' => 'desc',
                    'Plus ancien au plus récent' => 'asc',
                ],
                'expanded' => true,
                'multiple' => false,
                'placeholder' => false,
            ]);
    }

    private function generateYears(int $startYear): array
    {
        $currentYear = (int) date('Y');
        $years = [];
        for ($year = $currentYear; $year >= $startYear; $year--) {
            $years[$year] = $year;
        }
        return $years;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchData::class,
            'method' => 'GET',
            'csrf_protection' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'search_form';
    }
}
