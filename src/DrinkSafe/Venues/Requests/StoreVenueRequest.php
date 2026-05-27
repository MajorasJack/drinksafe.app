<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Requests;

use DrinkSafe\Venues\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreVenueRequest
 *
 * Validates data for creating a new venue.
 * Ensures all required fields are present and checks for duplicate venues.
 */
final class StoreVenueRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
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
            'name.required' => 'Venue name is required.',
            'name.max' => 'Venue name must not exceed 255 characters.',
            'city.required' => 'City is required.',
            'city.max' => 'City name must not exceed 100 characters.',
            'address.max' => 'Address must not exceed 500 characters.',
            'latitude.required' => 'Latitude coordinate is required.',
            'latitude.numeric' => 'Latitude must be a valid number.',
            'latitude.between' => 'Latitude must be between -90 and 90 degrees.',
            'longitude.required' => 'Longitude coordinate is required.',
            'longitude.numeric' => 'Longitude must be a valid number.',
            'longitude.between' => 'Longitude must be between -180 and 180 degrees.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Adds custom validation to check for duplicate venues (same name + city).
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
     * Check if a venue with the same name and city already exists.
     */
    protected function duplicateExists(): bool
    {
        return Venue::where('name', $this->input('name'))
            ->where('city', $this->input('city'))
            ->exists();
    }
}
