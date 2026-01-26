<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\ActionCardType;

#[ORM\Entity(repositoryClass: CardRepository::class)]
class ActionCard extends AbstractCard
{
    #[ORM\Column(length: 255)]
    private ?ActionCardType $type = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $count = null;

    public function getType(): ?ActionCardType
    {
        return $this->type;
    }

    public function setType(ActionCardType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): static
    {
        $this->count = $count;

        return $this;
    }

}
