<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class GameJoinRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    public ?int $gameId = null;
}
