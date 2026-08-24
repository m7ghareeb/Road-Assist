<?php

namespace App\Models;

use App\Enums\DriverStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'latitude', 'longitude', 'status'])]
class Driver extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'latitude'  => 'float',
            'longitude' => 'float',
            'status'    => DriverStatus::class,
        ];
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
