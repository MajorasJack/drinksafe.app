<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreVenueRequest
 *
 * Validates data for creating a new venue. `VenueService::createVenue()`
 * refuses a duplicate, because a name and city alone can't tell a chain's
 * branches apart.
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
}
