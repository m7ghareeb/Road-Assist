<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['trip_id', 'from_status', 'to_status', 'actor_type', 'actor_id'])]
class TripEvent extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
