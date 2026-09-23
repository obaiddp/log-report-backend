<?php

namespace App\Http\Requests;

use App\Enums\AssetType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
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
        $asset = $this->route('asset');

        return [
            'user_id' => ['sometimes', 'integer', Rule::exists(User::class, 'id')],
            'type' => ['sometimes', Rule::enum(AssetType::class)],
            'brand' => ['sometimes', 'string', 'max:255'],
            'model' => ['sometimes', 'string', 'max:255'],
            'serial_number' => ['sometimes', 'string', 'max:255', Rule::unique('assets', 'serial_number')->ignore($asset)],
            'ram' => ['sometimes', 'nullable', 'string', 'max:50'],
            'ram_gb' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999'],
            'storage' => ['sometimes', 'nullable', 'string', 'max:100'],
            'asset_tag' => ['sometimes', 'string', 'max:255', Rule::unique('assets', 'asset_tag')->ignore($asset)],
            'acquired_at' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
