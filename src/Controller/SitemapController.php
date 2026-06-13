<?php

namespace App\Controller;

use App\Repository\TableauRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'sitemap', defaults: ['_format' => 'xml'])]
    public function index(
        TableauRepository $tableauRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $tableaux = $tableauRepository->findAll();
        $categories = $categoryRepository->findAll();

        $response = new Response(
            $this->renderView('sitemap/sitemap.xml.twig', [
                'tableaux' => $tableaux,
                'categories' => $categories,
            ]),
            200,
            ['Content-Type' => 'text/xml']
        );

        return $response;
    }
}
