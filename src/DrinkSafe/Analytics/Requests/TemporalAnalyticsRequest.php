<?php

declare(strict_types=1);

namespace DrinkSafe\Analytics\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * TemporalAnalyticsRequest
 *
 * Validates request parameters for the temporal analytics API endpoint.
 * Supports filtering by city, period (30/90/365 days), and optional venue UUID.
 */
final class TemporalAnalyticsRequest extends FormRequest
{
    /**
     * Determine if the user is authorised to make this request.
     *
     * Analytics data is publicly accessible, so always returns true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'period' => ['sometimes', 'integer', Rule::in([30, 90, 365])],
            'venue_uuid' => ['sometimes', 'nullable', 'string', 'uuid', 'exists:venues,uuid'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period.in' => 'The period must be one of: 30, 90, or 365 days.',
            'venue_uuid.exists' => 'The specified venue does not exist.',
            'venue_uuid.uuid' => 'The venue UUID must be a valid UUID format.',
        ];
    }

    /**
     * Get the validated city filter.
     *
     * @return string|null City name or null if not specified
     */
    public function getCity(): ?string
    {
        $city = $this->validated('city');

        return is_string($city) ? $city : null;
    }

    /**
     * Get the validated period in days.
     *
     * @return int Period in days (default: 90)
     */
    public function getPeriodDays(): int
    {
        $period = $this->validated('period');

        return is_numeric($period) ? (int) $period : 90;
    }

    /**
     * Get the validated venue UUID filter.
     *
     * @return string|null Venue UUID or null if not specified
     */
    public function getVenueUuid(): ?string
    {
        $venueUuid = $this->validated('venue_uuid');

        return is_string($venueUuid) ? $venueUuid : null;
    }
}
