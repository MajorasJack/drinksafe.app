<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Requests;

use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateVenueRequest
 *
 * Validates data for updating an existing venue.
 * All fields are optional to allow partial updates.
 */
final class UpdateVenueRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
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
            'name.max' => 'Venue name must not exceed 255 characters.',
            'city.max' => 'City name must not exceed 100 characters.',
            'address.max' => 'Address must not exceed 500 characters.',
            'latitude.numeric' => 'Latitude must be a valid number.',
            'latitude.between' => 'Latitude must be between -90 and 90 degrees.',
            'longitude.numeric' => 'Longitude must be a valid number.',
            'longitude.between' => 'Longitude must be between -180 and 180 degrees.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Adds custom validation to check for duplicate venues when name or city is updated.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->duplicateExists()) {
                $validator->errors()->add(
                    'name',
                    sprintf(
                        "A venue named '%s' already exists in %s.",
                        $this->input('name'),
                        $this->input('city')
                    )
                );
            }
        });
    }

    /**
     * Check if a venue with the same name and city already exists (excluding current venue).
     */
    protected function duplicateExists(): bool
    {
        $uuid = $this->route('uuid');
        $name = $this->input('name');
        $city = $this->input('city');

        if ($name === null && $city === null) {
            return false;
        }

        $query = Venue::where('uuid', '!=', $uuid);

        if ($name !== null) {
            $query->where('name', $name);
        }

        if ($city !== null) {
            $query->where('city', $city);
        }

        return $query->exists();
    }
}
