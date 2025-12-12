<?php

declare(strict_types=1);

namespace App\Http\Requests\Notes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'visibility' => ['required', Rule::in(['shared', 'private', 'locked'])],
            'pin' => ['nullable', 'string', 'min:4', 'max:32', 'required_if:visibility,locked'],
        ];
    }
}