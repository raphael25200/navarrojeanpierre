<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Repository\CategoryRepository;

class AppExtension extends AbstractExtension
{
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getCategories', [$this, 'getCategories']),
        ];
    }

    public function getCategories(): array
    {
        return $this->categoryRepository->findAll();
    }
}
