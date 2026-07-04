<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Tableau;
use App\Entity\Category;
use App\Form\TableauType;
use App\Form\CategoryType;
use App\Repository\AvisRepository;
use App\Repository\TableauRepository;
use App\Service\ImageAnalyzerService;
use App\Service\ImageUploaderService;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/admin', name: 'admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{

    #[Route('/tableaux', name: '.tableau.index')]
    public function indexTableau(TableauRepository $repository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $tableaux = $repository->paginateTableaux($page);

        // Sécurité : vérifie qu'il y a des tableaux
        if (!$tableaux) {
            $this->addFlash('info', 'Aucun tableau trouvé.');
            return $this->redirectToRoute('admin.tableau.create');
        }

        return $this->render('admin/tableau/index.html.twig', [
            'tableaux' => $tableaux,

        ]);
    }

    #[Route('/tableaux/{id}/edit', name: '.tableau.edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    public function editTableau(Tableau $tableau, Request $request, EntityManagerInterface $em, ImageUploaderService $imageUploader)
    {
        // Vérifie que le tableau existe
        if (!$tableau) {
            $this->addFlash('error', 'Tableau non trouvé.');
            return $this->redirectToRoute('admin.tableau.index');
        }

        $form = $this->createForm(TableauType::class, $tableau);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($tableau);
            $em->flush();
            $imageUploader->uploadImage($tableau); // Utilise le service ici
            $em->flush();
            $this->addFlash('success', 'Les informations du tableau ont bien été modifiées');
            return $this->redirectToRoute('admin.tableau.index');
        }
        return $this->render('admin/tableau/edit.html.twig', [
            'tableau' => $tableau,
            'form'  => $form,
        ]);
    }

    #[Route('/tableaux/create', name: '.tableau.create')]
    public function createTableau(TableauRepository $repository, Request $request, EntityManagerInterface $em, ImageUploaderService $imageUploader)
    {
        $tableau = new Tableau();
        $form = $this->createForm(TableauType::class, $tableau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($tableau);
            $em->flush();
            $imageUploader->uploadImage($tableau); // Utilise le service ici
            $em->flush();
            $this->addFlash('success', 'Tableau créé avec succès');

            // Calcule la dernière page
            $total = $repository->countAll();
            $limit = 20; // Le nombre d’éléments par page
            $lastPage = (int) ceil($total / $limit);

            return $this->redirectToRoute('admin.tableau.index', [
                'page' => 1
            ]);
        }

        return $this->render('admin/tableau/create.html.twig', [
            'form'  => $form
        ]);
    }

    #[Route('/tableaux/{id}/delete', name: '.tableau.delete', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    public function removeTableau(Request $request, Tableau $tableau, EntityManagerInterface $em): Response
    {
        // Vérifie que le tableau existe
        if (!$tableau) {
            $this->addFlash('error', 'Tableau non trouvé.');
            return $this->redirectToRoute('admin.tableau.index');
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('delete' . $tableau->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide. Suppression annulée.');
            return $this->redirectToRoute('admin.tableau.index');
        }

        // Supprime les fichiers uniquement s'ils existent
        $filesystem = new Filesystem();
        $thumbnailPath = $this->getParameter('kernel.project_dir') . '/public/images/thumbnail/' . $tableau->getThumbnail();
        $previewPath   = $this->getParameter('kernel.project_dir') . '/public/images/preview/' . $tableau->getPreview();

        if ($filesystem->exists($thumbnailPath)) {
            $filesystem->remove($thumbnailPath);
        }

        if ($filesystem->exists($previewPath)) {
            $filesystem->remove($previewPath);
        }

        // Supprime l'entité
        $em->remove($tableau);
        $em->flush();

        $this->addFlash('success', 'Le tableau a bien été supprimé');

        return $this->redirectToRoute('admin.tableau.index');
    }

    #[Route('/tableaux/{id}/generate-ai', name: 'tableau_generate_ai', methods: ['POST'])]
    public function generateAI(Tableau $tableau, ImageAnalyzerService $analyzer): JsonResponse
    {
        // Vérifie que le tableau a bien une image
        $imageName = $tableau->getImage();
        if (!$imageName) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Aucune image définie pour ce tableau.'
            ]);
        }

        // Construire l'URL complète de l'image
        $imageUrl = 'https://navarrojeanpierre.com/images/images_sources/' . $imageName;

        // Log temporaire côté serveur pour debug
        error_log('Image URL : ' . $imageUrl);

        try {
            // Appel du service IA
            $result = $analyzer->analyzeImage($imageUrl);

            // Log réponse brute IA
            error_log('Réponse IA : ' . print_r($result, true));

            // Retourne toujours le JSON, même si vide
            return new JsonResponse($result);
        } catch (\Throwable $e) {
            // Capture toutes les exceptions et renvoie en JSON
            return new JsonResponse([
                'error' => true,
                'message' => 'Erreur lors de l’analyse IA : ' . $e->getMessage()
            ]);
        }
    }



    ////////////GESTION CATEGORY

    #[Route('/categories', name: '.category.index')]
    public function indexCategory(CategoryRepository $repository)
    {
        return $this->render('admin/category/index.html.twig', [
            'categories' => $repository->findAll()
        ]);
    }

    #[Route('/categories/create', name: '.category.create')]
    public function createCategory(Request $request, EntityManagerInterface $em)
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'La catégorie a bien été créée');
            return $this->redirectToRoute('admin.category.index');
        }
        return $this->render('admin/category/create.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/categories/{id}', requirements: ['id' => Requirement::DIGITS], name: '.category.edit', methods: ['GET', 'POST'])]
    public function editCategory(Category $category, Request $request, EntityManagerInterface $em)
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'La catégorie a bien été modifiée');
            return $this->redirectToRoute('admin.category.index');
        }
        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'form' => $form
        ]);
    }

    #[Route(
        '/categories/{id}/delete',
        name: '.category.delete',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['POST']
    )]
    public function removeCategory(
        Request $request,
        Category $category,
        EntityManagerInterface $em
    ): Response {
        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide. Suppression annulée.');
            return $this->redirectToRoute('admin.category.index');
        }

        // Vérifie si la catégorie contient encore des tableaux
        if (!$category->getTableaux()->isEmpty()) {
            $this->addFlash(
                'error',
                'Impossible de supprimer cette catégorie : elle contient encore des tableaux.'
            );
            return $this->redirectToRoute('admin.category.index');
        }

        // Suppression de la catégorie
        $em->remove($category);
        $em->flush();

        $this->addFlash('success', 'La catégorie a bien été supprimée.');

        return $this->redirectToRoute('admin.category.index');
    }



    /////////////GESTION SLIDER

    // --- 1. Affichage du slider ---
    #[Route('/slider', name: '.slider.index', methods: ['GET'])]
    public function index(TableauRepository $tableauRepository): Response
    {
        $tableaux = $tableauRepository->findAll();

        return $this->render('admin/slider/index.html.twig', [
            'tableaux' => $tableaux
        ]);
    }

    // --- 2. Sauvegarde AJAX du slider ---
    #[Route('slider/save', name: '.slider.save', methods: ['POST'])]
    public function saveSlider(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['sliderItems'])) {
            return $this->json(['success' => false, 'message' => 'Aucune donnée reçue']);
        }

        // Vérifie le nombre d'images cochées
        $sliderCount = 0;
        foreach ($data['sliderItems'] as $item) {
            if ($item['isInSlider']) $sliderCount++;
        }

        if ($sliderCount > 5) {
            return $this->json(['success' => false, 'message' => 'Vous ne pouvez sélectionner que 5 images.']);
        }

        // Enregistre les changements
        try {
            foreach ($data['sliderItems'] as $item) {
                $tableau = $em->getRepository(Tableau::class)->find($item['id']);
                if (!$tableau) continue;

                $tableau->setIsInSlider($item['isInSlider']);
                $tableau->setCustomTitle($item['customTitle']);
                $em->persist($tableau);
            }

            $em->flush();
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    //////////////////COMMENTS

    #[Route('/comments', name: '.comments.index')]
    public function listAllComments(AvisRepository $avisRepository): Response
    {
        // Récupérer tous les commentaires, triés par date décroissante
        $allAvis = $avisRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/comments/index.html.twig', [
            'allAvis' => $allAvis,
        ]);
    }

    // --- Publier un commentaire ---
    #[Route('/comments/publish/{id}', name: '.comments.publish', methods: ['POST'])]
    public function publishComment(Avis $avis, EntityManagerInterface $em): Response
    {
        $avis->setIsPublished(true);
        $em->flush();

        $this->addFlash('success', 'Le commentaire a été publié.');
        return $this->redirectToRoute('admin.comments.index');
    }

    // --- Rejeter un commentaire ---
    #[Route('/comments/reject/{id}', name: '.comments.reject', methods: ['POST'])]
    public function rejectComment(Avis $avis, EntityManagerInterface $em): Response
    {
        $em->remove($avis);
        $em->flush();

        $this->addFlash('success', 'Le commentaire a été supprimé.');
        return $this->redirectToRoute('admin.comments.index');
    }
    #[Route('/tableaux/batch-ai', name: '.tableau.batch_ai', methods: ['GET'])]
    public function batchAiForm(): Response
    {
        return $this->render('admin/tableau/batch.html.twig');
    }

    #[Route('/tableaux/save-ai/{numero}', name: '.tableau.save_ai', methods: ['POST'])]
    public function saveAI(int $numero, TableauRepository $repository, ImageAnalyzerService $analyzer, EntityManagerInterface $em): JsonResponse
    {
        $tableau = $repository->findOneBy(['numero_tableau' => $numero]);

        if (!$tableau) {
            return new JsonResponse(['error' => true, 'message' => 'Œuvre non trouvée.'], 404);
        }

        $imageName = $tableau->getImage();
        if (!$imageName) {
            return new JsonResponse(['error' => true, 'message' => 'Pas d\'image.']);
        }

        $imageUrl = 'https://www.navarrojeanpierre.com/images/images_sources/' . $imageName;

        try {
            $result = $analyzer->analyzeImage($imageUrl, $tableau->getTitle());

            if (!empty($result['error'])) {
                return new JsonResponse(['error' => true, 'message' => $result['error']]);
            }

            if (!empty($result['description'])) {
                $tableau->setDescription($result['description']);
            }
            if (!empty($result['keywords'])) {
                $tableau->setKeywords(implode(', ', $result['keywords']));
            }
            if (!empty($result['aria_label'])) {
                $tableau->setAriaLabel($result['aria_label']);
            }

            $em->flush();

            return new JsonResponse([
                'success' => true,
                'numero' => $numero,
                'title' => $tableau->getTitle(),
                'description' => $result['description'],
                'keywords' => $result['keywords'],
                'aria_label' => $result['aria_label'],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => true, 'message' => $e->getMessage()]);
        }
    }
    #[Route('/tableaux/batch-images', name: '.tableau.batch_images', methods: ['GET'])]
    public function batchImagesForm(): Response
    {
        return $this->render('admin/tableau/batch_images.html.twig');
    }

    #[Route('/tableaux/generate-images/{numero}', name: '.tableau.generate_images', methods: ['POST'])]
    public function generateImages(int $numero, TableauRepository $repository, ImageUploaderService $imageUploader, EntityManagerInterface $em): JsonResponse
    {
        $tableau = $repository->findOneBy(['numero_tableau' => $numero]);

        if (!$tableau) {
            return new JsonResponse(['error' => true, 'message' => 'Œuvre non trouvée.'], 404);
        }

        try {
            $success = $imageUploader->regenerateVariantsFromSource($tableau);

            if (!$success) {
                return new JsonResponse(['error' => true, 'message' => 'Image source introuvable.']);
            }

            $em->flush();

            return new JsonResponse([
                'success' => true,
                'numero' => $numero,
                'title' => $tableau->getTitle(),
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
