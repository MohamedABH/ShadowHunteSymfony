<?php

enum Location: string
{
    case HAND = 'hand';
    case DECK = 'deck';
    case DISCARD_PILE = 'discard_pile';
    case IN_PLAY = 'in_play';
}