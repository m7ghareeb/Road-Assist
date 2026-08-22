<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyDriverOfOffer implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        public int $tripId,
        public int $driverId,
    ) {}

    public function handle(): void
    {
        // Mocked per the brief. Production: FCM/APNs push (plus HMS, given the
        // Huawei build) and a broadcast on the driver's private channel.
        Log::info('Offer notification sent', [
            'trip_id'   => $this->tripId,
            'driver_id' => $this->driverId,
        ]);
    }
}
