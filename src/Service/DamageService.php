<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Player;
use App\Enum\GameStatus;
use Doctrine\ORM\EntityManagerInterface;

class DamageService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function applyDamage(Player $player, int $amount): array
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Damage amount must be non-negative.');
        }

        $before = (int) ($player->getCurrentDamage() ?? 0);
        $after = $before + $amount;
        $player->setCurrentDamage($after);

        return [
            'before' => $before,
            'after' => $after,
            'delta' => $amount,
            'isKnockedOut' => $this->isKnockedOut($player),
        ];
    }

    public function heal(Player $player, int $amount): array
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Heal amount must be non-negative.');
        }

        $before = (int) ($player->getCurrentDamage() ?? 0);
        $after = max(0, $before - $amount);
        $player->setCurrentDamage($after);

        return [
            'before' => $before,
            'after' => $after,
            'healed' => $before - $after,
            'isKnockedOut' => $this->isKnockedOut($player),
        ];
    }

    public function isKnockedOut(Player $player): bool
    {
        $maxDamage = $player->getCharacterCard()?->getMaxDamage();
        if ($maxDamage === null) {
            return false;
        }

        return ((int) ($player->getCurrentDamage() ?? 0)) >= $maxDamage;
    }

    public function enforceKnockout(Player $player): bool
    {
        if (!$this->isKnockedOut($player)) {
            return false;
        }

        $game = $player->getGame();
        if (!$game) {
            return true;
        }

        $wasOwner = $game->getOwner()?->getId() === $player->getUser()?->getId();

        foreach ($player->getCards() as $location) {
            $location->setPlayer(null);
            $this->entityManager->persist($location);
        }

        $game->removePlayer($player);
        $this->entityManager->remove($player);

        $remainingCount = $game->getPlayers()->count();

        if ($remainingCount === 0) {
            $game->setStatus(GameStatus::ABORTED);
        } elseif ($remainingCount === 1) {
            $game->setStatus(GameStatus::COMPLETED);
        }

        if ($wasOwner && $remainingCount > 0) {
            $newOwnerPlayer = $game->getPlayers()->first();
            if ($newOwnerPlayer instanceof Player && $newOwnerPlayer->getUser()) {
                $game->setOwner($newOwnerPlayer->getUser());
            }
        }

        $this->entityManager->persist($game);

        return true;
    }
}
