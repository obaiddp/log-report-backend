<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort_by' => ['sometimes', 'string', Rule::in($this->sortFields())],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [];
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->validated('search', ''));

        return $search === '' ? null : $search;
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', 15);
    }

    public function sortBy(): string
    {
        return (string) $this->validated('sort_by', $this->defaultSortBy());
    }

    public function sortDirection(): string
    {
        return (string) $this->validated('sort_direction', $this->defaultSortDirection());
    }

    /**
     * @return array<int, string>
     */
    abstract protected function sortFields(): array;

    protected function defaultSortBy(): string
    {
        return 'created_at';
    }

    protected function defaultSortDirection(): string
    {
        return 'desc';
    }
}
