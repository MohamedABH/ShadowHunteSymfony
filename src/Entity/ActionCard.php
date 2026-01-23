<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CardRepository::class)]
class ActionCard extends AbstractCard
{
    #[ORM\Column(length: 255)]
    private ?ActionCardType $type = null;

    public function getType(): ?ActionCardType
    {
        return $this->type;
    }

    public function setType(ActionCardType $type): static
    {
        $this->type = $type;

        return $this;
    }

}
