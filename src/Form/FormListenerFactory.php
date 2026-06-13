<?php
namespace App\Form;

use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Event\SubmitEvent;
use App\Repository\TableauRepository;
use Symfony\Component\Form\FormEvent;

class FormListenerFactory {

public function __construct(
    private SluggerInterface $slugger,
    private TableauRepository $tableauRepository)
{

}

    public function autoSlug(string $field): callable
{
    return function (FormEvent $event) use ($field) {
        $data = $event->getData();
        $form = $event->getForm();

        // On ne fait rien si les données ne sont pas valides ou si un slug est déjà défini
        if (!is_array($data) || empty($data[$field]) || !empty($data['slug'])) {
            return;
        }

        $originalSlug = strtolower($this->slugger->slug($data[$field]));
        $slug = $originalSlug;
        $suffix = 1;

        // Récupère l'entité liée au formulaire (utile si on édite une entité déjà existante)
        $existingEntity = $form->getData();
        $currentId = is_object($existingEntity) && method_exists($existingEntity, 'getId')
            ? $existingEntity->getId()
            : null;

        // Boucle tant qu'un slug identique existe dans la base
        while ($existing = $this->tableauRepository->findOneBy(['slug' => $slug])) {
            if ($currentId && $existing->getId() === $currentId) {
                break; // C'est la même entité, pas besoin de modifier
            }
            $slug = $originalSlug . '-' . $suffix++;
        }

        $data['slug'] = $slug;
        $event->setData($data);
    };
}

    public function timestamps(): callable
    {
        return function (SubmitEvent $event) {
            $data= $event->getData();
            $data->setUpdatedAt(new \DateTimeImmutable());
            if (!$data->getId()) {
                $data->setCreatedAt(new \DateTimeImmutable());
            }
        };
    }
}