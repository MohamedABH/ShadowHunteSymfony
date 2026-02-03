<?php

namespace App\Entity;

use App\Repository\GameRepository;
use App\Enum\GameStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?GameStatus $status = null;

    /**
     * @var Collection<int, Position>
     */
    #[ORM\OneToMany(targetEntity: Position::class, mappedBy: 'game', orphanRemoval: true)]
    private Collection $positions;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'game', orphanRemoval: true)]
    private Collection $messages;

    /**
     * @var Collection<int, Player>
     */
    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: 'game', orphanRemoval: true)]
    private Collection $players;

    #[ORM\Column]
    private ?int $turn = null;

    #[ORM\ManyToOne(inversedBy: 'ownedGames')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    public function __construct()
    {
        $this->positions = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->players = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStatus(): ?GameStatus
    {
        return $this->status;
    }

    public function setStatus(GameStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, Position>
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    public function addPosition(Position $position): static
    {
        if (!$this->positions->contains($position)) {
            $this->positions->add($position);
            $position->setGame($this);
        }

        return $this;
    }

    public function removePosition(Position $position): static
    {
        if ($this->positions->removeElement($position)) {
            // set the owning side to null (unless already changed)
            if ($position->getGame() === $this) {
                $position->setGame(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setGame($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getGame() === $this) {
                $message->setGame(null);
            }
        }

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
            $player->setGame($this);
        }

        return $this;
    }

    public function removePlayer(Player $player): static
    {
        if ($this->players->removeElement($player)) {
            // set the owning side to null (unless already changed)
            if ($player->getGame() === $this) {
                $player->setGame(null);
            }
        }

        return $this;
    }

    public function getTurn(): ?int
    {
        return $this->turn;
    }

    public function setTurn(int $turn): static
    {
        $this->turn = $turn;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

}
