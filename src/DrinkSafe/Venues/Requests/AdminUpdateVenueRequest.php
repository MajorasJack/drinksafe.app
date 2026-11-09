<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Requests;

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Scopes\PublishedVenueScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * AdminUpdateVenueRequest
 *
 * Validates the admin's edit of a venue's details. The slug isn't editable,
 * so existing links keep working.
 */
final class AdminUpdateVenueRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
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
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
        ];
    }

    /**
     * Refuse a name and city that another venue already has, published or not.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['name', 'city'])) {
                    return;
                }

                $clashingVenue = Venue::withoutGlobalScope(PublishedVenueScope::class)
                    ->where('name', $this->input('name'))
                    ->where('city', $this->input('city'))
                    ->whereKeyNot($this->route('uuid'))
                    ->first();

                if ($clashingVenue !== null) {
                    $validator->errors()->add('name', sprintf(
                        '%s in %s already exists, so choose a different name or city.',
                        $clashingVenue->name,
                        $clashingVenue->city,
                    ));
                }
            },
        ];
    }
}
