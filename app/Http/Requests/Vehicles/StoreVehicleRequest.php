<?php

declare(strict_types=1);

namespace App\Http\Requests\Vehicles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['nullable', 'integer', 'exists:family_members,id'],
            'make' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'registration_number' => ['required', 'string', 'max:50', 'unique:vehicles,registration_number'],
            'rc_expiry_date' => ['nullable', 'date'],
            'insurance_expiry_date' => ['nullable', 'date'],
            'puc_expiry_date' => ['nullable', 'date'],
            'color' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['required', 'string', Rule::in(['petrol', 'diesel', 'electric', 'hybrid'])],
        ];
    }
}

