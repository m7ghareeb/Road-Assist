<?php

namespace App\Enums;

enum DriverStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Busy = 'busy';
}
