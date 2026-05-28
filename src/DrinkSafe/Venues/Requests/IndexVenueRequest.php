<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * IndexVenueRequest
 *
 * Validates query parameters for listing venues.
 * Supports filtering by bounds (viewport) and city.
 */
final class IndexVenueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'bounds' => ['nullable', 'string', 'regex:/^-?\d+\.?\d*,-?\d+\.?\d*,-?\d+\.?\d*,-?\d+\.?\d*$/'],
            'city' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bounds.regex' => 'Bounds must be in format: swLat,swLng,neLat,neLng (e.g., 51.0,-0.5,52.0,0.5)',
            'city.max' => 'City name must not exceed 100 characters.',
            'limit.integer' => 'Limit must be a valid integer.',
            'limit.min' => 'Limit must be at least 1.',
            'limit.max' => 'Limit must not exceed 500.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Adds custom validation to check bounds coordinate values.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $bounds = $this->input('bounds');
            if ($bounds === null || $bounds === '') {
                return;
            }

            $coordinates = explode(',', $bounds);
            if (count($coordinates) !== 4) {
                return;
            }

            [$swLat, $swLng, $neLat, $neLng] = array_map('floatval', $coordinates);

            if ($swLat < -90 || $swLat > 90) {
                $validator->errors()->add('bounds', 'Southwest latitude must be between -90 and 90.');
            }

            if ($neLat < -90 || $neLat > 90) {
                $validator->errors()->add('bounds', 'Northeast latitude must be between -90 and 90.');
            }

            if ($swLng < -180 || $swLng > 180) {
                $validator->errors()->add('bounds', 'Southwest longitude must be between -180 and 180.');
            }

            if ($neLng < -180 || $neLng > 180) {
                $validator->errors()->add('bounds', 'Northeast longitude must be between -180 and 180.');
            }

            if ($swLat > $neLat) {
                $validator->errors()->add('bounds', 'Southwest latitude must be less than or equal to northeast latitude.');
            }
        });
    }

    /**
     * Get the parsed bounds coordinates.
     *
     * @return array{swLat: float, swLng: float, neLat: float, neLng: float}|null
     */
    public function getBounds(): ?array
    {
        $bounds = $this->validated()['bounds'] ?? null;

        if ($bounds === null || $bounds === '') {
            return null;
        }

        $coordinates = explode(',', $bounds);
        if (count($coordinates) !== 4) {
            return null;
        }

        return [
            'swLat' => (float) $coordinates[0],
            'swLng' => (float) $coordinates[1],
            'neLat' => (float) $coordinates[2],
            'neLng' => (float) $coordinates[3],
        ];
    }
}
