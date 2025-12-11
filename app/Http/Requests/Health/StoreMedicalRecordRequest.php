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

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'exists:family_members,id'],
            'title' => ['required', 'string', 'max:255'],
            'record_type' => ['required', Rule::in(['general', 'diagnosis', 'lab', 'imaging', 'vaccine', 'allergy', 'other'])],
            'doctor_name' => ['nullable', 'string', 'max:255'],
            'recorded_at' => ['nullable', 'date', 'before_or_equal:today'],
            'primary_condition' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', Rule::in(['mild', 'moderate', 'severe', 'critical'])],
            'symptoms' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'treatment_plan' => ['nullable', 'string'],
            'follow_up_at' => ['nullable', 'date', 'after_or_equal:recorded_at'],
            'summary' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

