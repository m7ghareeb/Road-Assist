<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'location' => [
                'latitude'  => $this->latitude ?? null,
                'longitude' => $this->longitude ?? null,
            ],
            'status'   => $this->status,
            'distance' => $this->when(isset($this->distance), fn () => $this->formatDistance($this->distance)),
        ];
    }

    private function formatDistance(float $distance): string
    {
        if ($distance < 1) {
            return round($distance * 1000) . ' m';
        }

        return number_format($distance, 2) . ' km';
    }
}
