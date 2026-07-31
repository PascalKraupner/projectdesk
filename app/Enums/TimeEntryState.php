<?php

namespace App\Enums;

enum TimeEntryState: string
{
    case Running = 'running';
    case Paused = 'paused';
    case Completed = 'completed';
}
