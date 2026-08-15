<?php

namespace App\Models;

use App\Enums\TripOfferStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['trip_id', 'driver_id', 'status', 'offered_at'])]
class TripDriverOffer extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status'     => TripOfferStatus::class,
            'offered_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
