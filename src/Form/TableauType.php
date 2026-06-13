<?php

namespace App\Form;


use App\Entity\Tableau;
use App\Entity\Category;
use App\Form\FormListenerFactory;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TableauType extends AbstractType
{
    public function __construct(private FormListenerFactory $listenerFactory) {}

    public function buildForm(FormBuilderInterface $builder,  array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => true,
            ])

            ->add('commentaires', TextareaType::class, [
                'required' => false,
                'label' => 'Commentaires de l\'artiste',
            ])

            ->add('numero_tableau', IntegerType::class, [
                'label' => 'Numéro de taleau',
                'required' => false,
            ])

            ->add('date', DateType::class, [
                'widget' => 'single_text', // Utilise un champ de type <input type="date">
                'html5' => true,          // Active le support HTML5
                'attr' => ['class' => 'form-control'], // Ajoute une classe pour le styling
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'label' => 'Catégorie',
                'choice_label' => 'name',
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'rows' => 4,  // Définit le nombre de lignes visibles dans le textarea

                ],
                'label' => 'Description',  // Vous pouvez aussi personnaliser ou masquer le label
            ])
            ->add('keywords',  TextareaType::class, [
                'attr' => [
                    'rows' => 3,  // Définit le nombre de lignes visibles dans le textarea

                ],
                'label' => 'Mots-Clés',  // Vous pouvez aussi personnaliser ou masquer le label
            ])
            ->add('ariaLabel', TextareaType::class, [
                'label' => 'Aria Label',
                'attr' => [
                    'rows' => 3,  // Définit le nombre de lignes visibles dans le textarea
                    'placeholder' => 'Saisir le texte pour l’aria-label',
                ],
            ])
            ->add('points', ChoiceType::class, [
                'choices' => ['choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                    '6' => 6,
                    '8' => 8,
                    '10' => 10,
                    '12' => 12,
                    '15' => 15,
                    '20' => 20,
                    '25' => 25,
                    '30' => 30,
                    '40' => 40,
                    '50' => 50,
                    '60' => 60,
                    '80' => 80,
                    '100' => 100,
                    '120' => 120,
                ],],
                'label' => 'Points',
                'mapped' => false, // Ne pas mapper à l'entité
                'required' => false,
            ])
            ->add('format', ChoiceType::class, [
                'choices' => [
                    'F (figure)' => 'F',
                    'P (paysage)' => 'P',
                    'M (marine)' => 'M',
                ],
                'label' => 'Format',
                'mapped' => false, // Ne pas mapper à l'entité
                'required' => false,
            ])
            ->add('orientation', ChoiceType::class, [
                'choices' => [
                    'Paysage' => 'paysage',
                    'Portrait' => 'portrait',
                    'Carré' => 'carré',
                ],
                'expanded' => false, // pour des boutons radio
                'multiple' => false,
            ])
            ->add('dimension', TextType::class, [
                'label' => 'Dimension',
                'mapped' => true,
                'required' => false,
            ])
            ->add('forsale', CheckboxType::class, [
                'label'    => 'Disponible à la vente',  // Vous pouvez personnaliser le label ici
                'required' => false,  // La case à cocher n'est pas requise pour la validation du formulaire

            ])
            ->add('image', TextType::class, [
                'attr' => ['readonly' => true],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image (JPG, PNG)',
                'required' => false, // Initialement non requis
                'constraints' => [
                    new Assert\Image([
                        'maxSize' => '30M',
                        'maxSizeMessage' => 'Le fichier est trod lourd. Taille maximale autorisée : {{ limit }} {{ suffix }}.'
                    ])
                ]
            ])
            ->add('slug', TextType::class, [
                'required' => false
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer'
            ])

            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {

                $data = $event->getData();

                $points = $data['points'] ?? null;
                $format = $data['format'] ?? null;

                if ($points && $format) {
                    $data['dimension'] = $points . $format;
                    $event->setData($data);
                }
            })
            ->addEventListener(FormEvents::PRE_SUBMIT, $this->listenerFactory->autoslug('title'))
            ->addEventListener(FormEvents::SUBMIT, $this->listenerFactory->timestamps())
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();
                $tableau = $event->getData();  // Obtenez l'entité liée au formulaire

                // Vérifiez si l'image est vide et ajustez le champ de formulaire en conséquence
                if (empty($tableau->getImage())) {
                    $form->add('imageFile', FileType::class, [
                        'label' => 'Image (JPG, PNG)',
                        'required' => true,
                        'constraints' => [
                            new Assert\Image([
                                'maxSize' => '30M',
                                'maxSizeMessage' => 'Le fichier est trop lourd. Taille maximale autorisée : {{ limit }} {{ suffix }}.',
                            ])
                        ]
                    ]);
                }
            });
    }

    public function autoSlug(PreSubmitEvent $event): void
    {
        $data = $event->getData();
        if (!isset($data['title']) || empty($data['title'])) {
            return; // ✅ Évite l'erreur si le titre est absent
        }
        $slugger = new AsciiSlugger();
        $data['slug'] = strtolower($slugger->slug($data['title']));
        $event->setData($data);
    }

    public function Timestamps(SubmitEvent $event): void
    {
        $data = $event->getData();
        if (!($data instanceof Tableau)) {
            return;
        }

        $data->setUpdatedAt(new \DateTimeImmutable());
        if (!$data->getId()) {
            $data->setCreatedAt(new \DateTimeImmutable());
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tableau::class,
            'csrf_protection' => true,        // protection CSRF activée
            'csrf_field_name' => '_token',    // nom du champ du token
            'csrf_token_id'   => 'tableau_item', // identifiant unique pour ce formulaire
        ]);
    }
}
