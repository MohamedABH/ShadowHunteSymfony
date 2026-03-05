<?php

namespace App\Enum;

enum TurnPhase: string
{
    case ROLL = 'roll';
    case MOVE = 'move';
    case PLACE_ABILITY = 'place_ability';
    case ATTACK = 'attack';
    case END = 'end';
}