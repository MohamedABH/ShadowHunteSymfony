<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class GameCreateRequestDto
{
    #[Assert\NotBlank]
    public ?string $name = null;
}
