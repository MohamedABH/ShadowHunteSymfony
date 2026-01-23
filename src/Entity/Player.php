<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $currentDamage = null;

    #[ORM\Column]
    private ?bool $revealed = null;

    /**
     * @var Collection<int, Clue>
     */
    #[ORM\OneToMany(targetEntity: Clue::class, mappedBy: 'sender', orphanRemoval: true)]
    private Collection $sentClues;

    /**
     * @var Collection<int, Clue>
     */
    #[ORM\OneToMany(targetEntity: Clue::class, mappedBy: 'receiver', orphanRemoval: true)]
    private Collection $receivedClues;

    #[ORM\ManyToOne(inversedBy: 'holder')]
    private ?Location $location = null;

    #[ORM\ManyToOne(inversedBy: 'players')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\ManyToOne(inversedBy: 'players')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'players')]
    private ?Place $place = null;

    public function __construct()
    {
        $this->sentClues = new ArrayCollection();
        $this->receivedClues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCurrentDamage(): ?int
    {
        return $this->currentDamage;
    }

    public function setCurrentDamage(int $currentDamage): static
    {
        $this->currentDamage = $currentDamage;

        return $this;
    }

    public function isRevealed(): ?bool
    {
        return $this->revealed;
    }

    public function setRevealed(bool $revealed): static
    {
        $this->revealed = $revealed;

        return $this;
    }

    /**
     * @return Collection<int, Clue>
     */
    public function getSentClues(): Collection
    {
        return $this->sentClues;
    }

    public function addSentClue(Clue $sentClue): static
    {
        if (!$this->sentClues->contains($sentClue)) {
            $this->sentClues->add($sentClue);
            $sentClue->setSender($this);
        }

        return $this;
    }

    public function removeSentClue(Clue $sentClue): static
    {
        if ($this->sentClues->removeElement($sentClue)) {
            // set the owning side to null (unless already changed)
            if ($sentClue->getSender() === $this) {
                $sentClue->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Clue>
     */
    public function getReceivedClues(): Collection
    {
        return $this->receivedClues;
    }

    public function addReceivedClue(Clue $receivedClue): static
    {
        if (!$this->receivedClues->contains($receivedClue)) {
            $this->receivedClues->add($receivedClue);
            $receivedClue->setReceiver($this);
        }

        return $this;
    }

    public function removeReceivedClue(Clue $receivedClue): static
    {
        if ($this->receivedClues->removeElement($receivedClue)) {
            // set the owning side to null (unless already changed)
            if ($receivedClue->getReceiver() === $this) {
                $receivedClue->setReceiver(null);
            }
        }

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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
