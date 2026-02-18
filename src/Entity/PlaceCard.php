<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PlaceCard extends AbstractCard
{
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $roll = null;

    /**
     * @return int[]|null
     */
    public function getRoll(): ?array
    {
        return $this->roll;
    }

    /**
     * @param int[] $roll
     */
    public function setRoll(array $roll): static
    {
        $count = count($roll);
        if ($count < 1 || $count > 2) {
            throw new \InvalidArgumentException('Roll must contain 1 or 2 values.');
        }

        $normalized = [];
        foreach ($roll as $value) {
            $intValue = (int) $value;
            if ($intValue < 2 || $intValue > 10) {
                throw new \InvalidArgumentException('Roll values must be between 2 and 10.');
            }
            $normalized[] = $intValue;
        }

        $this->roll = $normalized;

        return $this;
    }
}