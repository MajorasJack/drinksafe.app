# Feature Proposals Enhancement - Context

**Last Updated:** 2026-05-27

---

## Key Files

### Documentation to Enhance

| File | Purpose |
|------|---------|
| `docs/features/FEATURE_PROPOSALS.md` | Target document for enhancement |

### Reference Documentation

| File | Purpose |
|------|---------|
| `docs/architecture/modular-structure.md` | Module patterns and directory structure standards |
| `docs/architecture/naming-conventions.md` | PHP, Vue, TypeScript naming standards |
| `docs/api/README.md` | API documentation format and structure |
| `docs/deployment/deployment-guide.md` | Deployment documentation patterns |

### Codebase References

| Path | Purpose |
|------|---------|
| `src/DrinkSafe/Venues/` | Example module structure for backend |
| `src/DrinkSafe/Reports/` | Example module with enums and services |
| `resources/js/components/` | Vue component structure reference |
| `resources/js/composables/` | Composable patterns |
| `resources/js/stores/` | Pinia store patterns |
| `resources/js/types/` | TypeScript type definitions |

---

## Existing Patterns to Follow

### Module Directory Structure (from modular-structure.md)

```
src/DrinkSafe/{ModuleName}/
├── README.md
├── Models/
├── Controllers/
├── Services/
├── Policies/
├── Requests/
├── Resources/
├── Exceptions/
├── Enums/
└── Tests/
    ├── Feature/
    └── Unit/
```

### Service Class Pattern

```php
namespace DrinkSafe\{Module}\Services;

use DrinkSafe\{Module}\Models\{Model};
use DrinkSafe\{Module}\Exceptions\{Exception};

class {Name}Service
{
    public function __construct(
        private readonly DependencyService $dependency
    ) {}

    public function methodName(array $data): Model
    {
        // Business logic
    }
}
```

### Vue Component Pattern

```vue
<script setup lang="ts">
import { computed, ref } from 'vue'
import type { TypeName } from '@/types/typename'

interface Props {
    propName: TypeName
    optionalProp?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    optionalProp: true
})

const emit = defineEmits<{
    'event-name': [payload: TypeName]
}>()
</script>

<template>
    <!-- Template content -->
</template>
```

### Composable Pattern

```typescript
import { ref, computed } from 'vue'
import type { TypeName } from '@/types/typename'

export function useFeatureName() {
    const state = ref<TypeName[]>([])
    const loading = ref(false)

    const computedValue = computed(() => /* ... */)

    const actionMethod = async () => {
        // Implementation
    }

    return {
        state,
        loading,
        computedValue,
        actionMethod
    }
}
```

---

## Features Being Documented

### 1. Venue Safety Scores

**Domain:** Venues module extension
**New Components:**
- Backend: SafetyScoreService, score computation logic
- Frontend: SafetyBadge component, score display
- Database: Potentially cached_safety_score column or separate table

### 2. Incident Heatmap Layer

**Domain:** New Analytics module or Shared extension
**New Components:**
- Backend: HeatmapService, spatial aggregation queries
- Frontend: useHeatmap composable, Leaflet.heat integration
- Database: Spatial indexing considerations

### 3. Time-based Analytics

**Domain:** New Analytics module
**New Components:**
- Backend: TemporalAnalyticsService, aggregation queries
- Frontend: AnalyticsDashboard page, chart components
- Database: No schema changes (uses existing data)

### 4. Venue Response/Claim System

**Domain:** New VenueOwners module
**New Components:**
- Backend: VenueOwner model, VenueClaim model, VenueResponse model
- Frontend: Claim flow pages, response management
- Database: New tables for owners, claims, responses

### 5. Nearby Alerts

**Domain:** New Notifications module
**New Components:**
- Backend: AlertService, notification jobs, user preferences
- Frontend: NotificationSettings component, alert displays
- Database: New tables for preferences, saved venues, alert history

---

## Decisions Made

| Decision | Rationale | Date |
|----------|-----------|------|
| Skip fake report seeding | Could defame real venues; better to let organic data accumulate | 2026-05-27 |
| Prioritise Safety Scores first | High value, low complexity, no new infrastructure | 2026-05-27 |
| Use traffic light system for scores | Clearer than stars (wrong connotation) or numeric (abstract) | 2026-05-27 |

---

## Dependencies

### Inter-Feature Dependencies

```
Venue Safety Scores ─────────────────────┐
                                         │
Time-based Analytics ────────────────────┤
                                         ├──► No dependencies
Incident Heatmap ────────────────────────┤
                                         │
Venue Response System ───► User Auth ────┤
                                         │
Nearby Alerts ───► User Auth ────────────┘
                   Notification System
```

### External Dependencies

| Feature | External Dependency |
|---------|---------------------|
| Incident Heatmap | Leaflet.heat library |
| Time-based Analytics | Chart library (e.g., Chart.js) |
| Venue Response System | User authentication (Fortify already installed) |
| Nearby Alerts | Push notification service (future), Email provider |

---

## Open Questions (To Be Resolved)

1. **Safety Scores:** What threshold defines "sufficient data"? (Proposal: 3+ reports)
2. **Heatmap:** What colour scheme is most accessible? (Proposal: green → yellow → red)
3. **Analytics:** Should temporal analytics be public or require auth?
4. **Venue Response:** Should venue owners see full report details?
5. **Alerts:** Is real-time proximity needed, or venue-based sufficient for MVP?
