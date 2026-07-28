<?php

declare(strict_types=1);

namespace App\Http\Requests\Links;

use App\Enums\LinkStatus;
use App\Rules\SafeRedirectUrl;
use App\Services\ShortCodeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('custom_alias')) {
            $this->merge([
                'custom_alias' => app(ShortCodeService::class)->normalizeAlias((string) $this->input('custom_alias')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'original_url' => ['required', 'string', 'max:2048', new SafeRedirectUrl()],
            'custom_alias' => [
                'sometimes',
                'nullable',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9_-]+$/',
                Rule::notIn(config('cloudigo.reserved_aliases', [])),
                Rule::unique('links', 'short_code'),
            ],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
            'status' => ['sometimes', Rule::enum(LinkStatus::class)],
        ];
    }
}
