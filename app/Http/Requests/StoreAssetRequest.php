<?php

namespace App\Http\Requests;

use App\Enums\AssetType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
            'type' => ['required', Rule::enum(AssetType::class)],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'serial_number' => ['required', 'string', 'max:255', Rule::unique('assets', 'serial_number')],
            'ram' => ['nullable', 'string', 'max:50'],
            'ram_gb' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'storage' => ['nullable', 'string', 'max:100'],
            'asset_tag' => ['required', 'string', 'max:255', Rule::unique('assets', 'asset_tag')],
            'acquired_at' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
