<?php

declare(strict_types=1);

namespace App\Http\Requests\Links;

use App\Enums\LinkStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLinkStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(LinkStatus::class)],
        ];
    }
}
