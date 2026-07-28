<?php

declare(strict_types=1);

namespace App\Http\Requests\Links;

use App\Enums\LinkStatus;
use App\Rules\SafeRedirectUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'original_url' => ['sometimes', 'required', 'string', 'max:2048', new SafeRedirectUrl()],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
            'status' => ['sometimes', Rule::enum(LinkStatus::class)],
        ];
    }
}
