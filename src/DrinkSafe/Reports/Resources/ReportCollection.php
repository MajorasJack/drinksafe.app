<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

/**
 * ReportCollection
 *
 * Transforms a collection of Report models with pagination metadata.
 * Includes pagination information when available.
 */
final class ReportCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response = [
            'data' => $this->collection,
        ];

        if ($this->resource instanceof AbstractPaginator) {
            $response['meta'] = [
                'current_page' => $this->resource->currentPage(),
                'from' => $this->resource->firstItem(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'to' => $this->resource->lastItem(),
                'total' => $this->resource->total(),
            ];
        }

        return $response;
    }
}
