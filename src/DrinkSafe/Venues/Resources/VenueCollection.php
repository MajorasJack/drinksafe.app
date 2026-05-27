<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * VenueCollection
 *
 * Transforms a collection of Venue models into a JSON API response
 * with optional pagination metadata.
 */
final class VenueCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}
