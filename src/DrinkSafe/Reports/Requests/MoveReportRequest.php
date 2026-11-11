<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Requests;

use DrinkSafe\Reports\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * MoveReportRequest
 *
 * Validates the venue an admin is moving a pending report onto. Any venue
 * counts, published or not, as a report about a new branch may belong on
 * one that is still waiting for review.
 */
final class MoveReportRequest extends FormRequest
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
            'venue_uuid' => ['required', 'uuid', Rule::exists('venues', 'uuid')],
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
            'venue_uuid.required' => 'Choose a venue to move the report to.',
            'venue_uuid.uuid' => 'That venue doesn\'t exist.',
            'venue_uuid.exists' => 'That venue doesn\'t exist.',
        ];
    }
}
