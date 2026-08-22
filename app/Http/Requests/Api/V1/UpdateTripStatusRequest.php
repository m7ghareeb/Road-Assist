<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TripStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTripStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('trip');

        return $trip->driver_id !== null
            && (int) $this->input('driver_id') === $trip->driver_id;
    }

    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'exists:drivers,id'],
            'status'    => ['required', Rule::enum(TripStatus::class)],
        ];
    }
}
