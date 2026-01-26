<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PlaceCard extends AbstractCard
{
    #[ORM\Column(length: 255)]
    private ?string $roll = null;

    public function getRoll(): ?string
    {
        return $this->roll;
    }

    public function setRoll(string $roll): static
    {
        $this->roll = $roll;

        return $this;
    }
}