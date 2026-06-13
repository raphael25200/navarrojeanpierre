<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ContactDTO
{
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 200,
        minMessage: 'Le nom doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    public string $name = '';

    #[Assert\NotBlank(message: 'L\'adresse e-mail est obligatoire')]
    #[Assert\Email(message: 'Veuillez saisir une adresse e-mail valide')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Le message est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 2000,
        minMessage: 'Le message doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères'
    )]
    public string $message = '';

    #[Assert\NotBlank(message: 'Le type de demande est obligatoire')]
    #[Assert\Choice(
        choices: ['acquisition', 'renseignement', 'message'],
        message: 'Veuillez sélectionner un type de demande valide'
    )]
    public string $type = '';
}
