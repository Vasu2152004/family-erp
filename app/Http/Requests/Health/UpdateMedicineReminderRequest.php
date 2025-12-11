<?php

declare(strict_types=1);

namespace App\Http\Requests\Health;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicineReminderRequest extends FormRequest
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
            'frequency' => ['required', Rule::in(['once', 'daily', 'weekly'])],
            'reminder_time' => ['required', 'date_format:H:i'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'status' => ['required', Rule::in(['active', 'paused', 'completed'])],
        ];
    }
}

