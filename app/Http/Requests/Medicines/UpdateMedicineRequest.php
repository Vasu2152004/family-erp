<?php

declare(strict_types=1);

namespace App\Http\Requests\Medicines;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $medicine = $this->route('medicine');
        return $medicine ? $this->user()?->can('update', $medicine) ?? false : false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'min_stock_level' => ['nullable', 'numeric', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'family_member_id' => ['nullable', 'exists:family_members,id'],
            'prescription_id' => ['nullable', 'exists:prescriptions,id'],
            'prescription_file' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
        ];
    }
}
