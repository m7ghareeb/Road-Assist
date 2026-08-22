<?php

namespace App\Exceptions;

use App\Enums\TripStatus;
use Exception;

final class InvalidTripTransitionException extends Exception
{
    public function __construct(
        private TripStatus $from,
        private TripStatus $to,
    ) {
        parent::__construct();
    }

    public function render($request)
    {
        return response()->json([
            'message' => "Cannot move a trip from {$this->from->value} to {$this->to->value}.",
        ], 422);
    }
}
