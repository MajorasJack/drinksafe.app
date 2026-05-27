# React to Vue.js Migration - Context Document

**Last Updated: 2026-05-14**

---

## Overview

This document captures the critical context, decisions, and dependencies for the DrinkSafe React to Vue.js migration project. Reference this document throughout implementation to maintain consistency and understand architectural decisions.

---

## Project Context

### Application Purpose
DrinkSafe is an anonymous venue reporting platform where users can:
- Report spiking incidents at venues anonymously
- Search and view reported venues on an interactive map
- Filter reports by date, location, and time of day
- Access police contact information for proper crime reporting

**Key Principles:**
- **Anonymity:** No personal data collected (no authentication initially)
- **Informational:** Not a crime reporting platform (makes this clear to users)
- **Community-driven:** User submissions help others make informed decisions
- **Accessible:** Simple, user-friendly interface

### Migration Goals
1. Migrate existing React prototype to production-ready Vue.js application
2. Integrate with Laravel backend using modular architecture
3. Maintain design fidelity while enhancing functionality
4. Ensure security, performance, and scalability

---

## Key Files and Directories

### Source React Application
```
design_files/
├── src/src/
│   ├── pages/           # React pages (5 pages total)
│   ├── components/      # React components (layout + UI)
│   ├── context/         # React Context API (state management)
│   ├── data/            # Mock data (venues + reports)
│   └── types.ts         # TypeScript interfaces
└── package.json         # React dependencies
```

**Critical Source Files:**
- `design_files/src/src/types.ts` - Data model definitions (Venue, Report interfaces)
- `design_files/src/src/data/mockData.ts` - Mock data structure and content
- `design_files/src/src/pages/SubmitReport.tsx` - Complex multi-step form logic
- `design_files/src/src/context/ReportContext.tsx` - State management patterns
- `design_files/package.json` - Frontend dependencies to migrate

### Target Laravel Application Structure

**Modular Backend:**
```
src/DrinkSafe/
├── Venues/
│   ├── Models/Venue.php
│   ├── Controllers/VenueController.php
│   ├── Services/VenueService.php
│   ├── Policies/VenuePolicy.php
│   ├── Requests/StoreVenueRequest.php
│   ├── Resources/VenueResource.php
│   ├── Exceptions/VenueNotFoundException.php
│   └── Tests/{Feature,Unit}/
├── Reports/
│   ├── Models/Report.php
│   ├── Controllers/ReportController.php
│   ├── Services/ReportService.php
│   ├── Enums/TimeOfDay.php
│   └── Tests/{Feature,Unit}/
└── Shared/
    ├── Traits/HasUuid.php
    ├── Services/GeolocationService.php
    └── Exceptions/DrinkSafeException.php
```

**Vue.js Frontend:**
```
resources/js/
├── pages/              # Inertia.js pages (Home, Map, VenueDetail, SubmitReport, About)
├── components/         # Reusable Vue components
│   ├── layout/        # AppLayout, AppHeader, AppFooter, PoliceStrip
│   ├── venues/        # VenueCard, VenueMap, VenueSearchInput
│   ├── reports/       # ReportCard, ReportList, ReportForm
│   └── ui/            # SearchBar, DateFilter, DisclaimerBanner
├── composables/        # Vue composables (useVenues, useReports, useSearch, useMap)
├── stores/            # Pinia stores (venueStore, reportStore)
├── types/             # TypeScript types (venue.ts, report.ts, api.ts)
└── lib/               # Utility functions
```

**Configuration Files:**
- `composer.json` - PHP autoloading configuration (add `DrinkSafe\\` namespace)
- `vite.config.ts` - Frontend build configuration
- `tailwind.config.js` - Tailwind CSS configuration (already v4)
- `routes/drinksafe.php` - Module-specific routes

---

## Critical Design Decisions

### 1. Modular Architecture Pattern

**Decision:** Use `src/DrinkSafe/` namespace instead of `app/` directory

**Rationale:**
- Separation of concerns (domain-driven design)
- Easier to scale with multiple modules
- Clear ownership and boundaries
- Better testability

**Implementation:**
- Create `src/DrinkSafe/` directory structure
- Update `composer.json` PSR-4 autoloading:
  ```json
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "DrinkSafe\\": "src/DrinkSafe/"
    }
  }
  ```
- Create module subdirectories: Venues, Reports, Shared
- Each module has: Models, Controllers, Services, Policies, Requests, Resources, Exceptions, Tests

### 2. UUID Primary Keys

**Decision:** Use UUIDs instead of auto-incrementing integers

**Rationale:**
- Security: Prevents enumeration attacks
- Anonymity: Harder to correlate reports
- Distributed systems: No ID collision concerns

**Implementation:**
- Create `HasUuid` trait in `src/DrinkSafe/Shared/Traits/`
- Apply trait to Venue and Report models
- Use `Str::uuid()` for generation
- Migration: `$table->uuid('uuid')->primary();`

### 3. Service Layer Pattern

**Decision:** Business logic in services, not controllers

**Rationale:**
- Controllers are thin (routing and response formatting only)
- Services are testable in isolation
- Business logic reusable across controllers
- Easier to maintain and refactor

**Implementation:**
- Create services: `VenueService`, `VenueSearchService`, `ReportService`, `ReportModerationService`, `GeolocationService`
- Inject services into controllers via constructor
- Services throw custom exceptions (not Laravel exceptions)
- Services return domain objects (Eloquent models or collections)

### 4. Inertia.js v3 for Frontend

**Decision:** Use Inertia.js v3 instead of traditional API + SPA

**Rationale:**
- Server-side routing with client-side rendering
- No need for separate API versioning
- Built-in form handling and validation errors
- Automatic prop serialization
- SSR support out of the box

**Implementation:**
- Controllers return `Inertia::render('PageName', [...props])`
- Props are API Resources (VenueResource, ReportResource)
- Frontend uses Inertia `<Link>` for navigation
- Forms use `useForm()` for validation errors
- Wayfinder generates TypeScript route functions

### 5. Pinia for State Management

**Decision:** Use Pinia instead of Vue Context API

**Rationale:**
- Official Vue state management library
- Better TypeScript support
- Composition API syntax
- DevTools integration
- Easier to test

**Implementation:**
- Create stores: `venueStore.ts`, `reportStore.ts`
- Use Composition API syntax (`defineStore`)
- Actions fetch data from Inertia-rendered props initially
- Actions can refetch data via API endpoints if needed
- Composables (`useVenues`, `useReports`) wrap store logic

### 6. Soft Deletes for Reports

**Decision:** Soft delete reports instead of hard delete

**Rationale:**
- Moderation: Can review and restore flagged content
- Legal: Compliance with potential takedown requests
- Analytics: Track deletion patterns
- Safety: Accidental deletions recoverable

**Implementation:**
- Add `SoftDeletes` trait to Report model
- Migration: `$table->softDeletes();`
- Queries automatically exclude soft-deleted records
- Admin interface (future) can view deleted reports

### 7. PII Detection and Sanitization

**Decision:** Detect and prevent PII in report descriptions

**Rationale:**
- Privacy: Protect users from accidentally sharing personal info
- Legal: GDPR/data protection compliance
- Platform integrity: Maintain anonymous nature

**Implementation:**
- Create `ReportModerationService` with PII detection
- Regex patterns for: emails, phone numbers, full names (basic)
- Validation rule: fail if PII detected
- User-facing error: "Please remove personal information"
- Future: ML-based PII detection

### 8. Geospatial Indexing

**Decision:** Use database geospatial features for location queries

**Rationale:**
- Performance: Fast radius searches
- Accuracy: Native database calculations
- Scalability: Handle thousands of venues

**Implementation:**
- Migration: Add geospatial indexes on (latitude, longitude)
- MySQL: Use `SPATIAL INDEX` and `ST_Distance_Sphere`
- PostgreSQL: Use PostGIS extension
- Service: `GeolocationService` for Haversine calculations (fallback)
- Scope: `Venue::nearby($lat, $lng, $radiusKm)`

---

## Data Model Mapping

### Venue Model

**React Interface:**
```typescript
interface Venue {
  id: string;
  name: string;
  city: string;
  address: string;
  lat: number;
  lng: number;
}
```

**Laravel Model:**
```php
// src/DrinkSafe/Venues/Models/Venue.php
namespace DrinkSafe\Venues\Models;

class Venue extends Model
{
    use HasFactory, HasUuid;

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
}
```

**Database Schema:**
```sql
CREATE TABLE venues (
    uuid VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    address VARCHAR(500),
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_city (city),
    INDEX idx_name (name),
    INDEX idx_location (latitude, longitude),
    INDEX idx_city_name (city, name),
    FULLTEXT INDEX idx_fulltext_name (name)
);
```

### Report Model

**React Interface:**
```typescript
interface Report {
  id: string;
  venueId: string;
  date: string; // ISO string
  timeOfDay: 'Morning' | 'Afternoon' | 'Evening' | 'Night' | 'Unknown';
  description: string;
  createdAt: string; // ISO string
}
```

**Laravel Model:**
```php
// src/DrinkSafe/Reports/Models/Report.php
namespace DrinkSafe\Reports\Models;

class Report extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'venue_uuid',
        'incident_date',
        'time_of_day',
        'description',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'time_of_day' => TimeOfDay::class,
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_uuid');
    }
}
```

**Database Schema:**
```sql
CREATE TABLE reports (
    uuid VARCHAR(36) PRIMARY KEY,
    venue_uuid VARCHAR(36) NOT NULL,
    incident_date DATE NOT NULL,
    time_of_day ENUM('Morning', 'Afternoon', 'Evening', 'Night', 'Unknown') NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    FOREIGN KEY (venue_uuid) REFERENCES venues(uuid) ON DELETE CASCADE,
    INDEX idx_venue_date (venue_uuid, incident_date DESC),
    INDEX idx_deleted_created (deleted_at, created_at DESC),
    INDEX idx_time_of_day (time_of_day),
    FULLTEXT INDEX idx_fulltext_description (description)
);
```

---

## API Endpoint Structure

### Public API Endpoints (JSON Responses)

**Venues:**
- `GET /api/venues` - List all venues with report counts
- `GET /api/venues/search?q={query}&city={city}&lat={lat}&lng={lng}&radius={radius}` - Search venues
- `GET /api/venues/{uuid}` - Get single venue with reports

**Reports:**
- `GET /api/reports?venue_uuid={uuid}&start_date={date}&end_date={date}&time_of_day={time}` - List reports with filters
- `GET /api/reports/{uuid}` - Get single report
- `POST /api/reports` - Submit new report (with venue creation if needed)

### Page Routes (Inertia.js Responses)

**Pages:**
- `GET /` - Home page (recent reports)
- `GET /map` - Map page (all venues + reports with filters)
- `GET /venue/{uuid}` - Venue detail page (single venue + all its reports)
- `GET /report` - Submit report page (form)
- `GET /about` - About page (static content)

---

## Dependencies and External Services

### Backend Dependencies (Laravel)
```json
{
  "php": "^8.3",
  "laravel/framework": "^13.7",
  "inertiajs/inertia-laravel": "^3.0",
  "laravel/fortify": "^1.34",
  "laravel/wayfinder": "^0.1.14"
}
```

### Frontend Dependencies (Vue.js)
```json
{
  "vue": "^3.x",
  "@inertiajs/vue3": "^3.x",
  "pinia": "^2.x",
  "vue-leaflet": "^4.x",
  "@vue-leaflet/vue-leaflet": "latest",
  "leaflet": "^1.9.4",
  "motion-vue": "^1.x",
  "tailwindcss": "^4.x",
  "@vueuse/core": "^11.x",
  "date-fns": "^4.x",
  "lucide-vue-next": "latest",
  "sonner-vue": "latest"
}
```

### Map Tiles Provider
- **Default:** OpenStreetMap (free, no API key required)
- **Alternative:** Mapbox (better performance, requires API key)
- **Configuration:** `resources/js/lib/leaflet.ts`

---

## Testing Strategy

### Backend Testing (Pest)

**Unit Tests:**
- Location: `src/DrinkSafe/{Module}/Tests/Unit/`
- Target: Models, Services, Scopes, Helpers
- Coverage: >90%

**Feature Tests:**
- Location: `src/DrinkSafe/{Module}/Tests/Feature/`
- Target: Controllers, API endpoints, Inertia pages
- Coverage: All endpoints

**Test Conventions:**
- Use `it()` for test descriptions
- Use AAA pattern (Arrange-Act-Assert)
- Use factories for model creation
- Use `fake()` for randomized data
- Use JSON test methods (`getJson`, `postJson`)
- Use response helpers (`assertOk`, `assertNotFound`)

### Frontend Testing

**Component Tests (Optional):**
- Tool: Vitest + Vue Test Utils
- Target: Complex components (form steps, map interactions)

**E2E Tests:**
- Tool: Laravel Dusk or Playwright
- Target: Critical user flows (search, submit report, view venue)
- Location: `tests/Browser/DrinkSafe/`

---

## Security Considerations

### Authentication
- **Current:** No authentication (fully anonymous)
- **Future:** Admin authentication for moderation (Laravel Fortify)

### Authorization
- **Current:** All endpoints public (read + report submission)
- **Future:** Policies for admin actions (delete reports, ban venues)

### Input Validation
- **FormRequests:** All user input validated
- **PII Detection:** Email, phone, names detected and rejected
- **Rate Limiting:** Report submission limited to 5 per hour per IP
- **SQL Injection:** Prevented by Eloquent ORM (no raw queries)
- **XSS:** Prevented by Vue.js auto-escaping (use `v-html` with caution)
- **CSRF:** Laravel CSRF tokens on all POST requests

### Data Privacy
- **No PII:** Platform designed to avoid collecting personal data
- **Soft Deletes:** Reports can be removed on request
- **Anonymity:** UUIDs prevent correlation of reports

---

## Performance Optimization Strategies

### Database
- Index all foreign keys and filter columns
- Use eager loading to prevent N+1 queries
- Implement full-text search for name/description search
- Use geospatial indexes for location queries

### Frontend
- Code splitting: Lazy load pages with Vite
- Image optimization: WebP format, lazy loading
- Bundle optimization: Tree-shaking, minification
- CSS optimization: Purge unused Tailwind classes

### API
- Pagination: Limit results to 50 per page (configurable)
- Caching: Redis cache for venue list (5 min TTL)
- Rate limiting: Prevent abuse

### Map
- Marker clustering: Group markers when >50 visible
- Lazy loading: Load marker data as user pans
- Debounced filters: Wait 300ms after user stops typing

---

## Development Workflow

### Branch Strategy
- `main` - Production-ready code
- `develop` - Integration branch
- `feature/{phase-name}` - Feature branches per phase

### Commit Messages
- Follow Conventional Commits format
- Examples: `feat: add venue search service`, `fix: report validation error`, `test: add venue controller tests`

### Code Review Checklist
- [ ] Code follows Laravel/Vue best practices
- [ ] Tests written and passing
- [ ] No linting errors (Pint, ESLint)
- [ ] Documentation updated
- [ ] No security vulnerabilities
- [ ] Performance considerations addressed

### Phase Sign-off Process
1. Complete all tasks in phase
2. Run all tests (`pa test --coverage`, `npm run test`)
3. Run linters (`vendor/bin/pint`, `npm run lint`)
4. Manual QA testing
5. Review checklist completed
6. Sign-off recorded in plan document

---

## Known Limitations and Future Enhancements

### Current Limitations
- No authentication (anonymous only)
- No report moderation interface (auto-moderation only)
- No report editing/deletion (submit once)
- No email notifications (no user accounts)
- Basic PII detection (regex-based)

### Future Enhancements
- Admin dashboard for moderation
- User accounts with saved searches
- Email alerts for new reports in followed venues
- ML-based PII detection
- Report verification system (crowd-sourced)
- API for third-party integrations
- Mobile app (React Native or Flutter)

---

## Troubleshooting Guide

### Common Issues

**Issue:** Namespace not found after creating `src/DrinkSafe/`
- **Solution:** Run `composer dump-autoload`

**Issue:** Inertia props not received in Vue component
- **Solution:** Check controller returns `Inertia::render()` with props array

**Issue:** Leaflet map not rendering
- **Solution:** Import Leaflet CSS in `app.ts`, fix default marker icons

**Issue:** Validation errors not displayed in form
- **Solution:** Use Inertia `useForm()` and access `form.errors`

**Issue:** N+1 queries detected
- **Solution:** Add eager loading in controller: `->with('reports')`

**Issue:** Tests failing with database errors
- **Solution:** Ensure test database configured, run migrations in test environment

---

## Contact and Resources

### Documentation
- Laravel 13: https://laravel.com/docs/13.x
- Inertia.js v3: https://inertiajs.com/
- Vue 3: https://vuejs.org/guide/
- Pinia: https://pinia.vuejs.org/
- Leaflet: https://leafletjs.com/
- Tailwind CSS v4: https://tailwindcss.com/

### Project-Specific Docs
- `docs/architecture/` - Architecture decisions
- `docs/api/` - API documentation
- `docs/frontend/` - Component library
- `docs/deployment/` - Deployment guides

---

**Document Status:** Living Document (update as decisions are made)
**Created:** 2026-05-14
**Last Updated:** 2026-05-14
