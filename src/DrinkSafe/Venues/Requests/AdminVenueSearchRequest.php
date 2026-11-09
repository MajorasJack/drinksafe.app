<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Requests;

use DrinkSafe\Reports\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * AdminVenueSearchRequest
 *
 * Validates the filters on the admin's list of every venue.
 */
final class AdminVenueSearchRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', Rule::in(['published', 'unpublished', 'orphaned'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * The filters the admin chose, with empty ones left out.
     *
     * @return array{q?: string, city?: string, state?: string}
     */
    public function filters(): array
    {
        return array_filter(
            $this->safe()->only(['q', 'city', 'state']),
            fn (mixed $value): bool => $value !== null,
        );
    }
}
