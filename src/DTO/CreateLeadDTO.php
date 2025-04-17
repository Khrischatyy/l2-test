<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateLeadDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $lastName;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\+?[1-9]\d{1,14}$/')]
    public string $phone;

    #[Assert\NotBlank]
    #[Assert\Date]
    public string $dateOfBirth;

    public array $additionalData = [];
} 