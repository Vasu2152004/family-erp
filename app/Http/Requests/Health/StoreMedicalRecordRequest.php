<?php

declare(strict_types=1);

namespace App\Http\Requests\Health;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'title' => ['required', 'string', 'max:255'],
            'record_type' => ['required', 'string', Rule::in(['general', 'diagnosis', 'lab', 'imaging', 'vaccine', 'allergy', 'other'])],
            'category' => ['nullable', 'string', 'max:255'],
            'doctor_name' => ['nullable', 'string', 'max:255'],
            'primary_condition' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'string', Rule::in(['mild', 'moderate', 'severe', 'critical'])],
            'symptoms' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'treatment_plan' => ['nullable', 'string'],
            'follow_up_at' => ['nullable', 'date', 'after_or_equal:today'],
            'recorded_at' => ['nullable', 'date'],
            'summary' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
