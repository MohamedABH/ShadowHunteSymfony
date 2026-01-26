<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\Colors;

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

    #[ORM\ManyToOne(inversedBy: 'players')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\ManyToOne(inversedBy: 'players')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'players')]
    private ?Position $position = null;

    #[ORM\ManyToOne]
    private ?CharacterCard $characterCard = null;

    /**
     * @var Collection<int, Location>
     */
    #[ORM\OneToMany(targetEntity: Location::class, mappedBy: 'player')]
    private Collection $cards;

    #[ORM\Column]
    private ?Colors $color = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $playingOrder = null;

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

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getCharacterCard(): ?CharacterCard
    {
        return $this->characterCard;
    }

    public function setCharacterCard(?CharacterCard $characterCard): static
    {
        $this->characterCard = $characterCard;

        return $this;
    }

    /**
     * @return Collection<int, Location>
     */
    public function getCardss(): Collection
    {
        return $this->cards;
    }

    public function addCard(Location $card): static
    {
        if (!$this->cards->contains($card)) {
            $this->cards->add($card);
            $card->setPlayer($this);
        }

        return $this;
    }

    public function removeCard(Location $card): static
    {
        if ($this->cards->removeElement($card)) {
            // set the owning side to null (unless already changed)
            if ($card->getPlayer() === $this) {
                $card->setPlayer(null);
            }
        }

        return $this;
    }

    public function getColor(): ?Colors
    {
        return $this->color;
    }

    public function setColor(Colors $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getPlayingOrder(): ?int
    {
        return $this->playingOrder;
    }

    public function setPlayingOrder(?int $playingOrder): static
    {
        $this->playingOrder = $playingOrder;

        return $this;
    }
}
