<?php

declare(strict_types=1);

namespace App\Http\Requests\Health;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('record');
        return $record ? $this->user()?->can('update', $record) ?? false : false;
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['sometimes', 'required', 'integer', 'exists:family_members,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'record_type' => ['sometimes', 'string', Rule::in(['general', 'diagnosis', 'lab', 'imaging', 'vaccine', 'allergy', 'other'])],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'doctor_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'primary_condition' => ['sometimes', 'nullable', 'string', 'max:255'],
            'severity' => ['sometimes', 'nullable', 'string', Rule::in(['mild', 'moderate', 'severe', 'critical'])],
            'symptoms' => ['sometimes', 'nullable', 'string'],
            'diagnosis' => ['sometimes', 'nullable', 'string'],
            'treatment_plan' => ['sometimes', 'nullable', 'string'],
            'follow_up_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
            'recorded_at' => ['sometimes', 'nullable', 'date'],
            'summary' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
