<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Resources;

use DrinkSafe\Venues\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * VenueMapResource
 *
 * Minimal resource for map markers to reduce payload size.
 * Includes only fields needed for rendering map pins.
 *
 * @mixin Venue
 */
final class VenueMapResource extends JsonResource
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
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'reports_count' => $this->whenCounted('reports'),
        ];
    }
}
