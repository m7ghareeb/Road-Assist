<?php

namespace App\Exceptions;

use Exception;

final class DriverNotEligibleToAcceptTripException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'message' => 'You are not eligible to accept this trip.',
        ], 403);
    }
}
