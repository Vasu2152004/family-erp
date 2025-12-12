<?php

declare(strict_types=1);

namespace App\Http\Requests\Health;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $prescription = $this->route('prescription');
        return $prescription ? $this->user()?->can('update', $prescription) ?? false : false;
    }

    public function rules(): array
    {
        return [
            'medication_name' => ['sometimes', 'string', 'max:255'],
            'dosage' => ['sometimes', 'nullable', 'string', 'max:255'],
            'frequency' => ['sometimes', 'nullable', 'string', 'max:255'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', 'max:255'],
            'instructions' => ['sometimes', 'nullable', 'string'],
            'attachment' => ['sometimes', 'nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
        ];
    }
}
