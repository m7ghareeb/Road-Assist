<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TowType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('idempotency-key'),
        ]);
    }

    public function rules(): array
    {
        return [
            'idempotency_key'  => ['required', 'uuid'], // simulate as a unique key for idempotency
            'customer_id'      => ['required', 'exists:customers,id'], // simulate as a auth user
            'pickup_latitude'  => ['required', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['required', 'numeric', 'between:-180,180'],
            'type'             => ['required', Rule::enum(TowType::class)],
        ];
    }
}
