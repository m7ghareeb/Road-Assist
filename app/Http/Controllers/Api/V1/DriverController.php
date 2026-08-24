<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Drivers\FindNearbyDriversAction;
use App\Actions\Drivers\UpdateDriverLocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\NearbyDriversRequest;
use App\Http\Requests\Api\V1\UpdateDriverLocationRequest;
use App\Http\Resources\V1\DriverResource;
use App\Models\Driver;

class DriverController extends Controller
{
    public function nearby(NearbyDriversRequest $request)
    {
        $drivers = FindNearbyDriversAction::handle(
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
            $request->validated('radius_km') ?? FindNearbyDriversAction::DEFAULT_RADIUS_KM,
            $request->validated('limit') ?? FindNearbyDriversAction::DEFAULT_LIMIT,
        );

        return DriverResource::collection($drivers);
    }

    public function updateLocation(UpdateDriverLocationRequest $request, Driver $driver)
    {
        $driver = UpdateDriverLocationAction::handle(
            $driver,
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
        );

        return DriverResource::make($driver);
    }
}
