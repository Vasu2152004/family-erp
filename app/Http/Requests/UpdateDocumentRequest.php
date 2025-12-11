<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document ? $this->user()?->can('update', $document) ?? false : false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:120'],
            'document_type' => ['sometimes', 'string', Rule::in(Document::TYPES)],
            'family_member_id' => ['sometimes', 'nullable', 'integer', 'exists:family_members,id'],
            'is_sensitive' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'string', 'min:6', 'max:64'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}

