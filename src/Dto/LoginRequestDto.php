<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class LoginRequestDto
{
    // Either username or email should be provided
    public ?string $username = null;

    public ?string $email = null;

    #[Assert\NotBlank]
    public ?string $password = null;
}
