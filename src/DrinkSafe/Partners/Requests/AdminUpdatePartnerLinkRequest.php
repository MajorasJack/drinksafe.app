<?php

declare(strict_types=1);

namespace DrinkSafe\Partners\Requests;

use DrinkSafe\Partners\Models\PartnerLink;
use DrinkSafe\Reports\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * AdminUpdatePartnerLinkRequest
 *
 * Validates the admin's edit of a partner link. The code is printed on the
 * partner's QR code, so it isn't editable. Each field may be sent on its
 * own, so switching a link on or off needs only `active`.
 */
final class AdminUpdatePartnerLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorised to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('moderate', Report::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'required', 'string', 'max:100'],
            'destination' => ['sometimes', 'required', 'string', 'max:255', sprintf('regex:%s', PartnerLink::DESTINATION_PATTERN)],
            'active' => ['sometimes', 'required', 'boolean'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'destination.regex' => 'The destination must be a path on this site, starting with a single /.',
        ];
    }
}
