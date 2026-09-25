<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $itemType = $this->route('item_type');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('item_types', 'name')->ignore($itemType)],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::enum(RecordStatus::class)],
        ];
    }
}
