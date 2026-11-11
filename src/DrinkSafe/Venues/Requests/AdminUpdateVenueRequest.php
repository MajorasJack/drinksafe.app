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
     * Refuse a new name or city when another venue, published or not, already has that name and city.
     *
     * Chain branches share a name and city, so the check only runs when the
     * name or city changes, ignoring case and surrounding spaces. Otherwise
     * a branch couldn't be saved at all, even to move its pin.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['name', 'city']) || ! $this->changesNameOrCity()) {
                    return;
                }

                $clashingVenue = Venue::withoutGlobalScope(PublishedVenueScope::class)
                    ->sameNameAndCity(name: $this->input('name'), city: $this->input('city'))
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

    /**
     * Whether the submitted name or city differs from the venue's own, ignoring case and surrounding spaces.
     *
     * An unknown venue counts as a change, and the controller returns not found for it.
     */
    private function changesNameOrCity(): bool
    {
        $venue = Venue::withoutGlobalScope(PublishedVenueScope::class)->find($this->route('uuid'));

        return $venue === null
            || mb_strtolower(trim($this->input('name'))) !== mb_strtolower($venue->name)
            || mb_strtolower(trim($this->input('city'))) !== mb_strtolower($venue->city);
    }
}
