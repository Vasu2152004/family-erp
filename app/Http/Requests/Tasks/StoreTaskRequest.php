<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $frequency = $this->input('frequency');

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'frequency' => ['required', 'string', Rule::in(['daily', 'weekly', 'monthly', 'once'])],
            'family_member_id' => ['nullable', 'integer', 'exists:family_members,id'],
            'status' => ['nullable', 'string', Rule::in(['pending', 'in_progress', 'done'])],
            'recurrence_time' => ['nullable', 'date_format:H:i'],
        ];

        // Conditional validation based on frequency
        if ($frequency === 'once') {
            $rules['due_date'] = ['required', 'date', 'after_or_equal:today'];
            $rules['recurrence_day'] = ['nullable'];
        } elseif ($frequency === 'weekly') {
            $rules['recurrence_day'] = ['required', 'integer', 'min:1', 'max:7'];
            $rules['due_date'] = ['nullable'];
        } elseif ($frequency === 'monthly') {
            $rules['recurrence_day'] = ['required', 'integer', 'min:1', 'max:31'];
            $rules['due_date'] = ['nullable'];
        } else {
            // daily
            $rules['recurrence_day'] = ['nullable'];
            $rules['due_date'] = ['nullable'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Task title is required.',
            'frequency.required' => 'Task frequency is required.',
            'frequency.in' => 'Frequency must be one of: daily, weekly, monthly, or once.',
            'due_date.required' => 'Due date is required for one-time tasks.',
            'due_date.after_or_equal' => 'Due date must be today or in the future.',
            'recurrence_day.required' => 'Recurrence day is required for weekly and monthly tasks.',
            'recurrence_day.min' => 'Recurrence day must be at least 1.',
            'recurrence_day.max' => 'Recurrence day must be at most 7 for weekly tasks or 31 for monthly tasks.',
        ];
    }
}
