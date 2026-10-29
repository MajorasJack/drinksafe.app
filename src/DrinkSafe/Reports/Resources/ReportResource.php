<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Resources;

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Venues\Resources\VenueResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ReportResource
 *
 * Transforms Report model to consistent JSON structure for API responses.
 * Includes venue relationship when eager loaded and visible. Moderation
 * fields are only sent to admins.
 *
 * @mixin Report
 */
final class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'venue_uuid' => $this->venue_uuid,
            'incident_date' => $this->incident_date?->toDateString(),
            'incident_time' => $this->incident_time,
            'time_of_day' => $this->time_of_day->value,
            'description' => $this->description,
            'formatted_date' => $this->formatted_date,
            'status' => $this->when($request->user()?->is_admin === true, fn (): string => $this->status->value),
            'reviewed_at' => $this->when($request->user()?->is_admin === true, fn (): ?string => $this->reviewed_at?->toISOString()),
            'venue' => $this->when(
                $this->relationLoaded('venue') && $this->venue !== null,
                fn () => (new VenueResource($this->venue))->resolve()
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
