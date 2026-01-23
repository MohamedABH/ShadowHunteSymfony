<?php

namespace App\Entity;

use App\Repository\PlaceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlaceRepository::class)]
class Place
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(mappedBy: 'place', cascade: ['persist', 'remove'])]
    private ?Location $location = null;

    /**
     * @var Collection<int, Player>
     */
    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: 'place')]
    private Collection $players;

    public function __construct()
    {
        $this->players = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        // unset the owning side of the relation if necessary
        if ($location === null && $this->location !== null) {
            $this->location->setPlace(null);
        }

        // set the owning side of the relation if necessary
        if ($location !== null && $location->getPlace() !== $this) {
            $location->setPlace($this);
        }

        $this->location = $location;

        return $this;
    }

    /**
     * @return Collection<int, Player>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(Player $player): static
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
            $player->setPlace($this);
        }

        return $this;
    }

    public function removePlayer(Player $player): static
    {
        if ($this->players->removeElement($player)) {
            // set the owning side to null (unless already changed)
            if ($player->getPlace() === $this) {
                $player->setPlace(null);
            }
        }

        return $this;
    }
}
