<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreReportRequest
 *
 * Validates report submission data including venue information (existing or new),
 * incident details, and description. Performs custom PII validation to prevent
 * submission of personal information.
 */
final class StoreReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorised to make this request.
     *
     * Returns true as report submission is open to all users.
     * Future: Add authentication check when user system is implemented.
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
            // Venue identification (either existing venue OR new venue details)
            'venue_uuid' => [
                'nullable',
                'exists:venues,uuid',
            ],
            'venue_name' => [
                'required_without:venue_uuid',
                'string',
                'max:255',
            ],
            'venue_city' => [
                'required_without:venue_uuid',
                'string',
                'max:100',
            ],
            'venue_address' => [
                'nullable',
                'string',
                'max:500',
            ],

            // Optional geolocation coordinates
            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            // Incident details
            'incident_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'time_of_day' => [
                'required',
                Rule::in(['Morning', 'Afternoon', 'Evening', 'Night', 'Unknown']),
            ],

            // Description
            'description' => [
                'required',
                'string',
                'min:20',
                'max:1000',
            ],
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
            'venue_uuid.exists' => 'The selected venue does not exist.',
            'venue_name.required_without' => 'Please provide a venue name or select an existing venue.',
            'venue_city.required_without' => 'Please provide a city or select an existing venue.',
            'incident_date.required' => 'Please specify when the incident occurred.',
            'incident_date.before_or_equal' => 'Incident date cannot be in the future.',
            'time_of_day.required' => 'Please select the time of day.',
            'time_of_day.in' => 'Please select a valid time of day.',
            'description.required' => 'Please provide a description of the incident.',
            'description.min' => 'Please provide at least 20 characters of detail.',
            'description.max' => 'Description must not exceed 1000 characters.',
            'latitude.between' => 'Latitude must be between -90 and 90 degrees.',
            'longitude.between' => 'Longitude must be between -180 and 180 degrees.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Adds custom validation to detect personally identifiable information (PII)
     * in the description field. Prevents submission if emails or phone numbers
     * are detected.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $description = $this->input('description', '');

            if ($this->containsPII($description)) {
                $validator->errors()->add(
                    'description',
                    'Description contains personal information. Please remove names, emails, and phone numbers.'
                );
            }
        });
    }

    /**
     * Check if description contains personally identifiable information.
     *
     * Detects email addresses and phone numbers using regex patterns.
     * Returns true if PII is detected, false otherwise.
     */
    protected function containsPII(string $description): bool
    {
        // Check for email addresses
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $description)) {
            return true;
        }

        // Check for UK phone numbers (+44 or 0 prefix)
        if (preg_match('/(\+44|0)\s?\d{3,4}\s?\d{3,4}\s?\d{3,4}/', $description)) {
            return true;
        }

        // Check for general international phone numbers (XXX-XXX-XXXX pattern)
        if (preg_match('/\d{3}[-.\s]?\d{3}[-.\s]?\d{4}/', $description)) {
            return true;
        }

        return false;
    }
}
