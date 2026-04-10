<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Planned = 'planned';
    case Question = 'question';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Dropped = 'dropped';
}
