<?php

namespace App\Entity;

use App\Repository\ClueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClueRepository::class)]
class Clue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?bool $resolution = null;

    #[ORM\ManyToOne(inversedBy: 'sentClues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Player $sender = null;

    #[ORM\ManyToOne(inversedBy: 'receivedClues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Player $receiver = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Card $card = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isResolution(): ?bool
    {
        return $this->resolution;
    }

    public function setResolution(?bool $resolution): static
    {
        $this->resolution = $resolution;

        return $this;
    }

    public function getSender(): ?Player
    {
        return $this->sender;
    }

    public function setSender(?Player $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getReceiver(): ?Player
    {
        return $this->receiver;
    }

    public function setReceiver(?Player $receiver): static
    {
        $this->receiver = $receiver;

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
}
