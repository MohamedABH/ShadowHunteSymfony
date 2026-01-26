<?php

namespace App\Enum;

enum GameStatus: string
{
    case PENDING = 'pending';
    case ONGOING = 'ongoing';
    case COMPLETED = 'completed';
}