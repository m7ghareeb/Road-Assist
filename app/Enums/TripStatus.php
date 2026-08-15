<?php

namespace App\Enums;

enum TripStatus: string
{
    case Searching = 'searching';
    case Assigned = 'assigned';
    case Arrived = 'arrived';
    case Started = 'started';
    case Completed = 'completed';
}
