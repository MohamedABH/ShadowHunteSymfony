<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Enum\CharacterCardType;

#[ORM\Entity(repositoryClass: CardRepository::class)]
class CharacterCard extends AbstractCard
{
    #[ORM\Column(length: 255)]
    private ?CharacterCardType $type = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $maxDamage = null;

    public function getType(): ?CharacterCardType
    {
        return $this->type;
    }

    public function setType(CharacterCardType $type): static
    {
        $this->type = $type;

        return $this;
    }

        public function getMaxDamage(): ?int
    {
        return $this->maxDamage;
    }

    public function setMaxDamage(int $maxDamage): static
    {
        $this->maxDamage = $maxDamage;

        return $this;
    }

}