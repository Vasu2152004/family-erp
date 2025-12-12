<?php

declare(strict_types=1);

namespace App\Http\Requests\Health;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicineReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reminder = $this->route('reminder');
        return $reminder ? $this->user()?->can('update', $reminder) ?? false : false;
    }

    public function rules(): array
    {
        return [
            'frequency' => ['sometimes', 'string', Rule::in(['once', 'daily', 'weekly'])],
            'reminder_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'days_of_week' => ['sometimes', 'nullable', 'array'],
            'days_of_week.*' => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'paused', 'completed'])],
        ];
    }
}
