<?php

declare(strict_types=1);

namespace App\Http\Requests\Health;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'medical_record_id' => ['nullable', 'integer', 'exists:medical_records,id'],
            'visit_date' => ['required', 'date'],
            'visit_time' => ['nullable', 'date_format:H:i'],
            'status' => ['sometimes', 'string', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'doctor_name' => ['nullable', 'string', 'max:255'],
            'clinic_name' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'visit_type' => ['required', 'string', Rule::in(['consultation', 'follow_up', 'emergency', 'routine_checkup', 'surgery', 'other'])],
            'chief_complaint' => ['nullable', 'string', 'max:255'],
            'examination_findings' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'treatment_given' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'follow_up_at' => ['nullable', 'date'],
            'next_visit_date' => ['nullable', 'date', 'after_or_equal:visit_date'],
        ];
    }
}
