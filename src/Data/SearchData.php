<?php

namespace App\Data;

class SearchData
{
    public ?string $q = null;

    public ?array $categories = [];

    public ?int $year = null;

    public ?string $orientation = null; // Modification ici

    public ?bool $forsale = null;

    public ?string $order = 'desc'; // Ajout pour le tri par année

}
