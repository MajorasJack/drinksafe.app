<?php

declare(strict_types=1);

namespace DrinkSafe\Partners\Requests;

use Carbon\CarbonImmutable;
use DrinkSafe\Reports\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * AdminPartnerRangeRequest
 *
 * Validates the date range the admin's partner scan totals cover.
 */
final class AdminPartnerRangeRequest extends FormRequest
{
    /**
     * Days covered when no start date is given, today included.
     */
    public const int DEFAULT_DAYS = 30;

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
     * A start date with no end date runs to today, so it can't be after today.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d', ...($this->filled('to') ? [] : [sprintf('before_or_equal:%s', today('Europe/London')->toDateString())])],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
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
            'from.before_or_equal' => 'The start date must be on or before today.',
            'to.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }

    /**
     * The UK dates to total between, both included. A blank end is today
     * and a blank start is 30 days before the end, counting the end.
     *
     * @return array{from: CarbonImmutable, to: CarbonImmutable}
     */
    public function range(): array
    {
        $to = $this->filled('to') ? CarbonImmutable::parse($this->validated('to')) : CarbonImmutable::today('Europe/London');

        return [
            'from' => $this->filled('from') ? CarbonImmutable::parse($this->validated('from')) : $to->subDays(self::DEFAULT_DAYS - 1),
            'to' => $to,
        ];
    }
}
