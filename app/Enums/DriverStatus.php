<?php

namespace App\Enums;

enum DriverStatus: string
{
    case Available = 'available';
    case Busy = 'busy';
    case Offline = 'offline';
}
