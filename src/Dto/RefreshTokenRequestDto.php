<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RefreshTokenRequestDto
{
    #[Assert\NotBlank]
    public ?string $refresh_token = null;
}
