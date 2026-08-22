<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'status' => $this->status,
            'type'   => $this->type,
            'pickup' => [
                'latitude'  => (float) $this->pickup_latitude,
                'longitude' => (float) $this->pickup_longitude,
            ],
            'customer' => $this->whenLoaded('customer', fn () => [
                'id'   => $this->customer?->id,
                'name' => $this->customer?->name,
            ]),
            'driver' => $this->whenLoaded('driver', fn () => [
                'id'   => $this->driver?->id,
                'name' => $this->driver?->name,
            ]),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
