<?php

declare(strict_types=1);

namespace DrinkSafe\Heatmap\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HeatmapRequest
 *
 * Validates heatmap API requests with bounding box parameters.
 * Supports north, south, east, west coordinates and time period filter.
 */
final class HeatmapRequest extends FormRequest
{
    /**
     * Determine if the user is authorised to make this request.
     *
     * Heatmap data is publicly accessible, so always returns true.
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
            'north' => ['required', 'numeric', 'between:-90,90'],
            'south' => ['required', 'numeric', 'between:-90,90'],
            'east' => ['required', 'numeric', 'between:-180,180'],
            'west' => ['required', 'numeric', 'between:-180,180'],
            'period' => ['sometimes', 'integer', Rule::in([7, 30, 90, 365])],
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
            'north.required' => 'North latitude is required',
            'north.between' => 'North latitude must be between -90 and 90',
            'south.required' => 'South latitude is required',
            'south.between' => 'South latitude must be between -90 and 90',
            'east.required' => 'East longitude is required',
            'east.between' => 'East longitude must be between -180 and 180',
            'west.required' => 'West longitude is required',
            'west.between' => 'West longitude must be between -180 and 180',
            'period.in' => 'Period must be 7, 30, 90, or 365 days',
        ];
    }

    /**
     * Get the validated north latitude.
     */
    public function getNorth(): float
    {
        return (float) $this->validated('north');
    }

    /**
     * Get the validated south latitude.
     */
    public function getSouth(): float
    {
        return (float) $this->validated('south');
    }

    /**
     * Get the validated east longitude.
     */
    public function getEast(): float
    {
        return (float) $this->validated('east');
    }

    /**
     * Get the validated west longitude.
     */
    public function getWest(): float
    {
        return (float) $this->validated('west');
    }

    /**
     * Get the validated period in days.
     *
     * @return int Period in days (default: 30)
     */
    public function getPeriodDays(): int
    {
        $period = $this->validated('period');

        return is_numeric($period) ? (int) $period : 30;
    }
}
