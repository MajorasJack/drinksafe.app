<?php

declare(strict_types=1);

namespace DrinkSafe\Partners\Requests;

use DrinkSafe\Partners\Models\PartnerLink;
use DrinkSafe\Reports\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * AdminUpdatePartnerLinkRequest
 *
 * Validates the admin's edit of a partner link. The code is printed on the
 * partner's QR code, so it isn't editable. Each field may be sent on its
 * own, so switching a link on or off needs only `active`, but at least one
 * must be sent.
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
     * Refuse a request that changes nothing.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->hasAny(['label', 'destination', 'active'])) {
                    $validator->errors()->add('label', 'Send at least one of label, destination or active.');
                }
            },
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
