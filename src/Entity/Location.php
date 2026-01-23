<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\ManyToOne(inversedBy: 'locations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Card $card = null;

    /**
     * @var Collection<int, Player>
     */
    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: 'location')]
    private Collection $holder;

    #[ORM\OneToOne(inversedBy: 'location', cascade: ['persist', 'remove'])]
    private ?Place $place = null;

    public function __construct()
    {
        $this->holder = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;

        return $this;
    }

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): static
    {
        $this->card = $card;

        return $this;
    }

    /**
     * @return Collection<int, Player>
     */
    public function getHolder(): Collection
    {
        return $this->holder;
    }

    public function addHolder(Player $holder): static
    {
        if (!$this->holder->contains($holder)) {
            $this->holder->add($holder);
            $holder->setLocation($this);
        }

        return $this;
    }

    public function removeHolder(Player $holder): static
    {
        if ($this->holder->removeElement($holder)) {
            // set the owning side to null (unless already changed)
            if ($holder->getLocation() === $this) {
                $holder->setLocation(null);
            }
        }

        return $this;
    }

    public function getPlace(): ?Place
    {
        return $this->place;
    }

    public function setPlace(?Place $place): static
    {
        $this->place = $place;

        return $this;
    }

}
