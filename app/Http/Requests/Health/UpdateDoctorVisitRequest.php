<?php

declare(strict_types=1);

namespace App\Http\Requests\Health;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'medical_record_id' => ['nullable', 'exists:medical_records,id'],
            'visit_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'doctor_name' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'clinic' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'follow_up_at' => ['nullable', 'date', 'after_or_equal:visit_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

