<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $family = $this->route('family');

        if (!$family) {
            return false;
        }

        $familyId = is_numeric($family) ? (int) $family : (int) ($family->id ?? 0);

        return $familyId > 0 && ($this->user()?->isFamilyAdmin($familyId) ?? false);
    }

    public function rules(): array
    {
        $builtInTypes = Document::TYPES;
        $customTypes = DocumentType::where('tenant_id', $this->user()->tenant_id)
            ->pluck('slug')
            ->toArray();
        $allowedTypes = array_merge($builtInTypes, $customTypes);

        return [
            'title' => ['required', 'string', 'max:120'],
            'document_type' => ['required', 'string', Rule::in($allowedTypes)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:12288'],
            'family_member_id' => ['nullable', 'integer', 'exists:family_members,id'],
            'is_sensitive' => ['boolean'],
            'password' => ['required_if:is_sensitive,true', 'nullable', 'string', 'min:6', 'max:64'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}

