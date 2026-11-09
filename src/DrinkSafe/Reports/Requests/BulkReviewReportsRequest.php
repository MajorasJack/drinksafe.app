<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Requests;

use DrinkSafe\Reports\Enums\ReportStatus;
use DrinkSafe\Reports\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BulkReviewReportsRequest
 *
 * Validates an admin's decision on several pending reports at once, and
 * which venues to hide if rejecting them leaves those venues with no
 * published reports.
 */
final class BulkReviewReportsRequest extends FormRequest
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
            'uuids' => ['required', 'array', 'max:100'],
            'uuids.*' => ['required', 'uuid'],
            'status' => [
                'required',
                Rule::enum(ReportStatus::class)->only([ReportStatus::Published, ReportStatus::Rejected]),
            ],
            'hide_venues' => ['sometimes', 'array', 'max:100'],
            'hide_venues.*' => ['required', 'uuid'],
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
     * The reports the decision applies to.
     *
     * @return array<int, string>
     */
    public function uuids(): array
    {
        return $this->validated('uuids');
    }

    /**
     * The venues to hide if a rejection leaves them with no published reports.
     *
     * @return array<int, string>
     */
    public function hideVenueUuids(): array
    {
        return $this->validated('hide_venues', []);
    }
}
