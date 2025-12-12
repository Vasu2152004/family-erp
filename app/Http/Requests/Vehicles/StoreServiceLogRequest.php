<?php

declare(strict_types=1);

namespace App\Http\Requests\Vehicles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_date' => ['required', 'date'],
            'odometer_reading' => ['required', 'integer', 'min:0'],
            'cost' => ['required', 'numeric', 'min:0'],
            'service_center_name' => ['nullable', 'string', 'max:255'],
            'service_center_contact' => ['nullable', 'string', 'max:50'],
            'service_type' => ['required', 'string', Rule::in(['regular_service', 'major_service', 'repair', 'other'])],
            'description' => ['nullable', 'string'],
            'next_service_due_date' => ['nullable', 'date', 'after_or_equal:service_date'],
            'next_service_odometer' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

