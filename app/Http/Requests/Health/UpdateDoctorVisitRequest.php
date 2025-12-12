<?php

declare(strict_types=1);

namespace App\Http\Requests\Health;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $visit = $this->route('visit');
        return $visit ? $this->user()?->can('update', $visit) ?? false : false;
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['sometimes', 'required', 'integer', 'exists:family_members,id'],
            'medical_record_id' => ['sometimes', 'nullable', 'integer', 'exists:medical_records,id'],
            'visit_date' => ['sometimes', 'date'],
            'visit_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'status' => ['sometimes', 'string', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'doctor_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'clinic_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'specialization' => ['sometimes', 'nullable', 'string', 'max:255'],
            'visit_type' => ['sometimes', 'string', Rule::in(['consultation', 'follow_up', 'emergency', 'routine_checkup', 'surgery', 'other'])],
            'chief_complaint' => ['sometimes', 'nullable', 'string', 'max:255'],
            'examination_findings' => ['sometimes', 'nullable', 'string'],
            'diagnosis' => ['sometimes', 'nullable', 'string', 'max:255'],
            'treatment_given' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'follow_up_at' => ['sometimes', 'nullable', 'date'],
            'next_visit_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
