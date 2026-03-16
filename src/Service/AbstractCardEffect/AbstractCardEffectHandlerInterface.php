<?php

namespace App\Service\AbstractCardEffect;

use App\Entity\AbstractCard;
use App\Entity\Game;
use App\Entity\Player;

interface AbstractCardEffectHandlerInterface
{
    public function execute(AbstractCard $card, Player $player, Game $game, array $context = []): AbstractCardEffectResult;

    public function supports(AbstractCard $card): bool;

    public function getRequiredContext(): array;
}
