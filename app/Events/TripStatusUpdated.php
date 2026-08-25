<?php

namespace App\Events;

use App\Enums\TripStatus;
use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripStatusUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Trip $trip, public TripStatus $fromStatus) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("trip.{$this->trip->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trip.status.updated';
    }

    public function broadcastWith(): array
    {
        return array_filter([
            'trip_id'     => $this->trip->id,
            'from_status' => $this->fromStatus->value,
            'to_status'   => $this->trip->status->value,
            'updated_at'  => $this->trip->updated_at->toIso8601String(),

            'driver' => $this->trip->driver ? [
                'id'   => $this->trip->driver->id,
                'name' => $this->trip->driver->name,
            ] : null,
        ], fn ($value) => filled($value));
    }
}
