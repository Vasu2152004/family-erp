<?php

declare(strict_types=1);

namespace App\Http\Requests\Medicines;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicineIntakeReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Decode JSON string for selected_dates if it's a string
        if ($this->has('selected_dates')) {
            $selectedDates = $this->selected_dates;
            
            if (is_string($selectedDates)) {
                // Try to decode JSON
                $decoded = json_decode($selectedDates, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->merge(['selected_dates' => $decoded]);
                } else {
                    // If not JSON, try comma-separated values
                    $dates = array_filter(array_map('trim', explode(',', $selectedDates)));
                    $this->merge(['selected_dates' => array_values($dates)]);
                }
            } elseif (!is_array($selectedDates)) {
                $this->merge(['selected_dates' => []]);
            }
        } else {
            // If selected_dates is not present but we have selected_dates_input, use that
            if ($this->has('selected_dates_input') && $this->selected_dates_input) {
                $dates = array_filter(array_map('trim', explode(',', $this->selected_dates_input)));
                $this->merge(['selected_dates' => array_values($dates)]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'reminder_time' => ['required', 'date_format:H:i'],
            'frequency' => ['required', 'string', Rule::in(['daily', 'weekly', 'custom'])],
            'days_of_week' => ['required_if:frequency,weekly', 'array', 'min:1'],
            'days_of_week.*' => ['string', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'selected_dates' => ['required_if:frequency,custom', 'array', 'min:1'],
            'selected_dates.*' => ['required', 'date', 'after_or_equal:today'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'family_member_id' => ['nullable', 'exists:family_members,id'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'paused', 'completed'])],
        ];
    }
}














