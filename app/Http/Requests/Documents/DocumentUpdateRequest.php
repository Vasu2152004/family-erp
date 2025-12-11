<?php

declare(strict_types=1);

namespace App\Http\Requests\Documents;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentUpdateRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', Rule::in(Document::TYPES)],
            'document_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'family_member_id' => ['nullable', 'exists:family_members,id'],
            'is_sensitive' => ['boolean'],
            'password' => ['nullable', 'string', 'min:6'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today', 'required_if:document_type,PASSPORT,DRIVING_LICENSE,INSURANCE'],
        ];
    }
}

