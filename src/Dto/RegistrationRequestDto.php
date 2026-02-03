<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public ?string $password = null;

    #[Assert\NotBlank]
    public ?string $username = null;
}
