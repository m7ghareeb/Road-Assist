<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Trips\AcceptTrip;
use App\Actions\Trips\CreateTrip;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AcceptTripRequest;
use App\Http\Requests\Api\V1\StoreTripRequest;
use App\Http\Resources\V1\TripResource;
use App\Models\Driver;
use App\Models\Trip;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function store(StoreTripRequest $request)
    {
        $trip = CreateTrip::handle($request->validated());

        return TripResource::make($trip);
    }

    public function show($tripId)
    {
        // Logic to retrieve trip details by ID
    }

    public function accept(AcceptTripRequest $request, Trip $trip)
    {
        $driver = Driver::findOrFail($request->validated('driver_id'));

        $trip = AcceptTrip::handle($trip, $driver);

        return TripResource::make($trip);
    }

    public function updateStatus(Request $request, $tripId)
    {
        // Logic to update the status of a trip
    }
}
