<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Form\AvisType;
use App\Entity\Tableau;
use App\Data\SearchData;
use App\Form\SearchForm;
use App\Repository\TableauRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TableauController extends AbstractController
{
    #[Route('/oeuvres', name: 'oeuvres.search')]
    public function index(
        TableauRepository $repository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {

        $search = new SearchData();
        $form = $this->createForm(SearchForm::class, $search);
        $form->handleRequest($request);

        $query = $repository->findSearchQuery($search);

        $tableaux = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('oeuvres/index.html.twig', [
            'form' => $form->createView(),
            'tableaux' => $tableaux,
            'results_count' => $tableaux->getTotalItemCount(),
        ]);
    }

    #[Route('/category/{slug}', name: 'oeuvres.category_view', methods: ['GET'])]
    public function view(
        CategoryRepository $categoryRepository,
        string $slug,
        TableauRepository $repository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $category = $categoryRepository->findOneBy(['slug' => $slug]);

        if (!$category) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        $search = new SearchData();
        $search->categories = [$category]; // on force la catégorie

        $form = $this->createForm(SearchForm::class, $search);
        $form->handleRequest($request);

        // toujours forcer la catégorie même après soumission du form
        $search->categories = [$category];

        $query = $repository->findSearchQuery($search);

        $tableaux = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('oeuvres/category_view.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
            'tableaux' => $tableaux,
            'results_count' => $tableaux->getTotalItemCount(),
        ]);
    }

    #[Route('/tableau/{id}/avis', name: 'tableau_avis_partial', methods: ['GET', 'POST'])]
    public function loadComments(
        Tableau $tableau,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $avis = new Avis();
        $avis->setTableau($tableau);
        if ($this->getUser()) {
            $avis->setUser($this->getUser());
        }

        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avis->setIsPublished(false); // ou true si tu publies directement
            $em->persist($avis);
            $em->flush();

            // Envoi email à l’admin si besoin
            $mailer->send(
                (new Email())
                    ->from('no-reply@tonsite.com')
                    ->to('navarroraphael@yahoo.fr')
                    ->subject('Nouveau commentaire soumis')
                    ->html('<p>Nouveau commentaire sur « ' . $tableau->getTitle() . ' ».</p>')
            );

            // Retour JSON pour le widget
            return $this->json([
                'success' => true,
                'message' => 'Votre commentaire a été envoyé avec succès !',
                'comment' => [
                    'pseudo' => $avis->getPseudo() ?: 'Anonyme',
                    'content' => $avis->getContent(),
                    'email' => $avis->getEmail() ?: ''
                ]
            ]);
        }

        // Récupère les avis publiés
        $avisList = $tableau->getAvis()->filter(fn($a) => $a->isPublished());
        if ($request->isXmlHttpRequest()) {
            error_log('--- Requête AJAX ---');
            error_log('Nombre de commentaires : ' . count($avisList));
            error_log('Formulaire créé');

            $html = $this->renderView('partials/_comment_widget.html.twig', [
                'avisList' => $avisList,
                'commentForm' => $this->createForm(AvisType::class, new Avis())->createView(),
                'tableau' => $tableau
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Votre commentaire a été soumis et attend validation.',
                'html' => $html
            ]);
        }

        return $this->render('partials/_comment_widget.html.twig', [
            'avisList' => $avisList,
            'commentForm' => $form->createView(),
            'tableau' => $tableau
        ]);
    }
    #[Route('/oeuvre/{slug}', name: 'oeuvre.show', methods: ['GET'])]
    public function show(
        TableauRepository $repository,
        string $slug
    ): Response {
        $tableau = $repository->findOneBy(['slug' => $slug]);

        if (!$tableau) {
            throw $this->createNotFoundException('Œuvre non trouvée');
        }

        return $this->render('oeuvres/show.html.twig', [
            'tableau' => $tableau,
            'similar' => $repository->findSimilar($tableau, 4),
            'previous' => $repository->findPrevious($tableau),
            'next' => $repository->findNext($tableau),
        ]);
    }
}
