<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Resources;

use DrinkSafe\Reports\Resources\ReportResource;
use DrinkSafe\Venues\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * VenueResource
 *
 * Transforms a Venue model into a consistent JSON API response.
 *
 * @mixin Venue
 */
final class VenueResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'city' => $this->city,
            'address' => $this->address,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'reports_count' => $this->whenCounted('reports'),
            'reports' => $this->when(
                $this->relationLoaded('reports'),
                fn (): array => ReportResource::collection($this->reports)->resolve()
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
