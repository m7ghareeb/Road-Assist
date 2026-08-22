<?php

namespace App\Exceptions;

use Exception;

final class TripAlreadyAssignedException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'message' => 'This trip has already been assigned to another driver.',
        ], 409);
    }
}
