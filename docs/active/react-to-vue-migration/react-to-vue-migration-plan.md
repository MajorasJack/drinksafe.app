# React to Vue.js Migration Plan - DrinkSafe Application

**Last Updated: 2026-05-14**

---

## Executive Summary

This plan outlines the comprehensive migration of the DrinkSafe anonymous venue reporting application from React to Vue.js with Inertia.js v3, integrating it with a modular Laravel 13 backend architecture. The project will transform a standalone React prototype into a fully integrated, production-ready Laravel + Vue.js application following modern best practices and modular architecture patterns.

### Key Objectives

1. **Migrate frontend** from React to Vue 3 with Composition API and TypeScript
2. **Implement modular Laravel backend** structure (not monolithic `app/` directory)
3. **Create full-stack integration** using Inertia.js v3 for seamless SPA experience
4. **Build robust data layer** with migrations, models, factories, and seeders
5. **Ensure comprehensive test coverage** for all critical functionality
6. **Maintain feature parity** with existing React design while enhancing UX

### Success Metrics

- 100% feature parity with React prototype
- Full test coverage (Feature + Unit tests) for backend
- E2E test coverage for critical user flows
- Zero security vulnerabilities (OWASP Top 10 compliance)
- Performance: <100ms API response times, <2s page loads
- Modular architecture with clear separation of concerns
- Production-ready deployment capability

---

## Current State Analysis

### Existing React Application

**Technology Stack:**
- React 18.3.1 with TypeScript
- React Router DOM for navigation
- Framer Motion for animations
- React Leaflet for map functionality
- Tailwind CSS 3.4.17 for styling
- Lucide React for icons
- Sonner for toast notifications
- Date-fns for date formatting

**Application Structure:**
```
design_files/
├── src/
│   ├── pages/
│   │   ├── Home.tsx (hero section, search, recent reports)
│   │   ├── MapPage.tsx (interactive map with markers)
│   │   ├── VenueDetail.tsx (venue information + reports)
│   │   ├── SubmitReport.tsx (multi-step form)
│   │   └── About.tsx (information page)
│   ├── components/
│   │   ├── Layout.tsx (main layout wrapper)
│   │   ├── Header.tsx (navigation)
│   │   ├── Footer.tsx (site footer)
│   │   ├── PoliceStrip.tsx (police contact banner)
│   │   └── DisclaimerBanner.tsx (legal disclaimer)
│   ├── context/
│   │   └── ReportContext.tsx (global state management)
│   └── data/
│       └── mockData.ts (venues + reports mock data)
```

**Core Features Identified:**
1. **Home Page**: Hero section with search, recent reports display, "how it works" section
2. **Map Page**: Interactive Leaflet map showing venue markers with filtering
3. **Venue Detail**: Individual venue page with all associated reports
4. **Submit Report**: Multi-step form (location/time → details → success)
5. **About Page**: Information about the platform and police contacts
6. **Search**: By city, town, or venue name
7. **Filtering**: By date/time ranges
8. **Police Contact Strip**: Persistent across pages

**Data Models Identified:**
```typescript
interface Venue {
  id: string;
  name: string;
  city: string;
  address: string;
  lat: number;
  lng: number;
}

interface Report {
  id: string;
  venueId: string;
  date: string; // ISO string
  timeOfDay: 'Morning' | 'Afternoon' | 'Evening' | 'Night' | 'Unknown';
  description: string;
  createdAt: string; // ISO string
}
```

### Current Laravel Application

**Technology Stack:**
- Laravel 13.7 with PHP 8.3
- Inertia.js v3 Laravel adapter
- Laravel Fortify for authentication
- Laravel Wayfinder for typed routes
- Pest v4 for testing
- Vue 3 (basic setup already exists)

**Existing Structure:**
```
app/
├── Providers/
├── Models/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Actions/
└── Concerns/

resources/js/
├── pages/
├── components/
├── layouts/
├── composables/
├── types/
└── actions/
```

**Current Capabilities:**
- Authentication system (Fortify)
- Settings/profile management
- Inertia.js integration
- TypeScript support
- Tailwind CSS v4 configured

---

## Proposed Future State

### Target Architecture

**Modular Laravel Backend Structure:**
```
src/
├── DrinkSafe/
│   ├── Venues/
│   │   ├── Models/
│   │   │   └── Venue.php
│   │   ├── Controllers/
│   │   │   ├── VenueController.php
│   │   │   └── VenueSearchController.php
│   │   ├── Services/
│   │   │   ├── VenueService.php
│   │   │   └── VenueSearchService.php
│   │   ├── Policies/
│   │   │   └── VenuePolicy.php
│   │   ├── Requests/
│   │   │   ├── StoreVenueRequest.php
│   │   │   └── UpdateVenueRequest.php
│   │   ├── Resources/
│   │   │   ├── VenueResource.php
│   │   │   └── VenueCollection.php
│   │   ├── Exceptions/
│   │   │   ├── VenueNotFoundException.php
│   │   │   └── VenueDuplicateException.php
│   │   └── Tests/
│   │       ├── Feature/
│   │       └── Unit/
│   ├── Reports/
│   │   ├── Models/
│   │   │   └── Report.php
│   │   ├── Controllers/
│   │   │   ├── ReportController.php
│   │   │   └── ReportFilterController.php
│   │   ├── Services/
│   │   │   ├── ReportService.php
│   │   │   └── ReportModerationService.php
│   │   ├── Policies/
│   │   │   └── ReportPolicy.php
│   │   ├── Requests/
│   │   │   └── StoreReportRequest.php
│   │   ├── Resources/
│   │   │   ├── ReportResource.php
│   │   │   └── ReportCollection.php
│   │   ├── Enums/
│   │   │   └── TimeOfDay.php
│   │   ├── Exceptions/
│   │   │   ├── ReportNotFoundException.php
│   │   │   └── ReportValidationException.php
│   │   └── Tests/
│   │       ├── Feature/
│   │       └── Unit/
│   └── Shared/
│       ├── Traits/
│       │   └── HasUuid.php
│       ├── Services/
│       │   └── GeolocationService.php
│       └── Exceptions/
│           └── DrinkSafeException.php
```

**Vue.js Frontend Structure:**
```
resources/js/
├── pages/
│   ├── Home.vue
│   ├── Map.vue
│   ├── VenueDetail.vue
│   ├── SubmitReport.vue
│   └── About.vue
├── components/
│   ├── layout/
│   │   ├── AppLayout.vue
│   │   ├── AppHeader.vue
│   │   ├── AppFooter.vue
│   │   └── PoliceStrip.vue
│   ├── venues/
│   │   ├── VenueCard.vue
│   │   ├── VenueMap.vue
│   │   └── VenueSearchInput.vue
│   ├── reports/
│   │   ├── ReportCard.vue
│   │   ├── ReportForm.vue
│   │   └── ReportList.vue
│   └── ui/
│       ├── DisclaimerBanner.vue
│       ├── SearchBar.vue
│       └── DateFilter.vue
├── composables/
│   ├── useVenues.ts
│   ├── useReports.ts
│   ├── useSearch.ts
│   ├── useMap.ts
│   └── useFilters.ts
├── stores/
│   ├── venueStore.ts
│   └── reportStore.ts
├── types/
│   ├── venue.ts
│   ├── report.ts
│   └── api.ts
└── lib/
    ├── leaflet.ts
    └── utils.ts
```

### Technology Decisions

**Frontend:**
- Vue 3 with Composition API and `<script setup>` syntax
- TypeScript for type safety
- Pinia for state management (replacing Context API)
- Vue Leaflet for maps
- Tailwind CSS v4 (already configured)
- VueUse composables for reusable logic
- Framer Motion Vue (motion-vue) for animations

**Backend:**
- Modular architecture in `src/` namespace
- Service layer pattern for business logic
- Repository pattern where beneficial
- Policy-based authorization
- FormRequest validation
- API Resources for consistent JSON responses
- UUID primary keys for security

**Database:**
- PostgreSQL or MySQL (configurable)
- Proper indexing for search performance
- Soft deletes for reports
- Full-text search capabilities
- Geospatial indexes for location queries

---

## Implementation Phases

The implementation is structured into **9 major phases**, grouped by agent specialization for efficient parallel execution where possible.

---

## Phase 1: Project Setup & Architecture Foundation

**Primary Agent:** `technical-architect`
**Skills to Load:** N/A (exploration-based)
**Duration:** ~1 day
**Dependencies:** None

### Objectives
- Establish modular project structure
- Configure namespace autoloading
- Set up development environment
- Document architectural decisions

### Tasks

#### 1.1 Create Modular Directory Structure
- [ ] Create `src/DrinkSafe/` base directory
- [ ] Create `Venues/` module with subdirectories (Models, Controllers, Services, Policies, Requests, Resources, Exceptions, Tests)
- [ ] Create `Reports/` module with subdirectories (Models, Controllers, Services, Policies, Requests, Resources, Enums, Exceptions, Tests)
- [ ] Create `Shared/` module with subdirectories (Traits, Services, Exceptions)
- [ ] Update `.gitignore` if needed

**Acceptance Criteria:**
- All module directories exist
- Consistent structure across modules
- README.md in each module explaining its purpose

#### 1.2 Configure PSR-4 Autoloading
- [ ] Update `composer.json` to include `DrinkSafe\\` namespace mapping to `src/DrinkSafe/`
- [ ] Run `composer dump-autoload`
- [ ] Test namespace autoloading with a dummy class
- [ ] Update `phpunit.xml` to include new test directories

**Acceptance Criteria:**
- `composer dump-autoload` succeeds
- New namespace resolves correctly
- Tests can be discovered in new locations

#### 1.3 Document Architecture Decisions
- [ ] Create `docs/architecture/modular-structure.md` documenting module organization
- [ ] Create `docs/architecture/naming-conventions.md`
- [ ] Create `docs/architecture/data-flow.md` explaining request → controller → service → repository flow
- [ ] Update `CLAUDE.md` with project-specific patterns

**Acceptance Criteria:**
- Documentation is clear and actionable
- Examples provided for each pattern
- Team can reference docs for implementation

#### 1.4 Development Environment Configuration
- [ ] Verify Tailwind CSS v4 configuration
- [ ] Verify Inertia.js v3 configuration
- [ ] Install Vue Leaflet: `npm install vue-leaflet @vue-leaflet/vue-leaflet`
- [ ] Install Motion Vue: `npm install motion-vue`
- [ ] Install Pinia: `npm install pinia`
- [ ] Configure Vite for proper alias resolution (`@/` → `resources/js/`)
- [ ] Test `npm run dev` and `composer run dev` work correctly

**Acceptance Criteria:**
- All dependencies installed without conflicts
- Development servers start successfully
- Hot module replacement works
- No console errors on empty page load

### Phase 1 Review Checkpoint

**Before proceeding to Phase 2, complete these reviews:**

- [ ] Architecture documentation reviewed and approved
- [ ] Directory structure follows modular conventions
- [ ] Autoloading tested and working
- [ ] Development environment fully operational
- [ ] All Critical/High issues resolved

**Phase 1 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 2: Database Schema & Migrations

**Primary Agent:** `laravel-backend-developer`
**Skills to Load:** `laravel-backend-guidelines`, `pest-testing`
**Duration:** ~1 day
**Dependencies:** Phase 1

### Objectives
- Design normalized database schema
- Create migrations with proper indexes
- Ensure data integrity with foreign keys and constraints
- Plan for performance with geospatial and full-text indexes

### Tasks

#### 2.1 Create Venues Table Migration
- [ ] Run `pa make:migration create_venues_table --path=database/migrations/drinksafe`
- [ ] Define schema:
  - `uuid` (primary key, unique, indexed)
  - `name` (string, 255, indexed for search)
  - `city` (string, 100, indexed for filtering)
  - `address` (string, 500, nullable)
  - `latitude` (decimal 10,8, indexed)
  - `longitude` (decimal 11,8, indexed)
  - `created_at`, `updated_at` (timestamps)
- [ ] Add composite index: `(city, name)` for common search patterns
- [ ] Add geospatial index for location-based queries
- [ ] Add full-text index on `name` for search

**Acceptance Criteria:**
- Migration file created in correct path
- All indexes defined
- No performance warnings from `EXPLAIN` queries
- Migration runs successfully: `pa migrate --path=database/migrations/drinksafe`

#### 2.2 Create Reports Table Migration
- [ ] Run `pa make:migration create_reports_table --path=database/migrations/drinksafe`
- [ ] Define schema:
  - `uuid` (primary key, unique, indexed)
  - `venue_uuid` (foreign key to venues.uuid, indexed, cascading delete)
  - `incident_date` (date, indexed for filtering)
  - `time_of_day` (enum: Morning, Afternoon, Evening, Night, Unknown, indexed)
  - `description` (text, full-text indexed)
  - `created_at`, `updated_at`, `deleted_at` (timestamps, soft deletes)
- [ ] Add foreign key constraint: `venue_uuid` → `venues.uuid` ON DELETE CASCADE
- [ ] Add composite index: `(venue_uuid, incident_date DESC)` for venue detail page
- [ ] Add composite index: `(deleted_at, created_at DESC)` for recent reports query
- [ ] Add full-text index on `description` for content search

**Acceptance Criteria:**
- Migration file created
- Foreign key constraint enforces referential integrity
- Soft deletes enabled
- All indexes optimize common queries
- Migration runs successfully

#### 2.3 Create Database Seeder for Development
- [ ] Create `database/seeders/DrinkSafe/VenueSeeder.php`
- [ ] Create `database/seeders/DrinkSafe/ReportSeeder.php`
- [ ] Seed 50 diverse venues across UK cities
- [ ] Seed 200+ reports with realistic data
- [ ] Use `fake()` for randomized, realistic data
- [ ] Include various time periods (recent to 6 months old)
- [ ] Update `DatabaseSeeder.php` to call DrinkSafe seeders

**Acceptance Criteria:**
- Seeders populate database with realistic data
- Data is diverse and representative
- `pa db:seed` works without errors
- Seeded data suitable for frontend testing

#### 2.4 Test Database Schema
- [ ] Create `src/DrinkSafe/Venues/Tests/Unit/VenueSchemaTest.php`
- [ ] Test: venue table exists with correct columns
- [ ] Test: indexes exist and are correct type
- [ ] Test: foreign keys enforce constraints
- [ ] Create `src/DrinkSafe/Reports/Tests/Unit/ReportSchemaTest.php`
- [ ] Test: report table exists with correct columns
- [ ] Test: soft deletes work correctly
- [ ] Test: cascade delete works (delete venue → reports deleted)
- [ ] Run tests: `pa test --filter=Schema`

**Acceptance Criteria:**
- All schema tests pass
- Foreign key constraints tested
- Indexes verified
- Test coverage for schema structure

### Phase 2 Review Checkpoint

**Before proceeding to Phase 3, complete these reviews:**

- [ ] Database schema reviewed for normalization
- [ ] Indexes optimise identified query patterns
- [ ] Foreign key constraints enforce data integrity
- [ ] Seeders produce realistic test data
- [ ] All schema tests pass
- [ ] All Critical/High issues resolved

**Phase 2 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 3: Backend Models & Relationships

**Primary Agent:** `laravel-backend-developer`
**Skills to Load:** `laravel-backend-guidelines`, `pest-testing`
**Duration:** ~1 day
**Dependencies:** Phase 2

### Objectives
- Create Eloquent models with proper traits and relationships
- Implement factories for testing
- Define model policies for authorization
- Ensure type safety with docblocks and casts

### Tasks

#### 3.1 Create Venue Model
- [ ] Run `pa make:model --no-migration src/DrinkSafe/Venues/Models/Venue`
- [ ] Add traits: `HasFactory`, `HasUuid`
- [ ] Define `$fillable`: `['name', 'city', 'address', 'latitude', 'longitude']`
- [ ] Define `$casts`: `['latitude' => 'decimal:8', 'longitude' => 'decimal:8']`
- [ ] Define relationship: `reports()` → hasMany(Report)
- [ ] Add scope: `scopeNearby($query, $lat, $lng, $radiusKm)` for geospatial queries
- [ ] Add scope: `scopeInCity($query, $city)` for filtering
- [ ] Add scope: `scopeSearch($query, $term)` for name/city search
- [ ] Add comprehensive docblocks for all methods and properties
- [ ] Create `database/factories/DrinkSafe/VenueFactory.php`

**Acceptance Criteria:**
- Model uses UUID as primary key
- Relationships defined correctly
- Scopes tested and working
- Factory generates realistic venue data
- Docblocks describe all properties and methods

#### 3.2 Create Report Model
- [ ] Run `pa make:model --no-migration src/DrinkSafe/Reports/Models/Report`
- [ ] Add traits: `HasFactory`, `SoftDeletes`, `HasUuid`
- [ ] Define `$fillable`: `['venue_uuid', 'incident_date', 'time_of_day', 'description']`
- [ ] Define `$casts`: `['incident_date' => 'date', 'time_of_day' => TimeOfDay::class]`
- [ ] Define relationship: `venue()` → belongsTo(Venue, 'venue_uuid')
- [ ] Add scope: `scopeRecent($query, $days = 30)` for time filtering
- [ ] Add scope: `scopeByTimeOfDay($query, $timeOfDay)` for filtering
- [ ] Add accessor: `getFormattedDateAttribute()` for UI display
- [ ] Add comprehensive docblocks
- [ ] Create `database/factories/DrinkSafe/ReportFactory.php`

**Acceptance Criteria:**
- Model uses UUID and soft deletes
- Belongs to venue relationship works
- Enum casting works for time_of_day
- Scopes filter correctly
- Factory generates realistic reports

#### 3.3 Create TimeOfDay Enum
- [ ] Create `src/DrinkSafe/Reports/Enums/TimeOfDay.php`
- [ ] Define cases: `Morning`, `Afternoon`, `Evening`, `Night`, `Unknown`
- [ ] Implement `BackedEnum` with string values
- [ ] Add method: `label()` for human-readable display
- [ ] Add method: `icon()` for UI icon names (optional)
- [ ] Add comprehensive docblock

**Acceptance Criteria:**
- Enum properly backed by strings
- All cases defined
- Helper methods work
- Can be cast in Eloquent models

#### 3.4 Create Shared UUID Trait
- [ ] Create `src/DrinkSafe/Shared/Traits/HasUuid.php`
- [ ] Override `getKeyType()` to return `'string'`
- [ ] Override `getIncrementing()` to return `false`
- [ ] Add boot method to auto-generate UUID on creation
- [ ] Use `Str::uuid()` for generation
- [ ] Add comprehensive docblock

**Acceptance Criteria:**
- Trait generates UUIDs automatically
- Models using trait have string primary keys
- UUID format validated

#### 3.5 Create Model Factories
- [ ] Create `database/factories/DrinkSafe/VenueFactory.php`
  - Generate realistic venue names
  - Use real UK cities
  - Generate valid coordinates within UK bounds
  - Provide factory states: `london()`, `manchester()`, etc.
- [ ] Create `database/factories/DrinkSafe/ReportFactory.php`
  - Generate realistic descriptions using `fake()->paragraph()`
  - Random incident dates within past 6 months
  - Random time_of_day values
  - Associate with venue factory
  - Provide factory states: `recent()`, `old()`, `night()`, `morning()`

**Acceptance Criteria:**
- Factories generate realistic, randomized data
- Factory states work correctly
- Can create models with `ModelName::factory()->create()`
- Factories follow AAA pattern in tests

#### 3.6 Unit Test Models & Relationships
- [ ] Create `src/DrinkSafe/Venues/Tests/Unit/VenueTest.php`
  - Test: venue has UUID primary key
  - Test: venue.reports() relationship returns collection
  - Test: scopeInCity() filters correctly
  - Test: scopeSearch() finds by name and city
  - Test: factory creates valid venue
- [ ] Create `src/DrinkSafe/Reports/Tests/Unit/ReportTest.php`
  - Test: report has UUID primary key
  - Test: report.venue() relationship returns venue
  - Test: soft delete works
  - Test: scopeRecent() filters by date
  - Test: time_of_day casts to enum
  - Test: factory creates valid report
- [ ] Run tests: `pa test --filter=Venue` and `pa test --filter=Report`

**Acceptance Criteria:**
- All model tests pass
- Relationships tested bidirectionally
- Scopes tested with assertions
- Factory tests verify data validity
- 100% code coverage for model classes

### Phase 3 Review Checkpoint

**Before proceeding to Phase 4, complete these reviews:**

- [ ] Models follow Laravel conventions
- [ ] Relationships tested and working
- [ ] Factories generate valid test data
- [ ] Docblocks comprehensive and accurate
- [ ] All unit tests pass
- [ ] All Critical/High issues resolved

**Phase 3 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 4: Backend Services & Business Logic

**Primary Agent:** `laravel-backend-developer`
**Skills to Load:** `laravel-backend-guidelines`, `pest-testing`
**Duration:** ~2 days
**Dependencies:** Phase 3

### Objectives
- Implement service layer for business logic
- Create search and filter services
- Implement geolocation services
- Add report moderation/validation logic
- Ensure all services are testable and tested

### Tasks

#### 4.1 Create VenueService
- [ ] Create `src/DrinkSafe/Venues/Services/VenueService.php`
- [ ] Method: `getAllVenues(): Collection` - retrieve all venues with report counts
- [ ] Method: `getVenueById(string $uuid): Venue` - retrieve single venue with reports
- [ ] Method: `createVenue(array $data): Venue` - create new venue with validation
- [ ] Method: `updateVenue(string $uuid, array $data): Venue` - update venue
- [ ] Method: `deleteVenue(string $uuid): bool` - soft delete venue
- [ ] Method: `getVenueReports(string $venueUuid, int $limit = 50): Collection` - paginated reports
- [ ] Throw custom exceptions: `VenueNotFoundException`, `VenueDuplicateException`
- [ ] Add comprehensive docblocks and return types

**Acceptance Criteria:**
- All methods have typehints and return types
- Custom exceptions thrown for error cases
- Methods are testable (dependency injection)
- Docblocks explain purpose and parameters

#### 4.2 Create VenueSearchService
- [ ] Create `src/DrinkSafe/Venues/Services/VenueSearchService.php`
- [ ] Method: `search(string $query): Collection` - full-text search by name/city
- [ ] Method: `filterByCity(string $city): Collection` - filter venues by city
- [ ] Method: `nearby(float $lat, float $lng, int $radiusKm = 10): Collection` - geospatial search
- [ ] Method: `withReportCounts(): self` - eager load report counts
- [ ] Use Eloquent scopes from Venue model
- [ ] Optimise queries with eager loading
- [ ] Add comprehensive docblocks

**Acceptance Criteria:**
- Search is case-insensitive and matches partial strings
- Nearby search uses geospatial calculations
- Query optimisation prevents N+1 problems
- All methods tested

#### 4.3 Create ReportService
- [ ] Create `src/DrinkSafe/Reports/Services/ReportService.php`
- [ ] Method: `createReport(array $data): Report` - create report with venue lookup/creation
- [ ] Method: `getReportById(string $uuid): Report` - retrieve single report
- [ ] Method: `getRecentReports(int $limit = 10): Collection` - recent reports with venue data
- [ ] Method: `filterByDateRange(Carbon $start, Carbon $end): Collection` - date filtering
- [ ] Method: `filterByTimeOfDay(TimeOfDay $timeOfDay): Collection` - time filtering
- [ ] Method: `deleteReport(string $uuid): bool` - soft delete report
- [ ] Handle venue creation if venue doesn't exist (from report form)
- [ ] Throw custom exceptions: `ReportNotFoundException`, `ReportValidationException`

**Acceptance Criteria:**
- Report creation handles new venues seamlessly
- Filters combine correctly (AND logic)
- Soft deletes work and are respected in queries
- Exceptions provide clear error messages

#### 4.4 Create ReportModerationService
- [ ] Create `src/DrinkSafe/Reports/Services/ReportModerationService.php`
- [ ] Method: `validateDescription(string $description): bool` - check for PII/offensive content
- [ ] Method: `sanitizeDescription(string $description): string` - remove/mask PII
- [ ] Method: `flagSuspiciousReport(Report $report): void` - flag for manual review (future)
- [ ] Implement basic PII detection (names, emails, phone numbers)
- [ ] Implement profanity filter (basic)
- [ ] Add comprehensive docblocks

**Acceptance Criteria:**
- PII detection catches common patterns
- Descriptions are sanitised before storage
- Service is extensible for future moderation features
- Unit tests verify detection accuracy

#### 4.5 Create GeolocationService
- [ ] Create `src/DrinkSafe/Shared/Services/GeolocationService.php`
- [ ] Method: `distance(float $lat1, float $lng1, float $lat2, float $lng2): float` - Haversine formula
- [ ] Method: `isWithinRadius(float $lat1, float $lng1, float $lat2, float $lng2, int $radiusKm): bool`
- [ ] Method: `getBoundingBox(float $lat, float $lng, int $radiusKm): array` - for query optimisation
- [ ] Static helper methods for common calculations
- [ ] Add comprehensive docblocks

**Acceptance Criteria:**
- Distance calculations accurate (compare to known values)
- Bounding box optimises database queries
- Unit tests verify geospatial math

#### 4.6 Unit Test All Services
- [ ] Create `src/DrinkSafe/Venues/Tests/Unit/VenueServiceTest.php`
  - Test: getAllVenues returns collection with report counts
  - Test: getVenueById throws exception when not found
  - Test: createVenue validates required fields
  - Test: updateVenue updates fields correctly
  - Test: deleteVenue soft deletes venue
- [ ] Create `src/DrinkSafe/Venues/Tests/Unit/VenueSearchServiceTest.php`
  - Test: search finds venues by partial name match
  - Test: filterByCity returns only matching city
  - Test: nearby returns venues within radius
- [ ] Create `src/DrinkSafe/Reports/Tests/Unit/ReportServiceTest.php`
  - Test: createReport creates report with existing venue
  - Test: createReport creates new venue if needed
  - Test: getRecentReports orders by created_at DESC
  - Test: filterByDateRange respects date boundaries
  - Test: deleteReport soft deletes
- [ ] Create `src/DrinkSafe/Reports/Tests/Unit/ReportModerationServiceTest.php`
  - Test: validateDescription detects email addresses
  - Test: validateDescription detects phone numbers
  - Test: sanitizeDescription masks PII
- [ ] Create `src/DrinkSafe/Shared/Tests/Unit/GeolocationServiceTest.php`
  - Test: distance calculates correctly (London to Manchester ≈ 260km)
  - Test: isWithinRadius returns correct boolean
  - Test: getBoundingBox returns valid coordinates
- [ ] Run all service tests: `pa test --filter=Service`

**Acceptance Criteria:**
- All service unit tests pass
- Tests use AAA pattern (Arrange-Act-Assert)
- Mocking used where appropriate for dependencies
- Edge cases tested (null values, empty strings, etc.)
- 100% code coverage for service classes

### Phase 4 Review Checkpoint

**Before proceeding to Phase 5, complete these reviews:**

- [ ] Services follow single responsibility principle
- [ ] Business logic separated from controllers
- [ ] Services are testable and tested
- [ ] Custom exceptions used appropriately
- [ ] All unit tests pass
- [ ] All Critical/High issues resolved

**Phase 4 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 5: Backend API Layer (Controllers, Requests, Resources)

**Primary Agent:** `laravel-backend-developer`
**Skills to Load:** `laravel-backend-guidelines`, `pest-testing`
**Duration:** ~2 days
**Dependencies:** Phase 4

### Objectives
- Create RESTful API controllers
- Implement FormRequest validation
- Create API Resources for consistent JSON responses
- Define routes with Wayfinder integration
- Implement policies for authorization
- Create feature tests for all endpoints

### Tasks

#### 5.1 Create FormRequests for Validation
- [ ] Create `src/DrinkSafe/Venues/Requests/StoreVenueRequest.php`
  - Validate: `name` (required, string, max:255)
  - Validate: `city` (required, string, max:100)
  - Validate: `address` (nullable, string, max:500)
  - Validate: `latitude` (required, numeric, between:-90,90)
  - Validate: `longitude` (required, numeric, between:-180,180)
  - Custom rule: check for duplicate venue (same name + city)
- [ ] Create `src/DrinkSafe/Venues/Requests/UpdateVenueRequest.php`
  - Same rules as Store but all fields optional except UUID
- [ ] Create `src/DrinkSafe/Reports/Requests/StoreReportRequest.php`
  - Validate: `venue_uuid` (nullable, exists:venues,uuid) OR `venue_name` + `venue_city`
  - Validate: `incident_date` (required, date, before_or_equal:today)
  - Validate: `time_of_day` (required, in:TimeOfDay enum values)
  - Validate: `description` (required, string, min:20, max:1000)
  - Custom rule: PII detection (fail if email/phone detected)
- [ ] Add custom error messages for better UX

**Acceptance Criteria:**
- Validation rules comprehensive and restrictive
- Custom error messages clear and actionable
- FormRequests return 422 with validation errors
- All validation rules unit tested

#### 5.2 Create API Resources
- [ ] Create `src/DrinkSafe/Venues/Resources/VenueResource.php`
  - Return: `uuid`, `name`, `city`, `address`, `latitude`, `longitude`, `reports_count`, `created_at`
  - Conditional: include `reports` collection if loaded
- [ ] Create `src/DrinkSafe/Venues/Resources/VenueCollection.php`
  - Paginated collection wrapper
  - Include meta: total, per_page, current_page
- [ ] Create `src/DrinkSafe/Reports/Resources/ReportResource.php`
  - Return: `uuid`, `incident_date`, `time_of_day`, `description`, `created_at`
  - Conditional: include `venue` resource if loaded
  - Format dates using `carbon` helper
- [ ] Create `src/DrinkSafe/Reports/Resources/ReportCollection.php`
  - Paginated collection wrapper

**Acceptance Criteria:**
- Resources return consistent JSON structure
- Conditional loading works (avoid N+1)
- Dates formatted consistently (ISO 8601)
- Collections include pagination metadata

#### 5.3 Create VenueController
- [ ] Create `src/DrinkSafe/Venues/Controllers/VenueController.php`
- [ ] Method: `index(Request $request): JsonResponse` - list all venues with optional city filter
- [ ] Method: `show(string $uuid): JsonResponse` - single venue with reports
- [ ] Method: `store(StoreVenueRequest $request): JsonResponse` - create venue (admin only, future)
- [ ] Inject `VenueService` in constructor
- [ ] Return API Resources for all responses
- [ ] Handle exceptions and return appropriate HTTP status codes
- [ ] Use Response constants (e.g., `Response::HTTP_OK`, `Response::HTTP_NOT_FOUND`)

**Acceptance Criteria:**
- All methods return JSON responses
- Exceptions caught and returned as JSON errors
- Service layer called for business logic
- No business logic in controller methods

#### 5.4 Create VenueSearchController
- [ ] Create `src/DrinkSafe/Venues/Controllers/VenueSearchController.php`
- [ ] Method: `search(Request $request): JsonResponse` - search venues by query string
  - Query param: `q` (search term)
  - Query param: `city` (optional filter)
  - Query param: `lat`, `lng`, `radius` (optional geospatial)
- [ ] Inject `VenueSearchService` in constructor
- [ ] Validate query parameters
- [ ] Return `VenueCollection` resource

**Acceptance Criteria:**
- Search handles empty query gracefully
- Multiple filters work together (AND logic)
- Results ordered by relevance
- Response includes result count

#### 5.5 Create ReportController
- [ ] Create `src/DrinkSafe/Reports/Controllers/ReportController.php`
- [ ] Method: `index(Request $request): JsonResponse` - list reports with filters
  - Query param: `venue_uuid` (filter by venue)
  - Query param: `start_date`, `end_date` (date range)
  - Query param: `time_of_day` (filter by time)
  - Query param: `limit` (default 50, max 100)
- [ ] Method: `show(string $uuid): JsonResponse` - single report
- [ ] Method: `store(StoreReportRequest $request): JsonResponse` - create report
  - Handle venue creation if venue doesn't exist
  - Sanitise description using `ReportModerationService`
- [ ] Inject `ReportService` and `ReportModerationService` in constructor
- [ ] Return `ReportResource` and `ReportCollection`

**Acceptance Criteria:**
- Report submission works with existing or new venues
- Description sanitised before storage
- Filters work independently and combined
- Validation errors clear and actionable

#### 5.6 Define Routes
- [ ] Create `routes/drinksafe.php` for module routes
- [ ] Define routes:
  ```php
  Route::prefix('api/venues')->group(function () {
      Route::get('/', [VenueController::class, 'index'])->name('venues.index');
      Route::get('/search', [VenueSearchController::class, 'search'])->name('venues.search');
      Route::get('/{uuid}', [VenueController::class, 'show'])->name('venues.show');
  });

  Route::prefix('api/reports')->group(function () {
      Route::get('/', [ReportController::class, 'index'])->name('reports.index');
      Route::get('/{uuid}', [ReportController::class, 'show'])->name('reports.show');
      Route::post('/', [ReportController::class, 'store'])->name('reports.store');
  });
  ```
- [ ] Include routes in `routes/web.php`: `require __DIR__.'/drinksafe.php';`
- [ ] Run `pa route:list` to verify routes registered
- [ ] Run `pa wayfinder:generate` to generate TypeScript route functions

**Acceptance Criteria:**
- All routes registered and accessible
- Route names follow convention
- Wayfinder generates TypeScript functions
- No route conflicts

#### 5.7 Create Policies (Future Authorization)
- [ ] Create `src/DrinkSafe/Venues/Policies/VenuePolicy.php` (placeholder for future admin features)
- [ ] Create `src/DrinkSafe/Reports/Policies/ReportPolicy.php` (placeholder)
- [ ] Register policies in `AuthServiceProvider`

**Acceptance Criteria:**
- Policies created for future use
- Registered in service provider
- Basic structure in place

#### 5.8 Feature Test All API Endpoints
- [ ] Create `src/DrinkSafe/Venues/Tests/Feature/VenueControllerTest.php`
  - Test: `GET /api/venues` returns JSON with venues
  - Test: `GET /api/venues?city=London` filters by city
  - Test: `GET /api/venues/{uuid}` returns single venue with reports
  - Test: `GET /api/venues/{invalid}` returns 404
- [ ] Create `src/DrinkSafe/Venues/Tests/Feature/VenueSearchControllerTest.php`
  - Test: `GET /api/venues/search?q=neon` finds matching venues
  - Test: `GET /api/venues/search?lat=51.5&lng=-0.1&radius=10` returns nearby venues
  - Test: `GET /api/venues/search?q=&city=` handles empty query
- [ ] Create `src/DrinkSafe/Reports/Tests/Feature/ReportControllerTest.php`
  - Test: `GET /api/reports` returns recent reports
  - Test: `GET /api/reports?venue_uuid={uuid}` filters by venue
  - Test: `GET /api/reports?start_date=2024-01-01&end_date=2024-12-31` filters by date
  - Test: `GET /api/reports?time_of_day=Night` filters by time
  - Test: `GET /api/reports/{uuid}` returns single report
  - Test: `POST /api/reports` with valid data creates report (existing venue)
  - Test: `POST /api/reports` with new venue creates both venue and report
  - Test: `POST /api/reports` with invalid data returns 422 with errors
  - Test: `POST /api/reports` with PII in description returns 422
- [ ] Use JSON test methods: `getJson()`, `postJson()`
- [ ] Use response assertions: `assertOk()`, `assertNotFound()`, `assertUnprocessable()`
- [ ] Run tests: `pa test --filter=Controller`

**Acceptance Criteria:**
- All feature tests pass
- Tests cover happy path and error cases
- Tests use factories for test data
- JSON structure validated in assertions
- No database pollution between tests

### Phase 5 Review Checkpoint

**Before proceeding to Phase 6, complete these reviews:**

- [ ] API endpoints follow REST conventions
- [ ] Validation comprehensive and tested
- [ ] JSON responses consistent across endpoints
- [ ] All feature tests pass
- [ ] Wayfinder integration working
- [ ] All Critical/High issues resolved

**Phase 5 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 6: Frontend Setup & Core Components

**Primary Agent:** `vue-frontend-developer`
**Skills to Load:** `vue-best-practices`, `vue-inertia-frontend-guidelines`, `inertia-vue-development`, `tailwindcss-development`
**Duration:** ~2 days
**Dependencies:** Phase 5

### Objectives
- Set up Pinia store for state management
- Create reusable Vue 3 components with Composition API
- Implement TypeScript types for frontend
- Create composables for shared logic
- Configure Vue Leaflet for maps

### Tasks

#### 6.1 Set Up Pinia Store
- [ ] Create `resources/js/stores/venueStore.ts`
  - State: `venues: Venue[]`, `currentVenue: Venue | null`, `loading: boolean`
  - Action: `fetchVenues(filters?: VenueFilters): Promise<void>`
  - Action: `fetchVenueById(uuid: string): Promise<void>`
  - Action: `searchVenues(query: string): Promise<void>`
  - Getter: `venuesByCity(city: string): Venue[]`
- [ ] Create `resources/js/stores/reportStore.ts`
  - State: `reports: Report[]`, `recentReports: Report[]`, `loading: boolean`
  - Action: `fetchReports(filters?: ReportFilters): Promise<void>`
  - Action: `fetchRecentReports(limit?: number): Promise<void>`
  - Action: `submitReport(data: ReportSubmitData): Promise<Report>`
  - Getter: `reportsByVenue(venueUuid: string): Report[]`
- [ ] Configure Pinia in `resources/js/app.ts`
- [ ] Add TypeScript types for all state, actions, getters

**Acceptance Criteria:**
- Pinia stores use Composition API syntax
- Actions handle errors and set loading states
- TypeScript types prevent type errors
- Stores can be used in components with `useVenueStore()`

#### 6.2 Create TypeScript Types
- [ ] Create `resources/js/types/venue.ts`
  - Interface: `Venue` (matches backend VenueResource)
  - Interface: `VenueFilters` (city, search query, lat/lng/radius)
  - Type: `VenueCollection` (paginated response)
- [ ] Create `resources/js/types/report.ts`
  - Interface: `Report` (matches backend ReportResource)
  - Enum: `TimeOfDay` (matching backend enum)
  - Interface: `ReportFilters` (venue_uuid, date range, time_of_day)
  - Interface: `ReportSubmitData` (form data structure)
  - Type: `PopulatedReport` (report with venue data)
- [ ] Create `resources/js/types/api.ts`
  - Interface: `PaginationMeta` (total, per_page, current_page, etc.)
  - Interface: `ApiResponse<T>` (generic API response wrapper)
  - Interface: `ApiError` (validation errors structure)
- [ ] Update `resources/js/types/index.ts` to export all types

**Acceptance Criteria:**
- Types match backend API responses exactly
- Enums match backend enums
- All types exported from central index
- No `any` types used

#### 6.3 Create Vue Composables
- [ ] Create `resources/js/composables/useVenues.ts`
  - Use `useVenueStore()` internally
  - Expose: `venues`, `loading`, `fetchVenues()`, `searchVenues()`
  - Return type: `{ venues: ComputedRef<Venue[]>, loading: Ref<boolean>, ... }`
- [ ] Create `resources/js/composables/useReports.ts`
  - Use `useReportStore()` internally
  - Expose: `reports`, `recentReports`, `loading`, `fetchReports()`, `submitReport()`
- [ ] Create `resources/js/composables/useSearch.ts`
  - State: `query: Ref<string>`, `results: Ref<Venue[]>`
  - Method: `search(query: string): Promise<void>`
  - Debounced search with VueUse `useDebounceFn`
- [ ] Create `resources/js/composables/useFilters.ts`
  - State: `filters: Ref<ReportFilters>`, `activeFilters: ComputedRef<string[]>`
  - Method: `updateFilter(key: string, value: any): void`
  - Method: `clearFilters(): void`
  - Method: `applyFilters(): Promise<void>`
- [ ] Create `resources/js/composables/useMap.ts`
  - State: `map: Ref<L.Map | null>`, `markers: Ref<L.Marker[]>`
  - Method: `initMap(elementId: string, center: [number, number]): void`
  - Method: `addMarker(venue: Venue): L.Marker`
  - Method: `clearMarkers(): void`
  - Method: `fitBounds(venues: Venue[]): void`

**Acceptance Criteria:**
- Composables follow Vue 3 Composition API patterns
- Composables are reusable across components
- TypeScript types ensure type safety
- Composables handle loading and error states

#### 6.4 Create Layout Components
- [ ] Create `resources/js/components/layout/AppLayout.vue`
  - Template: `<div>`, `<PoliceStrip />`, `<AppHeader />`, `<main><slot /></main>`, `<AppFooter />`
  - Props: none (global layout)
  - Use Tailwind for structure
- [ ] Create `resources/js/components/layout/AppHeader.vue`
  - Template: navigation bar with logo, links (Home, Map, Submit Report, About)
  - Use Inertia `<Link>` component for navigation
  - Mobile responsive with hamburger menu
  - Use Lucide Vue icons
- [ ] Create `resources/js/components/layout/AppFooter.vue`
  - Template: footer with links, copyright, disclaimer
  - Props: none
- [ ] Create `resources/js/components/layout/PoliceStrip.vue`
  - Template: banner with police contact information
  - Dismissible with local storage persistence
  - Use `useLocalStorage` from VueUse

**Acceptance Criteria:**
- Layout components render correctly
- Navigation works with Inertia.js
- Mobile responsive
- Components use `<script setup>` syntax

#### 6.5 Create UI Components
- [ ] Create `resources/js/components/ui/DisclaimerBanner.vue`
  - Template: warning banner with disclaimer text
  - Props: `dismissible?: boolean`
  - Emit: `@dismissed` event
  - Use Tailwind for styling
- [ ] Create `resources/js/components/ui/SearchBar.vue`
  - Template: input with search icon, clear button
  - Props: `modelValue: string`, `placeholder?: string`
  - Emit: `update:modelValue`, `@search`
  - Use `v-model` for two-way binding
- [ ] Create `resources/js/components/ui/DateFilter.vue`
  - Template: date range picker (start + end date inputs)
  - Props: `startDate: string`, `endDate: string`
  - Emit: `update:startDate`, `update:endDate`, `@apply`
  - Validate: end date >= start date

**Acceptance Criteria:**
- Components reusable and composable
- Props and emits properly typed
- Components follow Vue 3 best practices
- Tailwind CSS used for styling

#### 6.6 Configure Vue Leaflet
- [ ] Install dependencies: `npm install vue-leaflet @vue-leaflet/vue-leaflet leaflet`
- [ ] Import Leaflet CSS in `resources/js/app.ts`: `import 'leaflet/dist/leaflet.css'`
- [ ] Create wrapper component `resources/js/components/ui/LeafletMap.vue`
  - Template: `<l-map>`, `<l-tile-layer>`, `<l-marker>` with slots
  - Props: `center: [number, number]`, `zoom: number`, `venues: Venue[]`
  - Emit: `@marker-click(venue: Venue)`
  - Configure tile layer: OpenStreetMap or Mapbox
- [ ] Fix Leaflet icon issue (default markers not showing):
  ```ts
  import L from 'leaflet';
  import icon from 'leaflet/dist/images/marker-icon.png';
  import iconShadow from 'leaflet/dist/images/marker-shadow.png';
  L.Marker.prototype.options.icon = L.icon({
    iconUrl: icon,
    shadowUrl: iconShadow,
  });
  ```

**Acceptance Criteria:**
- Leaflet map renders without errors
- Markers display correctly
- Map is interactive (pan, zoom)
- Click events work on markers

### Phase 6 Review Checkpoint

**Before proceeding to Phase 7, complete these reviews:**

- [ ] Pinia stores configured correctly
- [ ] TypeScript types comprehensive
- [ ] Composables reusable and tested
- [ ] Layout components render correctly
- [ ] Leaflet map integration working
- [ ] All Critical/High issues resolved

**Phase 6 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 7: Frontend Pages & Features

**Primary Agent:** `vue-frontend-developer`
**Skills to Load:** `vue-best-practices`, `vue-inertia-frontend-guidelines`, `inertia-vue-development`, `tailwindcss-development`
**Duration:** ~3 days
**Dependencies:** Phase 6

### Objectives
- Migrate all React pages to Vue.js
- Implement Inertia.js page components
- Integrate with backend API via Inertia props and Wayfinder
- Maintain design fidelity from React prototype
- Add animations and transitions

### Tasks

#### 7.1 Create Home Page (Home.vue)
- [ ] Create `resources/js/pages/Home.vue`
- [ ] Template sections:
  - Hero section with search bar (gradient background, large heading)
  - Recent reports section (3 most recent reports as cards)
  - "How it works" sidebar (3-step guide)
  - Police contact CTA box
- [ ] Props (from Inertia): `recentReports: Report[]`
- [ ] Use `useReports()` composable for fetching
- [ ] Use `useSearch()` composable for search functionality
- [ ] Use `SearchBar` component
- [ ] Use `DisclaimerBanner` component
- [ ] Use Inertia `<Link>` for navigation
- [ ] Add motion-vue animations (fade in on mount)
- [ ] Mobile responsive design

**Acceptance Criteria:**
- Page matches React prototype design
- Search functionality works and navigates to Map page
- Recent reports displayed with correct formatting
- Links work with Inertia.js
- Page is fully responsive

#### 7.2 Create Map Page (Map.vue)
- [ ] Create `resources/js/pages/Map.vue`
- [ ] Template sections:
  - Full-width Leaflet map with venue markers
  - Sidebar with venue list and filters
  - Search bar above map
  - Filter controls (city dropdown, date range)
- [ ] Props (from Inertia): `venues: Venue[]`, `reports: Report[]`, `filters?: ReportFilters`
- [ ] Use `useVenues()` and `useReports()` composables
- [ ] Use `useMap()` composable for map management
- [ ] Use `useFilters()` composable for filter state
- [ ] Use `LeafletMap` component
- [ ] Click marker to navigate to VenueDetail page
- [ ] Update URL query params when filters change
- [ ] Add loading skeleton while fetching data

**Acceptance Criteria:**
- Map displays all venue markers correctly
- Clicking marker navigates to venue detail
- Filters update map markers dynamically
- Sidebar list syncs with map markers
- Search updates venues in real-time
- URL reflects current filters (shareable)

#### 7.3 Create Venue Detail Page (VenueDetail.vue)
- [ ] Create `resources/js/pages/VenueDetail.vue`
- [ ] Template sections:
  - Venue header (name, city, address, map preview)
  - Report statistics (total reports, most common time)
  - List of all reports (paginated, 10 per page)
  - "Submit a report" CTA button
- [ ] Props (from Inertia): `venue: Venue`, `reports: Report[]`, `reportsMeta: PaginationMeta`
- [ ] Use `useVenues()` composable
- [ ] Use `ReportCard` component for each report
- [ ] Pagination controls (previous/next page)
- [ ] Date filtering within venue reports
- [ ] Small embedded map showing venue location
- [ ] Add transition animations for report list

**Acceptance Criteria:**
- Venue information displayed accurately
- Reports paginated correctly
- Filters work within venue reports
- Embedded map shows correct location
- CTA button navigates to SubmitReport page with venue pre-filled

#### 7.4 Create Submit Report Page (SubmitReport.vue)
- [ ] Create `resources/js/pages/SubmitReport.vue`
- [ ] Multi-step form (2 steps):
  - Step 1: Location & Time (venue search/create, date, time of day)
  - Step 2: Details (description textarea)
  - Step 3: Success confirmation
- [ ] Props (from Inertia): `venues: Venue[]`, `prefilledVenue?: Venue`
- [ ] Use `useReports()` composable for submission
- [ ] Use Inertia `useForm()` for form handling and validation errors
- [ ] Venue typeahead search with "Add new venue" option
- [ ] If new venue: show city input field
- [ ] Description textarea with character counter (min 20, max 1000)
- [ ] PII warning banner below description
- [ ] Disclaimer banner on step 2
- [ ] Submit button disabled while submitting
- [ ] Success page with navigation buttons (Map, About)
- [ ] Add step transition animations

**Acceptance Criteria:**
- Multi-step form works correctly
- Venue search with typeahead works
- New venue creation flow works
- Validation errors displayed clearly
- Success page shown after submission
- Form data cleared after success
- Animations smooth between steps

#### 7.5 Create About Page (About.vue)
- [ ] Create `resources/js/pages/About.vue`
- [ ] Template sections:
  - Platform purpose and mission
  - How the platform works
  - Legal disclaimer
  - Police contact information (UK regions)
  - Support resources (helplines, websites)
- [ ] Props (from Inertia): none (static content)
- [ ] Use `DisclaimerBanner` component
- [ ] Add ID anchors for deep linking (e.g., `#police`)
- [ ] Mobile responsive layout

**Acceptance Criteria:**
- Content clear and comprehensive
- Deep links work (e.g., `/about#police`)
- Police contact info for major UK regions
- Support resource links valid and accessible

#### 7.6 Create Report & Venue Components
- [ ] Create `resources/js/components/reports/ReportCard.vue`
  - Template: card layout with date badge, time of day, description preview
  - Props: `report: Report`, `showVenue?: boolean`
  - Click to expand full description
  - Use Lucide icons for time badges
- [ ] Create `resources/js/components/reports/ReportList.vue`
  - Template: list of `ReportCard` components
  - Props: `reports: Report[]`, `loading?: boolean`
  - Empty state when no reports
  - Loading skeleton
- [ ] Create `resources/js/components/venues/VenueCard.vue`
  - Template: card layout with name, city, report count
  - Props: `venue: Venue`
  - Emit: `@click` event
  - Use Inertia `<Link>` to navigate
- [ ] Create `resources/js/components/venues/VenueSearchInput.vue`
  - Template: input with typeahead dropdown
  - Props: `modelValue: string`, `venues: Venue[]`
  - Emit: `update:modelValue`, `@select(venue: Venue)`
  - Show "Add new venue" option if no exact match
- [ ] Create `resources/js/components/venues/VenueMap.vue` (wrapper around LeafletMap)
  - Template: `<LeafletMap>` with custom marker popups
  - Props: `venues: Venue[]`, `center?: [number, number]`
  - Emit: `@venue-click(venue: Venue)`
  - Marker popup shows venue name and report count

**Acceptance Criteria:**
- Components reusable across pages
- Components follow Vue 3 best practices
- Props and emits properly typed
- Components styled with Tailwind CSS
- Loading and empty states handled

#### 7.7 Integrate Wayfinder for Type-Safe Routing
- [ ] Run `pa wayfinder:generate` to generate TypeScript route functions
- [ ] Import route functions in components:
  ```ts
  import { route } from '@/routes'
  ```
- [ ] Use route functions instead of hardcoded URLs:
  ```vue
  <Link :href="route('venues.show', { uuid: venue.uuid })">
  ```
- [ ] Update all page components to use Wayfinder routes
- [ ] Update all navigation components

**Acceptance Criteria:**
- All routes use Wayfinder functions
- No hardcoded URLs in components
- TypeScript errors if route parameters incorrect
- Route generation runs automatically on changes

### Phase 7 Review Checkpoint

**Before proceeding to Phase 8, complete these reviews:**

- [ ] All pages migrated from React to Vue
- [ ] Pages match design prototype
- [ ] Inertia.js integration working correctly
- [ ] Animations and transitions smooth
- [ ] Mobile responsive
- [ ] All Critical/High issues resolved

**Phase 7 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 8: Backend-Frontend Integration & Inertia Setup

**Primary Agent:** `laravel-backend-developer`
**Skills to Load:** `laravel-backend-guidelines`, `inertia-vue-development`, `wayfinder-development`
**Duration:** ~1 day
**Dependencies:** Phase 7

### Objectives
- Create Inertia controller methods to render Vue pages
- Pass props from backend to frontend
- Configure shared Inertia props (flash messages, errors)
- Set up SSR (optional but recommended)
- Test full-stack integration

### Tasks

#### 8.1 Create Inertia Page Controllers
- [ ] Create `src/DrinkSafe/Pages/HomeController.php`
  - Method: `__invoke(): Response` - render Home page
  - Fetch recent reports using `ReportService`
  - Pass props: `recentReports: ReportCollection`
  - Return: `Inertia::render('Home', ['recentReports' => ...])`
- [ ] Create `src/DrinkSafe/Pages/MapController.php`
  - Method: `__invoke(Request $request): Response` - render Map page
  - Fetch venues and reports with optional filters from query params
  - Pass props: `venues: VenueCollection`, `reports: ReportCollection`, `filters: array`
- [ ] Create `src/DrinkSafe/Pages/VenueDetailController.php`
  - Method: `__invoke(string $uuid): Response` - render VenueDetail page
  - Fetch venue and its reports using `VenueService`
  - Paginate reports (10 per page)
  - Pass props: `venue: VenueResource`, `reports: ReportCollection`, `reportsMeta: PaginationMeta`
  - Throw 404 if venue not found
- [ ] Create `src/DrinkSafe/Pages/SubmitReportController.php`
  - Method: `create(Request $request): Response` - render SubmitReport page
  - Fetch all venues for typeahead
  - Pre-fill venue from query param if provided
  - Pass props: `venues: VenueCollection`, `prefilledVenue?: VenueResource`
- [ ] Create `src/DrinkSafe/Pages/AboutController.php`
  - Method: `__invoke(): Response` - render About page
  - Pass props: none (static page)

**Acceptance Criteria:**
- Controllers render Inertia responses
- Props match frontend TypeScript types
- API Resources used for consistent data structure
- Controllers handle errors gracefully

#### 8.2 Update Routes for Inertia Pages
- [ ] Update `routes/drinksafe.php` to include page routes:
  ```php
  // Page routes (Inertia)
  Route::get('/', HomeController::class)->name('home');
  Route::get('/map', MapController::class)->name('map');
  Route::get('/venue/{uuid}', VenueDetailController::class)->name('venue.show');
  Route::get('/report', [SubmitReportController::class, 'create'])->name('report.create');
  Route::get('/about', AboutController::class)->name('about');
  ```
- [ ] Keep API routes separate (already defined in Phase 5)
- [ ] Run `pa route:list` to verify
- [ ] Run `pa wayfinder:generate` to update TypeScript routes

**Acceptance Criteria:**
- Page routes return HTML (Inertia responses)
- API routes return JSON
- Routes follow RESTful naming
- Wayfinder generates correct TypeScript functions

#### 8.3 Configure Shared Inertia Props
- [ ] Update `app/Http/Middleware/HandleInertiaRequests.php`
- [ ] Add shared props:
  - `flash.success`, `flash.error` (session flash messages)
  - `errors` (validation errors)
  - `auth.user` (authenticated user if applicable, null for now)
- [ ] Create `resources/js/composables/useFlash.ts` for flash message handling
  - Use `usePage().props.flash` to access flash messages
  - Auto-dismiss after 5 seconds
  - Use Sonner for toast notifications

**Acceptance Criteria:**
- Flash messages accessible in all pages
- Validation errors accessible via `usePage().props.errors`
- Flash composable works across pages
- Toast notifications display correctly

#### 8.4 Test Full-Stack Integration
- [ ] Test: Navigate to `/` - Home page renders with recent reports
- [ ] Test: Search from Home page - navigates to Map page with query param
- [ ] Test: Click venue marker on Map - navigates to VenueDetail page
- [ ] Test: Submit report with existing venue - creates report and shows success
- [ ] Test: Submit report with new venue - creates venue and report
- [ ] Test: Submit report with validation errors - shows errors on form
- [ ] Test: Filter reports on Map page - updates URL and map markers
- [ ] Test: Navigate to About page - static page renders
- [ ] Test: Navigate to `/venue/{invalid-uuid}` - shows 404 page

**Acceptance Criteria:**
- All navigation works without full page reloads
- Props passed from backend to frontend correctly
- Validation errors displayed on frontend
- Flash messages work after form submission
- 404 errors handled gracefully

#### 8.5 Configure SSR (Optional but Recommended)
- [ ] Verify `@inertiajs/vite` plugin is configured in `vite.config.ts`
- [ ] SSR works automatically in Vite dev mode (no separate Node.js server needed)
- [ ] Test SSR: `curl http://localhost:8000/` - returns rendered HTML
- [ ] For production: configure SSR build in `vite.config.ts`
- [ ] Document SSR setup in `docs/architecture/ssr-setup.md`

**Acceptance Criteria:**
- SSR works in development mode
- Pages render initial HTML (not blank until JS loads)
- SEO-friendly (view source shows content)
- Production SSR build configured

### Phase 8 Review Checkpoint

**Before proceeding to Phase 9, complete these reviews:**

- [ ] Backend renders Inertia pages correctly
- [ ] Props match frontend expectations
- [ ] Full-stack integration tested end-to-end
- [ ] Flash messages and validation errors work
- [ ] SSR configured (optional)
- [ ] All Critical/High issues resolved

**Phase 8 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 9: Testing, Security & Production Readiness

**Primary Agent:** `test-engineer` (tests), `code-auditor` (security), `laravel-backend-developer` (fixes)
**Skills to Load:** `pest-testing`, `quality-assurance`, `bug-investigation`, `laravel-backend-guidelines`
**Duration:** ~2 days
**Dependencies:** Phase 8

### Objectives
- Comprehensive test coverage (Feature + Unit + E2E)
- Security audit (OWASP Top 10 compliance)
- Performance optimisation
- Production configuration
- Documentation and deployment guide

### Tasks

#### 9.1 Complete Test Coverage
- [ ] **Backend Unit Tests** (already mostly done in previous phases)
  - Verify 100% coverage for models, services, scopes
  - Run: `pa test --coverage`
  - Target: >90% code coverage
- [ ] **Backend Feature Tests** (already mostly done in Phase 5)
  - Verify all API endpoints tested
  - Verify all Inertia page controllers tested
  - Test error cases and edge cases
- [ ] **E2E Tests** (Playwright or Dusk)
  - Create `tests/Browser/DrinkSafe/HomePageTest.php`
    - Test: Home page loads with recent reports
    - Test: Search navigates to Map page
  - Create `tests/Browser/DrinkSafe/MapPageTest.php`
    - Test: Map renders with markers
    - Test: Click marker navigates to VenueDetail
    - Test: Filters update map
  - Create `tests/Browser/DrinkSafe/SubmitReportTest.php`
    - Test: Multi-step form submission (happy path)
    - Test: Validation errors display
    - Test: Success page shown after submission
  - Run: `pa dusk` or configure Playwright

**Acceptance Criteria:**
- Backend coverage >90%
- All feature tests pass
- E2E tests cover critical user flows
- Tests run in CI pipeline

#### 9.2 Security Audit (OWASP Top 10)
- [ ] **A01: Broken Access Control**
  - Verify: No unauthorized access to admin features (policies in place)
  - Verify: UUID prevents enumeration attacks
- [ ] **A02: Cryptographic Failures**
  - Verify: HTTPS enforced in production (`.env` APP_URL=https)
  - Verify: Sensitive data not logged
- [ ] **A03: Injection**
  - Verify: All queries use Eloquent ORM (no raw SQL)
  - Verify: User input sanitised in `ReportModerationService`
  - Test: SQL injection attempts fail
- [ ] **A04: Insecure Design**
  - Verify: Rate limiting on report submission (prevent spam)
  - Add: Rate limiting middleware to report routes
- [ ] **A05: Security Misconfiguration**
  - Verify: Debug mode off in production (`.env` APP_DEBUG=false)
  - Verify: Sensitive config not in version control
  - Verify: CORS configured correctly
- [ ] **A06: Vulnerable Components**
  - Run: `composer audit` for PHP dependencies
  - Run: `npm audit` for JavaScript dependencies
  - Fix: Any vulnerabilities found
- [ ] **A07: Authentication Failures**
  - Not applicable (no authentication yet)
- [ ] **A08: Software and Data Integrity Failures**
  - Verify: Composer and NPM lockfiles committed
  - Verify: Integrity checks for CDN assets (SRI)
- [ ] **A09: Logging and Monitoring**
  - Verify: Exceptions logged correctly
  - Verify: PII not logged in reports
  - Add: Sentry or similar error tracking (optional)
- [ ] **A10: Server-Side Request Forgery (SSRF)**
  - Verify: No user-controlled URLs fetched
  - Verify: Geolocation service uses validated coordinates

**Acceptance Criteria:**
- All OWASP Top 10 vulnerabilities mitigated
- Security tests pass
- Rate limiting configured
- Dependencies up-to-date and audited

#### 9.3 Performance Optimisation
- [ ] **Database Query Optimisation**
  - Run: `pa telescope:install` (optional for profiling)
  - Identify: N+1 queries in controllers
  - Fix: Add eager loading where needed
  - Verify: All queries use indexes (run `EXPLAIN`)
- [ ] **Frontend Performance**
  - Run: Lighthouse audit on all pages
  - Target: >90 performance score
  - Optimise: Images (lazy loading, WebP format)
  - Optimise: JavaScript bundle size (code splitting)
  - Optimise: CSS (purge unused Tailwind classes)
- [ ] **API Response Times**
  - Measure: API endpoint response times
  - Target: <100ms for all endpoints
  - Optimise: Add caching where appropriate (Redis)
- [ ] **Map Performance**
  - Optimise: Cluster markers when >50 venues visible
  - Optimise: Lazy load marker data as user pans map

**Acceptance Criteria:**
- No N+1 queries in production code
- Lighthouse scores >90
- API response times <100ms
- Map performs smoothly with 100+ markers

#### 9.4 Production Configuration
- [ ] Create `.env.example` with production settings:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://yourdomain.com`
  - Database connection settings
  - Session driver: `redis` (recommended)
  - Queue driver: `redis` (recommended)
  - Cache driver: `redis` (recommended)
- [ ] Configure CORS in `config/cors.php`
- [ ] Configure session security:
  - `SESSION_SECURE_COOKIE=true`
  - `SESSION_SAME_SITE=lax`
- [ ] Configure rate limiting in `app/Http/Kernel.php`
  - API routes: 60 requests per minute
  - Report submission: 5 requests per hour
- [ ] Create `docs/deployment/production-checklist.md`
- [ ] Create `docs/deployment/environment-setup.md`

**Acceptance Criteria:**
- `.env.example` comprehensive and documented
- Production config follows best practices
- Rate limiting tested
- Deployment documentation complete

#### 9.5 Final Documentation
- [ ] Update `README.md` with:
  - Project overview
  - Installation instructions
  - Development setup
  - Testing instructions
  - Deployment guide
- [ ] Create `docs/api/endpoints.md` documenting all API endpoints
- [ ] Create `docs/frontend/component-library.md` documenting Vue components
- [ ] Create `docs/architecture/system-overview.md` with system diagram
- [ ] Create `CONTRIBUTING.md` with contribution guidelines
- [ ] Create `CHANGELOG.md` for version tracking

**Acceptance Criteria:**
- Documentation comprehensive and up-to-date
- New developers can set up project from docs
- API endpoints documented with examples
- Architecture documented with diagrams

### Phase 9 Review Checkpoint

**Final Review:**

- [ ] All tests pass (Unit + Feature + E2E)
- [ ] Security audit complete
- [ ] Performance targets met
- [ ] Production configuration complete
- [ ] Documentation comprehensive
- [ ] All Critical/High issues resolved

**Phase 9 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Risk Assessment and Mitigation Strategies

### High-Priority Risks

| Risk | Impact | Probability | Mitigation Strategy |
|------|--------|-------------|---------------------|
| **Data Model Mismatch** | High | Medium | Create TypeScript types from backend responses early. Use Wayfinder for type-safe routing. |
| **Performance Issues with Map** | High | Medium | Implement marker clustering for >50 venues. Use geospatial indexes. Lazy load data. |
| **PII Exposure in Reports** | Critical | Low | Implement robust PII detection in `ReportModerationService`. Add manual review queue. |
| **Scope Creep** | Medium | High | Stick to defined MVP features. Document future enhancements separately. |
| **Incomplete Testing** | High | Medium | Enforce test coverage >90%. Review tests before phase sign-off. |
| **Geolocation Accuracy** | Medium | Medium | Use established libraries for geospatial calculations. Test with known coordinates. |
| **Database Performance** | High | Low | Index all foreign keys and filter columns. Use `EXPLAIN` to verify query plans. |

### Medium-Priority Risks

| Risk | Impact | Probability | Mitigation Strategy |
|------|--------|-------------|---------------------|
| **Third-Party Dependency Issues** | Medium | Low | Pin dependency versions in lockfiles. Test upgrades in dev environment first. |
| **Browser Compatibility** | Medium | Medium | Test in Chrome, Firefox, Safari. Use Browserslist for Tailwind compatibility. |
| **Mobile Responsiveness** | Medium | Medium | Test on real devices throughout development. Use responsive design breakpoints. |
| **State Management Complexity** | Low | Medium | Keep Pinia stores simple. Use composables for reusable logic. |

---

## Success Metrics

### Functional Completeness
- [ ] All React pages migrated to Vue.js with feature parity
- [ ] All user flows work end-to-end (search, view, submit)
- [ ] Map displays venues and reports correctly
- [ ] Report submission creates venues and reports
- [ ] Filters work independently and combined

### Code Quality
- [ ] Backend test coverage >90%
- [ ] Zero security vulnerabilities (OWASP Top 10 compliant)
- [ ] Code follows Laravel and Vue.js best practices
- [ ] No linting errors (Pint, ESLint, Prettier)
- [ ] Comprehensive documentation

### Performance
- [ ] API response times <100ms
- [ ] Lighthouse performance score >90
- [ ] First contentful paint <1.5s
- [ ] Time to interactive <3s
- [ ] Map renders smoothly with 100+ markers

### User Experience
- [ ] Mobile responsive on all devices
- [ ] Accessible (WCAG 2.1 AA compliant)
- [ ] Smooth animations and transitions
- [ ] Clear error messages and validation feedback
- [ ] Intuitive navigation

---

## Required Resources and Dependencies

### Technical Resources
- Laravel 13.7 + PHP 8.3 runtime environment
- Node.js 20+ for frontend build
- PostgreSQL or MySQL database
- Redis (optional, for caching and sessions)
- Git for version control

### Development Tools
- Composer for PHP dependencies
- NPM for JavaScript dependencies
- Pest for PHP testing
- Playwright or Dusk for E2E testing
- Laravel Pint for code formatting
- ESLint + Prettier for JavaScript formatting

### External Services
- Map tiles provider (OpenStreetMap or Mapbox)
- Error tracking service (optional: Sentry)
- Hosting provider (Laravel Cloud, AWS, DigitalOcean, etc.)

### Human Resources
- Backend developer (Laravel expertise)
- Frontend developer (Vue.js expertise)
- QA engineer (testing and security)
- DevOps engineer (deployment and CI/CD)

### Documentation References
- Laravel 13 documentation
- Inertia.js v3 documentation
- Vue 3 documentation
- Leaflet documentation
- Tailwind CSS v4 documentation
- Pest documentation

---

## Conclusion

This plan provides a comprehensive roadmap for migrating the DrinkSafe React application to Vue.js with a modular Laravel backend. The phased approach ensures:

1. **Solid foundation** with proper architecture and setup
2. **Incremental progress** with clear milestones and review checkpoints
3. **Quality assurance** through comprehensive testing at each phase
4. **Risk mitigation** through proactive identification and planning
5. **Production readiness** with security, performance, and documentation focus

By following this plan with the specified agents and skills at each phase, the team will deliver a robust, maintainable, and scalable application that meets all functional and non-functional requirements.

**Total Estimated Duration:** 12-14 working days across 9 phases

**Next Steps:**
1. Review and approve this plan
2. Set up project tracking (GitHub Issues, Jira, etc.)
3. Begin Phase 1: Project Setup & Architecture Foundation
4. Hold daily standups to track progress
5. Conduct phase reviews before advancing

---

**Plan Status:** Draft
**Created:** 2026-05-14
**Last Updated:** 2026-05-14
**Version:** 1.0
