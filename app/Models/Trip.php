<?php

namespace App\Models;

use App\Enums\TowType;
use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'driver_id', 'pickup_latitude', 'pickup_longitude', 'type', 'status', 'idempotency_key'])]
class Trip extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'pickup_latitude'  => 'float',
            'pickup_longitude' => 'float',
            'status'           => TripStatus::class,
            'type'             => TowType::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function driverOffers(): HasMany
    {
        return $this->hasMany(TripDriverOffer::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TripEvent::class);
    }
}
