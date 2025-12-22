<?php

declare(strict_types=1);

namespace App\Http\Requests\Medicines;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'min_stock_level' => ['nullable', 'numeric', 'min:0'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'family_member_id' => ['nullable', 'exists:family_members,id'],
            'prescription_id' => ['nullable', 'exists:prescriptions,id'],
            'prescription_file' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
        ];
    }
}
