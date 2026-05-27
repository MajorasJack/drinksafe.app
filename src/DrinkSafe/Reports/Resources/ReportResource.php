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
 * Includes venue relationship when eager loaded.
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
            'time_of_day' => $this->time_of_day->value,
            'description' => $this->description,
            'formatted_date' => $this->formatted_date,
            'venue' => $this->when(
                $this->relationLoaded('venue'),
                fn () => (new VenueResource($this->venue))->resolve()
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
