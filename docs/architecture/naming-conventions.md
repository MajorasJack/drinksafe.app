# Naming Conventions

**Last Updated:** 2026-05-14

---

## Overview

Consistent naming conventions improve code readability, maintainability, and developer experience. This document establishes naming standards for PHP, Vue.js, TypeScript, and file structures within the DrinkSafe application.

---

## PHP Conventions

### Classes

**Format:** PascalCase (TitleCase)

**Examples:**
- `Venue`
- `VenueService`
- `ReportController`
- `TimeOfDay`

### Methods

**Format:** camelCase

**Pattern:** Verb or verb-noun combination

**Examples:**
```php
public function getAllVenues(): Collection
public function createReport(array $data): Report
public function isWithinRadius(float $lat, float $lng, int $radius): bool
```

### Variables and Properties

**Format:** camelCase

**Pattern:** Descriptive nouns

**Examples:**
```php
$venue
$venueService
$reportCollection
$incidentDate
```

### Constants

**Format:** UPPER_SNAKE_CASE

**Examples:**
```php
const MAX_DESCRIPTION_LENGTH = 1000;
const DEFAULT_SEARCH_RADIUS = 10;
```

### Namespaces

**Format:** PascalCase

**Pattern:** `DrinkSafe\{Module}\{Subdirectory}`

**Examples:**
```php
namespace DrinkSafe\Venues\Services;
namespace DrinkSafe\Reports\Exceptions;
namespace DrinkSafe\Shared\Traits;
```

### Model Properties

**Database columns:** snake_case
**PHP properties:** camelCase (via attribute casting)

**Example:**
```php
// Database: venue_uuid
// PHP: $this->venueUuid (via casting or accessor)
```

### Exceptions

**Format:** PascalCase ending in `Exception`

**Pattern:** Descriptive noun + `Exception`

**Examples:**
```php
VenueNotFoundException
ReportValidationException
DrinkSafeException
```

### Enums

**Enum name:** PascalCase singular noun
**Case values:** PascalCase

**Example:**
```php
enum TimeOfDay: string
{
    case Morning = 'Morning';
    case Afternoon = 'Afternoon';
    case Evening = 'Evening';
}
```

### Traits

**Format:** PascalCase starting with adjective or capability

**Examples:**
```php
HasUuid
SoftDeletes
HasFactory
```

---

## Vue.js and TypeScript Conventions

### Component Names

**Format:** PascalCase

**Pattern:** Descriptive noun or noun-verb combination

**Single-file components:** Match class name

**Examples:**
```vue
AppLayout.vue
VenueCard.vue
ReportList.vue
SearchBar.vue
```

### Component Usage

**In templates:** PascalCase or kebab-case

**Examples:**
```vue
<AppHeader />
<app-header />  <!-- Both valid -->

<VenueCard :venue="venue" />
```

### Props

**Format:** camelCase in script, kebab-case in templates

**Example:**
```vue
<script setup lang="ts">
defineProps<{
    venueUuid: string
    recentReports: Report[]
}>()
</script>

<!-- Usage in parent -->
<VenueDetail :venue-uuid="uuid" :recent-reports="reports" />
```

### Events

**Format:** kebab-case

**Pattern:** Verb (past tense) or verb-object

**Examples:**
```vue
@marker-click="handleClick"
@venue-selected="onVenueSelected"
@form-submitted="handleSubmit"
```

### Composables

**Format:** camelCase starting with `use`

**Pattern:** `use{Noun}` or `use{Verb}{Noun}`

**Examples:**
```ts
useVenues()
useReports()
useSearch()
useFilters()
useMap()
```

### Stores (Pinia)

**Format:** camelCase ending in `Store`

**Pattern:** `{noun}Store`

**Examples:**
```ts
venueStore
reportStore
userStore
```

### TypeScript Interfaces and Types

**Format:** PascalCase

**Interfaces:** No `I` prefix
**Types:** PascalCase or camelCase for utility types

**Examples:**
```ts
interface Venue {
    uuid: string
    name: string
}

interface VenueFilters {
    city?: string
    searchQuery?: string
}

type TimeOfDay = 'Morning' | 'Afternoon' | 'Evening' | 'Night' | 'Unknown'
```

### TypeScript Variables

**Format:** camelCase

**Pattern:** Descriptive noun

**Examples:**
```ts
const venue: Venue = { ... }
const venues: Venue[] = []
const venueService = useVenues()
```

### TypeScript Functions

**Format:** camelCase

**Pattern:** Verb or verb-noun combination

**Examples:**
```ts
function fetchVenues(): Promise<Venue[]>
function filterByCity(city: string): Venue[]
const searchVenues = (query: string) => { ... }
```

---

## File and Directory Naming

### PHP Files

**Format:** PascalCase (matching class name)

**Pattern:** `{ClassName}.php`

**Examples:**
```
Venue.php
VenueService.php
StoreReportRequest.php
```

### Vue Component Files

**Format:** PascalCase

**Pattern:** `{ComponentName}.vue`

**Examples:**
```
AppLayout.vue
VenueCard.vue
ReportList.vue
```

### TypeScript Files

**Format:** camelCase or kebab-case

**Composables:** `use{Name}.ts`
**Stores:** `{name}Store.ts`
**Utilities:** `{name}.ts`

**Examples:**
```
useVenues.ts
venueStore.ts
venue.ts (type definitions)
api.ts
```

### Directories

**PHP modules:** PascalCase
**Vue directories:** camelCase or kebab-case
**Subdirectories:** PascalCase (PHP) or camelCase (Vue)

**Examples:**
```
src/DrinkSafe/Venues/
src/DrinkSafe/Reports/
resources/js/pages/
resources/js/components/venues/
resources/js/composables/
```

### Database Migrations

**Format:** snake_case with timestamp prefix

**Pattern:** `{timestamp}_create_{table_name}_table.php`

**Examples:**
```
2024_01_01_000000_create_venues_table.php
2024_01_01_000001_create_reports_table.php
```

### Database Tables

**Format:** snake_case, plural

**Examples:**
```
venues
reports
venue_reports (pivot tables)
```

### Database Columns

**Format:** snake_case

**Examples:**
```
venue_uuid
incident_date
time_of_day
created_at
```

### Route Names

**Format:** dot.notation, lowercase

**Pattern:** `{module}.{action}`

**Examples:**
```php
Route::get('/venues', [VenueController::class, 'index'])->name('venues.index');
Route::get('/venues/{uuid}', [VenueController::class, 'show'])->name('venues.show');
Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
```

---

## Test Naming Conventions

### Test Files

**Format:** PascalCase ending with `Test`

**Pattern:** `{ClassUnderTest}Test.php`

**Examples:**
```
VenueTest.php
VenueServiceTest.php
ReportControllerTest.php
```

### Test Methods (PestPHP)

**Format:** Descriptive sentence in `it()` function

**Pattern:** `it('{describes expected behavior}')`

**Examples:**
```php
it('creates venue with UUID primary key')
it('throws exception when venue not found')
it('filters reports by date range')
it('validates description contains no PII')
```

### Test Method Naming (Traditional PHPUnit)

**Format:** camelCase starting with `test`

**Pattern:** `test{ExpectedBehavior}`

**Examples:**
```php
testCreatesVenueWithUuidPrimaryKey()
testThrowsExceptionWhenVenueNotFound()
testFiltersReportsByDateRange()
```

---

## Documentation Naming

### Markdown Files

**Format:** kebab-case

**Examples:**
```
modular-structure.md
naming-conventions.md
data-flow.md
react-to-vue-migration-plan.md
```

### README Files

**Format:** `README.md` (uppercase)

**Location:** Every module and major directory

---

## Examples by Category

### Backend Service Example

**File:** `src/DrinkSafe/Venues/Services/VenueSearchService.php`

```php
<?php

declare(strict_types=1);

namespace DrinkSafe\Venues\Services;

use Illuminate\Support\Collection;
use DrinkSafe\Venues\Models\Venue;

class VenueSearchService
{
    public function searchByName(string $query): Collection
    {
        return Venue::where('name', 'LIKE', "%{$query}%")
            ->get();
    }

    public function filterByCity(string $city): Collection
    {
        return Venue::where('city', $city)->get();
    }

    public function findNearbyVenues(
        float $latitude,
        float $longitude,
        int $radiusKm = 10
    ): Collection {
        return Venue::nearby($latitude, $longitude, $radiusKm)->get();
    }
}
```

### Frontend Component Example

**File:** `resources/js/components/venues/VenueCard.vue`

```vue
<script setup lang="ts">
import { computed } from 'vue'
import type { Venue } from '@/types/venue'

interface Props {
    venue: Venue
    showReportCount?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    showReportCount: true
})

const emit = defineEmits<{
    click: [venue: Venue]
    'report-view': [uuid: string]
}>()

const handleCardClick = () => {
    emit('click', props.venue)
}
</script>

<template>
    <div class="venue-card" @click="handleCardClick">
        <h3>{{ venue.name }}</h3>
        <p>{{ venue.city }}</p>
        <span v-if="showReportCount">{{ venue.reportsCount }} reports</span>
    </div>
</template>
```

### Frontend Composable Example

**File:** `resources/js/composables/useVenues.ts`

```ts
import { ref, computed } from 'vue'
import type { Venue, VenueFilters } from '@/types/venue'
import { useVenueStore } from '@/stores/venueStore'

export function useVenues() {
    const venueStore = useVenueStore()

    const venues = computed(() => venueStore.venues)
    const loading = ref(false)

    const fetchVenues = async (filters?: VenueFilters) => {
        loading.value = true
        try {
            await venueStore.fetchVenues(filters)
        } finally {
            loading.value = false
        }
    }

    const searchVenues = async (query: string) => {
        loading.value = true
        try {
            await venueStore.searchVenues(query)
        } finally {
            loading.value = false
        }
    }

    return {
        venues,
        loading,
        fetchVenues,
        searchVenues
    }
}
```

---

## Special Cases and Edge Cases

### Acronyms

**In class names:** PascalCase the entire acronym if short

**Examples:**
```php
PIIDetector (not PiiDetector)
APIResource (not ApiResource)
UUIDGenerator (not UuidGenerator)
```

**Exception:** Long acronyms use standard PascalCase
```php
HttpRequestHandler (not HTTPRequestHandler)
```

### Compound Words

**Backend (PHP):** No separator, PascalCase or camelCase
```php
VenueSearchService
incidentDate
```

**Frontend (TypeScript/Vue):** No separator, camelCase
```ts
venueSearchQuery
searchBarVisible
```

### Boolean Variables and Methods

**Format:** Start with `is`, `has`, `should`, or `can`

**Examples:**
```php
$isActive
$hasReports
public function shouldModerate(): bool
public function canUpdate(): bool
```

```ts
const isLoading = ref(false)
const hasErrors = computed(() => errors.length > 0)
```

### Private/Protected Methods

**Format:** Same as public methods (camelCase)

**No prefix required** (unlike some conventions with `_prefix`)

**Example:**
```php
private function calculateDistance(float $lat1, float $lng1): float
{
    // ...
}
```

---

## Naming Anti-Patterns to Avoid

### Avoid Abbreviations
```php
// Bad
$rpt
$vn
$usr

// Good
$report
$venue
$user
```

### Avoid Single-Letter Variables
**Exception:** Loop counters and coordinates

```php
// Bad
$v = Venue::find($uuid);

// Good
$venue = Venue::find($uuid);

// Acceptable
for ($i = 0; $i < count($items); $i++)
foreach ($venues as $v) // Only if scope is tiny
```

### Avoid Generic Names
```php
// Bad
$data
$info
$temp
$manager

// Good
$venueData
$reportDetails
$temporaryVenue
$venueService
```

### Avoid Redundant Names
```php
// Bad
VenueVenue
VenueClass
venueVariable

// Good
Venue
venue
```

---

## IDE Configuration

### PHPStorm / VSCode

Configure IDE to enforce:
- PSR-12 coding standard
- camelCase for variables
- PascalCase for classes
- snake_case for database columns

### ESLint / Prettier

Configure for Vue/TypeScript:
```json
{
    "vue/component-name-in-template-casing": ["error", "PascalCase"],
    "vue/prop-name-casing": ["error", "camelCase"],
    "@typescript-eslint/naming-convention": [
        "error",
        {
            "selector": "interface",
            "format": ["PascalCase"]
        }
    ]
}
```

---

## Related Documentation

- [Modular Architecture Structure](./modular-structure.md)
- [Data Flow Architecture](./data-flow.md)
- [Laravel Best Practices Guidelines](../../CLAUDE.md)

---

**Document Status:** Active Reference
**Created:** 2026-05-14
**Last Updated:** 2026-05-14
