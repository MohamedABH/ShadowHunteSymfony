<?php

namespace App\Enum;

enum LocationEnum: string
{
    case SIGHT_DECK = 'sight_deck';
    case DARK_DECK = 'dark_deck';
    case LIGHT_DECK = 'light_deck';
    case SIGHT_DISCARD = 'sight_discard';
    case DARK_DISCARD = 'dark_discard';
    case LIGHT_DISCARD = 'light_discard';
    case IN_PLAY = 'in_play';
}