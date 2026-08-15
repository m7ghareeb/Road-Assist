<?php

namespace App\Enums;

enum TripOfferStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Closed = 'closed';
}
