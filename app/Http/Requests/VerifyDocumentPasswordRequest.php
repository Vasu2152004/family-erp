<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDocumentPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');
        $user = $this->user();

        if (!$document || !$user) {
            return false;
        }

        // Allow password verification if user belongs to the same tenant and family
        // (They need to verify password before they can view/download)
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        // Check if user belongs to the family (has role or is a member)
        $hasRole = \App\Models\FamilyUserRole::where('family_id', $document->family_id)
            ->where('user_id', $user->id)
            ->exists();
        
        $isMember = \App\Models\FamilyMember::where('family_id', $document->family_id)
            ->where('user_id', $user->id)
            ->exists();

        return $hasRole || $isMember;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:6', 'max:64'],
        ];
    }
}

