<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Requests;

use DrinkSafe\Reports\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * AdminCoverageRequest
 *
 * Validates the comma-separated cities the admin's coverage table counts.
 */
final class AdminCoverageRequest extends FormRequest
{
    /**
     * Most cities counted at once. Each city takes three count queries.
     */
    public const int MAX_CITIES = 30;

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
            'cities' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Refuse more cities than one page should count.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('cities') && count($this->cities()) > self::MAX_CITIES) {
                    $validator->errors()->add('cities', sprintf('Enter at most %d cities.', self::MAX_CITIES));
                }
            },
        ];
    }

    /**
     * The cities typed, trimmed, with blanks and repeats left out. Empty
     * when the field is blank.
     *
     * Repeats are matched ignoring case, because the counts are, and the
     * first spelling typed is kept.
     *
     * @return list<string>
     */
    public function cities(): array
    {
        return collect(explode(',', (string) $this->input('cities')))
            ->map(fn (string $city): string => trim($city))
            ->reject(fn (string $city): bool => $city === '')
            ->unique(fn (string $city): string => mb_strtolower($city))
            ->values()
            ->all();
    }
}
