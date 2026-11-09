<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Requests;

use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateReportStatusRequest
 *
 * Validates an admin's decision on a report: publish it or reject it, and
 * on a takedown, whether to hide a venue left with no published reports.
 */
final class UpdateReportStatusRequest extends FormRequest
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
            'status' => [
                'required',
                Rule::enum(ReportStatus::class)->only([ReportStatus::Published, ReportStatus::Rejected]),
            ],
            'hide_venue' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The decision the admin made.
     */
    public function status(): ReportStatus
    {
        return ReportStatus::from($this->validated('status'));
    }

    /**
     * Whether a takedown that leaves the venue with no published reports should hide the venue too.
     */
    public function hidesVenue(): bool
    {
        return $this->boolean('hide_venue');
    }
}
