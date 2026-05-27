# Data Flow Architecture

**Last Updated:** 2026-05-14

---

## Overview

This document describes the request-response flow in the DrinkSafe application, from client interaction through backend processing and back to the client. Understanding this flow is essential for implementing new features and debugging issues.

---

## Architecture Pattern

DrinkSafe follows a layered architecture with clear separation of concerns:

```
Client (Browser)
    ↕
Inertia.js Layer
    ↕
HTTP Layer (Controllers)
    ↕
Business Logic Layer (Services)
    ↕
Data Layer (Models / Database)
```

---

## Request Flow

### 1. Client-Side Request (Vue.js + Inertia.js)

**User Action:**
User interacts with the application (clicks button, submits form, navigates)

**Inertia Navigation:**
```vue
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { route } from '@/routes'

const viewVenue = (uuid: string) => {
    router.visit(route('venues.show', { uuid }))
}
</script>

<template>
    <Link :href="route('venues.show', { uuid: venue.uuid })">
        View Venue
    </Link>
</template>
```

**What Happens:**
- Inertia intercepts navigation
- Makes XHR request to Laravel backend
- Requests JSON response with page props
- No full page reload (SPA behavior)

---

### 2. Route Resolution

**Laravel Routes:**
```php
// routes/drinksafe.php
Route::get('/venue/{uuid}', [VenueDetailController::class, '__invoke'])
    ->name('venues.show');
```

**What Happens:**
- Request hits Laravel router
- Route matched by URI pattern
- Controller and method determined
- Middleware applied (CSRF, authentication, etc.)

---

### 3. Controller Layer

**Controller Responsibility:**
- Receive HTTP request
- Validate input (via FormRequests)
- Delegate to services for business logic
- Format response (API Resources or Inertia::render())

**Example: Inertia Page Controller**
```php
<?php

namespace DrinkSafe\Pages;

use Inertia\Inertia;
use Inertia\Response;
use DrinkSafe\Venues\Services\VenueService;
use DrinkSafe\Venues\Resources\VenueResource;
use DrinkSafe\Reports\Resources\ReportCollection;

class VenueDetailController extends Controller
{
    public function __construct(
        private readonly VenueService $venueService
    ) {}

    public function __invoke(string $uuid): Response
    {
        // Delegate to service layer
        $venue = $this->venueService->getVenueById($uuid);
        $reports = $this->venueService->getVenueReports($uuid);

        // Transform to API Resources
        return Inertia::render('VenueDetail', [
            'venue' => new VenueResource($venue),
            'reports' => new ReportCollection($reports),
        ]);
    }
}
```

**Example: API Controller**
```php
<?php

namespace DrinkSafe\Venues\Controllers;

use Illuminate\Http\JsonResponse;
use DrinkSafe\Venues\Services\VenueService;
use DrinkSafe\Venues\Resources\VenueResource;
use Symfony\Component\HttpFoundation\Response;

class VenueController extends Controller
{
    public function __construct(
        private readonly VenueService $venueService
    ) {}

    public function show(string $uuid): JsonResponse
    {
        $venue = $this->venueService->getVenueById($uuid);

        return (new VenueResource($venue))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
```

**Key Principle:** Controllers are thin - they orchestrate, they don't contain business logic.

---

### 4. Service Layer

**Service Responsibility:**
- Implement business logic
- Coordinate multiple models
- Apply business rules
- Throw custom exceptions on errors
- Return domain objects (models, collections)

**Example: VenueService**
```php
<?php

namespace DrinkSafe\Venues\Services;

use Illuminate\Support\Collection;
use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;

class VenueService
{
    public function getVenueById(string $uuid): Venue
    {
        $venue = Venue::with(['reports' => function ($query) {
            $query->latest()->limit(50);
        }])->find($uuid);

        if (!$venue) {
            throw new VenueNotFoundException($uuid);
        }

        return $venue;
    }

    public function getVenueReports(string $uuid, int $limit = 50): Collection
    {
        $venue = $this->getVenueById($uuid);

        return $venue->reports()
            ->latest('incident_date')
            ->limit($limit)
            ->get();
    }

    public function createVenue(array $data): Venue
    {
        // Business logic: Check for duplicates
        $existing = Venue::where('name', $data['name'])
            ->where('city', $data['city'])
            ->first();

        if ($existing) {
            throw new VenueDuplicateException($data['name'], $data['city']);
        }

        return Venue::create($data);
    }
}
```

**Key Principles:**
- Services are stateless (no instance properties storing request-specific data)
- Services use dependency injection
- Services throw domain exceptions
- Services coordinate multiple models when needed

---

### 5. Model Layer (Eloquent)

**Model Responsibility:**
- Define database schema
- Define relationships
- Provide query scopes
- Cast attributes

**Example: Venue Model**
```php
<?php

namespace DrinkSafe\Venues\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use DrinkSafe\Shared\Traits\HasUuid;
use DrinkSafe\Reports\Models\Report;

class Venue extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'city',
        'address',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'venue_uuid');
    }

    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeNearby($query, float $lat, float $lng, int $radiusKm)
    {
        // Geospatial query scope
        return $query->whereRaw(
            'ST_Distance_Sphere(
                point(longitude, latitude),
                point(?, ?)
            ) / 1000 <= ?',
            [$lng, $lat, $radiusKm]
        );
    }
}
```

**Key Principles:**
- Models are thin - mostly declarative
- Complex queries use scopes
- No business logic in models
- Relationships defined clearly

---

### 6. Database Query

**Query Execution:**
```php
// Eloquent query
$venue = Venue::with('reports')->find($uuid);

// SQL generated:
// SELECT * FROM venues WHERE uuid = ?
// SELECT * FROM reports WHERE venue_uuid = ?
```

**Optimisation Strategies:**
- Eager loading to prevent N+1 queries
- Indexes on foreign keys and filter columns
- Query scopes for reusable filters
- Pagination for large datasets

---

### 7. Response Transformation

**API Resources:**
API Resources transform Eloquent models to JSON with consistent structure.

**Example: VenueResource**
```php
<?php

namespace DrinkSafe\Venues\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VenueResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'city' => $this->city,
            'address' => $this->address,
            'location' => [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
            ],
            'reports_count' => $this->whenLoaded('reports', function () {
                return $this->reports->count();
            }),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

**Benefits:**
- Consistent JSON structure across endpoints
- Conditional field inclusion
- Type casting
- Relationship loading control

---

### 8. Response Sent to Client

**Inertia Response:**
```json
{
    "component": "VenueDetail",
    "props": {
        "venue": {
            "uuid": "abc-123-def",
            "name": "The Neon Club",
            "city": "London",
            "location": {
                "latitude": 51.5074,
                "longitude": -0.1278
            }
        },
        "reports": [...]
    },
    "url": "/venue/abc-123-def",
    "version": "1"
}
```

**Client-Side Handling:**
```vue
<script setup lang="ts">
import type { Venue, Report } from '@/types'

defineProps<{
    venue: Venue
    reports: Report[]
}>()
</script>

<template>
    <div>
        <h1>{{ venue.name }}</h1>
        <p>{{ venue.city }}</p>
        <ReportList :reports="reports" />
    </div>
</template>
```

---

## Form Submission Flow

### 1. User Submits Form

**Vue Component:**
```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { route } from '@/routes'

const form = useForm({
    venue_uuid: '',
    incident_date: '',
    time_of_day: 'Night',
    description: ''
})

const submit = () => {
    form.post(route('reports.store'), {
        onSuccess: () => {
            // Form submitted successfully
        },
        onError: (errors) => {
            // Validation errors available in form.errors
        }
    })
}
</script>

<template>
    <form @submit.prevent="submit">
        <textarea v-model="form.description" />
        <span v-if="form.errors.description">{{ form.errors.description }}</span>
        <button :disabled="form.processing">Submit</button>
    </form>
</template>
```

---

### 2. Request Validation (FormRequest)

**StoreReportRequest:**
```php
<?php

namespace DrinkSafe\Reports\Requests;

use Illuminate\Foundation\Http\FormRequest;
use DrinkSafe\Reports\Enums\TimeOfDay;

class StoreReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'venue_uuid' => 'nullable|exists:venues,uuid',
            'incident_date' => 'required|date|before_or_equal:today',
            'time_of_day' => 'required|in:' . implode(',', TimeOfDay::values()),
            'description' => 'required|string|min:20|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'Please provide at least 20 characters of detail.',
            'description.max' => 'Description must not exceed 1000 characters.',
        ];
    }
}
```

**Validation Flow:**
- FormRequest runs before controller method
- If validation fails: 422 response with errors
- If validation passes: Controller method executes

---

### 3. Service Processes Data

**ReportService:**
```php
<?php

namespace DrinkSafe\Reports\Services;

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Services\ReportModerationService;

class ReportService
{
    public function __construct(
        private readonly ReportModerationService $moderationService
    ) {}

    public function createReport(array $data): Report
    {
        // Business logic: Sanitise description
        $data['description'] = $this->moderationService->sanitizeDescription(
            $data['description']
        );

        // Business logic: Validate no PII
        if (!$this->moderationService->validateDescription($data['description'])) {
            throw new ReportValidationException('Description contains personal information');
        }

        return Report::create($data);
    }
}
```

---

### 4. Success Response

**Controller:**
```php
public function store(StoreReportRequest $request): RedirectResponse
{
    $report = $this->reportService->createReport($request->validated());

    return redirect()
        ->route('venue.show', $report->venue_uuid)
        ->with('success', 'Report submitted successfully');
}
```

**Client Receives:**
- Redirect to venue page
- Flash message available via `usePage().props.flash.success`

---

## Data Flow Diagrams

### Page Load Flow

```
User navigates to /venue/abc-123
    ↓
Inertia.js intercepts navigation
    ↓
XHR request to Laravel: GET /venue/abc-123
    ↓
Router resolves to VenueDetailController
    ↓
Controller calls VenueService.getVenueById('abc-123')
    ↓
Service queries Venue model
    ↓
Model executes SQL: SELECT * FROM venues WHERE uuid = 'abc-123'
    ↓
Database returns row
    ↓
Model hydrated with data
    ↓
Service returns Venue instance
    ↓
Controller transforms to VenueResource
    ↓
Inertia::render('VenueDetail', ['venue' => $resource])
    ↓
JSON response sent to client
    ↓
Inertia.js updates Vue component props
    ↓
Vue re-renders with new data
```

### Form Submission Flow

```
User submits report form
    ↓
Inertia form.post(route('reports.store'), data)
    ↓
XHR request to Laravel: POST /api/reports
    ↓
Router resolves to ReportController::store()
    ↓
StoreReportRequest validates input
    ↓ (validation passes)
Controller calls ReportService.createReport(data)
    ↓
Service sanitises description
    ↓
Service validates no PII
    ↓
Service creates Report model
    ↓
Model executes SQL: INSERT INTO reports ...
    ↓
Database returns new record ID
    ↓
Service returns Report instance
    ↓
Controller returns redirect with flash message
    ↓
Client redirects to venue page
    ↓
Flash message displayed via Sonner toast
```

### Error Flow

```
Service encounters error (e.g., venue not found)
    ↓
Service throws VenueNotFoundException($uuid)
    ↓
Laravel exception handler catches exception
    ↓
Handler renders error response (JSON or Inertia error page)
    ↓
Client receives 404 response
    ↓
Inertia.js renders error page component
```

---

## State Management Flow (Pinia)

### Store Usage

**Pinia Store:**
```ts
// stores/venueStore.ts
import { defineStore } from 'pinia'
import type { Venue } from '@/types/venue'

export const useVenueStore = defineStore('venue', () => {
    const venues = ref<Venue[]>([])
    const loading = ref(false)

    const fetchVenues = async () => {
        loading.value = true
        try {
            const response = await fetch('/api/venues')
            venues.value = await response.json()
        } finally {
            loading.value = false
        }
    }

    return { venues, loading, fetchVenues }
})
```

**Component Usage:**
```vue
<script setup lang="ts">
import { useVenueStore } from '@/stores/venueStore'
import { onMounted } from 'vue'

const venueStore = useVenueStore()

onMounted(() => {
    venueStore.fetchVenues()
})
</script>

<template>
    <div v-if="venueStore.loading">Loading...</div>
    <VenueList v-else :venues="venueStore.venues" />
</template>
```

**When to Use Stores:**
- Client-side state that persists across pages
- Data fetched via AJAX (not Inertia props)
- Shared state across multiple components

**When to Use Inertia Props:**
- Initial page load data
- Server-rendered content
- SEO-critical content

---

## Performance Considerations

### N+1 Query Prevention

**Problem:**
```php
// BAD: N+1 queries
$venues = Venue::all();
foreach ($venues as $venue) {
    echo $venue->reports->count(); // Queries for each venue
}
```

**Solution:**
```php
// GOOD: Eager loading
$venues = Venue::withCount('reports')->get();
foreach ($venues as $venue) {
    echo $venue->reports_count; // No additional queries
}
```

### Pagination

**Large datasets should be paginated:**
```php
public function index(): JsonResponse
{
    $reports = Report::with('venue')
        ->latest('incident_date')
        ->paginate(50);

    return new ReportCollection($reports);
}
```

### Caching

**Cache frequently accessed, rarely changing data:**
```php
public function getAllVenues(): Collection
{
    return Cache::remember('venues:all', 300, function () {
        return Venue::withCount('reports')->get();
    });
}
```

---

## Error Handling Strategy

### Custom Exceptions

**Throw specific exceptions:**
```php
throw new VenueNotFoundException($uuid);
throw new ReportValidationException('PII detected');
```

### Exception Handler

**Global exception handling:**
```php
// app/Exceptions/Handler.php
public function render($request, Throwable $e)
{
    if ($e instanceof VenueNotFoundException) {
        return response()->json([
            'message' => $e->getMessage(),
            'error' => 'venue_not_found'
        ], 404);
    }

    return parent::render($request, $e);
}
```

### Client-Side Error Handling

**Inertia error pages:**
```vue
<!-- resources/js/pages/Error.vue -->
<script setup lang="ts">
defineProps<{
    status: number
    message: string
}>()
</script>

<template>
    <div class="error-page">
        <h1>{{ status }}</h1>
        <p>{{ message }}</p>
        <Link href="/">Go Home</Link>
    </div>
</template>
```

---

## Testing Data Flow

### Feature Tests (Controller Level)

```php
it('returns venue with reports', function () {
    $venue = Venue::factory()
        ->has(Report::factory()->count(3))
        ->create();

    $response = $this->getJson(route('venues.show', $venue->uuid));

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'uuid',
                'name',
                'city',
                'reports_count'
            ]
        ])
        ->assertJson([
            'data' => [
                'uuid' => $venue->uuid,
                'reports_count' => 3
            ]
        ]);
});
```

### Unit Tests (Service Level)

```php
it('throws exception when venue not found', function () {
    $service = app(VenueService::class);

    expect(fn() => $service->getVenueById('invalid-uuid'))
        ->toThrow(VenueNotFoundException::class);
});
```

---

## Related Documentation

- [Modular Architecture Structure](./modular-structure.md)
- [Naming Conventions](./naming-conventions.md)
- [Testing Strategy](../../docs/active/react-to-vue-migration/react-to-vue-migration-context.md#testing-strategy)

---

**Document Status:** Active Reference
**Created:** 2026-05-14
**Last Updated:** 2026-05-14
