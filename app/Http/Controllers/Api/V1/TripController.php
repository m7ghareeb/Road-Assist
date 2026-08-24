<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Trips\AcceptTripAction;
use App\Actions\Trips\CreateTripAction;
use App\Actions\Trips\UpdateTripStatusAction;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AcceptTripRequest;
use App\Http\Requests\Api\V1\StoreTripRequest;
use App\Http\Requests\Api\V1\UpdateTripStatusRequest;
use App\Http\Resources\V1\TripResource;
use App\Models\Driver;
use App\Models\Trip;

class TripController extends Controller
{
    public function store(StoreTripRequest $request)
    {
        $trip = CreateTripAction::handle($request->validated());

        return TripResource::make($trip)
            ->response()
            ->setStatusCode($trip->wasRecentlyCreated ? 201 : 200); // because the idempotent action may return an existing trip if the same request is made again
    }

    public function show(Trip $trip)
    {
        $trip->load(['driver', 'customer']);

        return TripResource::make($trip);
    }

    public function accept(AcceptTripRequest $request, Trip $trip)
    {
        $driver = Driver::findOrFail($request->validated('driver_id'));

        $trip = AcceptTripAction::handle($trip, $driver);

        return TripResource::make($trip);
    }

    public function updateStatus(UpdateTripStatusRequest $request, Trip $trip)
    {
        $status = TripStatus::from($request->validated('status'));

        $trip = UpdateTripStatusAction::handle($trip, $status);

        return TripResource::make($trip);
    }
}
