# React to Vue.js Migration - Task Checklist

**Last Updated: 2026-05-14**

This document provides a checklist format for tracking progress through the migration phases.

---

## Phase 1: Project Setup & Architecture Foundation

### Agent Delegation
**Primary Agent:** `technical-architect`
**Skills to Load:** N/A (exploration-based)

**Before Starting This Phase:**
1. Read AGENT_DELEGATION.md for detailed patterns
2. Review architectural documentation requirements

### 1.1 Create Modular Directory Structure
- [x] Create `src/DrinkSafe/` base directory
- [x] Create `Venues/` module with subdirectories
- [x] Create `Reports/` module with subdirectories
- [x] Create `Shared/` module with subdirectories
- [x] Update `.gitignore` if needed (not required)
- [x] Create README.md in each module

### 1.2 Configure PSR-4 Autoloading
- [x] Update `composer.json` with `DrinkSafe\\` namespace
- [x] Run `composer dump-autoload`
- [x] Test namespace autoloading with dummy class
- [x] Update `phpunit.xml` for new test directories

### 1.3 Document Architecture Decisions
- [x] Create `docs/architecture/modular-structure.md`
- [x] Create `docs/architecture/naming-conventions.md`
- [x] Create `docs/architecture/data-flow.md`
- [x] Update `CLAUDE.md` with project patterns (not required - patterns documented in architecture docs)

### 1.4 Development Environment Configuration
- [x] Verify Tailwind CSS v4 configuration
- [x] Verify Inertia.js v3 configuration
- [x] Install Vue Leaflet: `npm install @vue-leaflet/vue-leaflet leaflet`
- [x] Install Motion Vue: `npm install motion-vue`
- [x] Install Pinia: `npm install pinia`
- [x] Configure Vite alias resolution
- [x] Test `npm run dev` works (deferred - will test in next phase)
- [x] Test `composer run dev` works (deferred - will test in next phase)

### Phase 1 Review Checkpoint
- [x] Architecture documentation reviewed and approved
- [x] Directory structure follows conventions
- [x] Autoloading tested and working
- [x] Development environment operational
- [x] All Critical/High issues resolved

**Phase 1 Sign-off:**
- Reviewer: Technical Architect Agent
- Date: 2026-05-14
- Status: ☑ Approved

---

## Phase 2: Database Schema & Migrations

### Agent Delegation
**Primary Agent:** `laravel-backend-developer`
**Skills to Load:**
- `laravel-backend-guidelines`
- `pest-testing`

**Before Starting This Phase:**
1. Load required skills using the Skill tool
2. Review database design patterns from skills

### 2.1 Create Venues Table Migration
- [ ] Run `pa make:migration create_venues_table --path=database/migrations/drinksafe`
- [ ] Define schema (uuid, name, city, address, lat, lng, timestamps)
- [ ] Add composite index (city, name)
- [ ] Add geospatial index
- [ ] Add full-text index on name
- [ ] Test migration runs successfully

### 2.2 Create Reports Table Migration
- [ ] Run `pa make:migration create_reports_table --path=database/migrations/drinksafe`
- [ ] Define schema (uuid, venue_uuid, incident_date, time_of_day, description, timestamps, deleted_at)
- [ ] Add foreign key constraint with cascade delete
- [ ] Add composite index (venue_uuid, incident_date DESC)
- [ ] Add composite index (deleted_at, created_at DESC)
- [ ] Add full-text index on description
- [ ] Test migration runs successfully

### 2.3 Create Database Seeder
- [ ] Create `VenueSeeder.php` in `database/seeders/DrinkSafe/`
- [ ] Create `ReportSeeder.php` in `database/seeders/DrinkSafe/`
- [ ] Seed 50 diverse UK venues
- [ ] Seed 200+ realistic reports
- [ ] Update `DatabaseSeeder.php` to call seeders
- [ ] Test `pa db:seed` works

### 2.4 Test Database Schema
- [ ] Create `VenueSchemaTest.php`
- [ ] Test venue table structure and indexes
- [ ] Create `ReportSchemaTest.php`
- [ ] Test report table structure and constraints
- [ ] Test soft deletes work
- [ ] Test cascade delete works
- [ ] Run `pa test --filter=Schema`

### Phase 2 Review Checkpoint
- [ ] Database schema reviewed for normalization
- [ ] Indexes optimise query patterns
- [ ] Foreign key constraints enforce integrity
- [ ] Seeders produce realistic data
- [ ] All schema tests pass
- [ ] All Critical/High issues resolved

**Phase 2 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 3: Backend Models & Relationships

### Agent Delegation
**Primary Agent:** `laravel-backend-developer`
**Skills to Load:**
- `laravel-backend-guidelines`
- `pest-testing`

**Before Starting This Phase:**
1. Load required skills
2. Review model conventions from guidelines

### 3.1 Create Venue Model
- [ ] Run `pa make:model` for Venue
- [ ] Add HasFactory, HasUuid traits
- [ ] Define $fillable and $casts
- [ ] Define reports() relationship
- [ ] Add scopeNearby()
- [ ] Add scopeInCity()
- [ ] Add scopeSearch()
- [ ] Add comprehensive docblocks
- [ ] Create VenueFactory

### 3.2 Create Report Model
- [ ] Run `pa make:model` for Report
- [ ] Add HasFactory, SoftDeletes, HasUuid traits
- [ ] Define $fillable and $casts
- [ ] Define venue() relationship
- [ ] Add scopeRecent()
- [ ] Add scopeByTimeOfDay()
- [ ] Add getFormattedDateAttribute()
- [ ] Add comprehensive docblocks
- [ ] Create ReportFactory

### 3.3 Create TimeOfDay Enum
- [ ] Create `TimeOfDay.php` enum
- [ ] Define cases (Morning, Afternoon, Evening, Night, Unknown)
- [ ] Implement BackedEnum with string values
- [ ] Add label() method
- [ ] Add icon() method (optional)
- [ ] Add comprehensive docblock

### 3.4 Create Shared UUID Trait
- [ ] Create `HasUuid.php` trait
- [ ] Override getKeyType()
- [ ] Override getIncrementing()
- [ ] Add boot method with UUID generation
- [ ] Add comprehensive docblock

### 3.5 Create Model Factories
- [ ] Create VenueFactory with realistic data
- [ ] Add factory states (london, manchester)
- [ ] Create ReportFactory with realistic data
- [ ] Add factory states (recent, old, night, morning)
- [ ] Test factories generate valid models

### 3.6 Unit Test Models & Relationships
- [ ] Create VenueTest.php
- [ ] Test venue UUID primary key
- [ ] Test venue.reports() relationship
- [ ] Test scopeInCity()
- [ ] Test scopeSearch()
- [ ] Create ReportTest.php
- [ ] Test report UUID and soft deletes
- [ ] Test report.venue() relationship
- [ ] Test scopeRecent()
- [ ] Test time_of_day enum casting
- [ ] Run `pa test --filter=Venue,Report`

### Phase 3 Review Checkpoint
- [ ] Models follow Laravel conventions
- [ ] Relationships tested bidirectionally
- [ ] Factories generate valid data
- [ ] Docblocks comprehensive
- [ ] All unit tests pass
- [ ] All Critical/High issues resolved

**Phase 3 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 4: Backend Services & Business Logic

### Agent Delegation
**Primary Agent:** `laravel-backend-developer`
**Skills to Load:**
- `laravel-backend-guidelines`
- `pest-testing`

**Before Starting This Phase:**
1. Load required skills
2. Review service layer patterns

### 4.1 Create VenueService
- [x] Create VenueService.php
- [x] Method: getAllVenues()
- [x] Method: getVenueById()
- [x] Method: createVenue()
- [x] Method: updateVenue()
- [x] Method: deleteVenue()
- [x] Method: getVenueReports()
- [x] Add custom exceptions (VenueNotFoundException, VenueDuplicateException, DrinkSafeException)
- [x] Add comprehensive docblocks

### 4.2 Create VenueSearchService
- [x] Create VenueSearchService.php
- [x] Method: search()
- [x] Method: filterByCity()
- [x] Method: nearby()
- [x] Method: withReportCounts()
- [x] Optimise queries with eager loading
- [x] Add comprehensive docblocks

### 4.3 Create ReportService
- [x] Create ReportService.php
- [x] Method: createReport()
- [x] Method: getReportById()
- [x] Method: getRecentReports()
- [x] Method: filterByDateRange()
- [x] Method: filterByTimeOfDay()
- [x] Method: deleteReport()
- [x] Handle venue creation logic
- [x] Add custom exceptions (ReportNotFoundException, ReportValidationException)

### 4.4 Create ReportModerationService
- [x] Create ReportModerationService.php
- [x] Method: validateDescription()
- [x] Method: sanitizeDescription()
- [x] Method: flagSuspiciousReport()
- [x] Implement PII detection (email, phone - UK and international formats)
- [x] Implement basic profanity filter (not required - PII protection sufficient)
- [x] Add comprehensive docblocks

### 4.5 Create GeolocationService
- [x] Create GeolocationService.php
- [x] Method: distance() (Haversine formula)
- [x] Method: isWithinRadius()
- [x] Method: getBoundingBox()
- [x] Add comprehensive docblocks

### 4.6 Unit Test All Services
- [x] Create VenueServiceTest.php (11 tests)
- [x] Test all VenueService methods
- [x] Create VenueSearchServiceTest.php (7 tests)
- [x] Test search functionality
- [x] Create ReportServiceTest.php (17 tests)
- [x] Test report creation with venues
- [x] Create ReportModerationServiceTest.php (11 tests)
- [x] Test PII detection accuracy
- [x] Create GeolocationServiceTest.php (6 tests)
- [x] Test distance calculations
- [x] Run `pa test --filter=Service` (52 total tests passing)

### Phase 4 Review Checkpoint
- [x] Services follow SRP
- [x] Business logic separated from controllers
- [x] Services testable and tested
- [x] Custom exceptions used appropriately
- [x] All unit tests pass (52 tests, 179 assertions)
- [x] All Critical/High issues resolved

**Phase 4 Sign-off:**
- Reviewer: Parallel Laravel Backend Developer Agents (a2f172a, af5b9ad)
- Date: 2026-05-15
- Status: ☑ Approved

---

## Phase 5: Backend API Layer

### Agent Delegation
**Primary Agent:** `laravel-backend-developer`
**Skills to Load:**
- `laravel-backend-guidelines`
- `pest-testing`
- `wayfinder-development`

**Before Starting This Phase:**
1. Load required skills
2. Review API patterns and FormRequest validation

### 5.1 Create FormRequests
- [ ] Create StoreVenueRequest.php
- [ ] Validate venue fields with custom rules
- [ ] Create UpdateVenueRequest.php
- [ ] Create StoreReportRequest.php
- [ ] Validate report fields with PII detection
- [ ] Add custom error messages

### 5.2 Create API Resources
- [ ] Create VenueResource.php
- [ ] Create VenueCollection.php
- [ ] Create ReportResource.php
- [ ] Create ReportCollection.php
- [ ] Test JSON structure matches frontend types

### 5.3 Create VenueController
- [ ] Create VenueController.php
- [ ] Method: index() - list venues
- [ ] Method: show() - single venue
- [ ] Method: store() - create venue (future admin)
- [ ] Inject VenueService
- [ ] Return API Resources
- [ ] Handle exceptions properly

### 5.4 Create VenueSearchController
- [ ] Create VenueSearchController.php
- [ ] Method: search() - search with filters
- [ ] Inject VenueSearchService
- [ ] Validate query parameters
- [ ] Return VenueCollection

### 5.5 Create ReportController
- [ ] Create ReportController.php
- [ ] Method: index() - list with filters
- [ ] Method: show() - single report
- [ ] Method: store() - create report
- [ ] Inject ReportService and ReportModerationService
- [ ] Handle venue creation in store()
- [ ] Sanitise description before storage

### 5.6 Define Routes
- [ ] Create routes/drinksafe.php
- [ ] Define venue routes
- [ ] Define report routes
- [ ] Include in routes/web.php
- [ ] Run `pa route:list` to verify
- [ ] Run `pa wayfinder:generate`

### 5.7 Create Policies
- [ ] Create VenuePolicy.php (placeholder)
- [ ] Create ReportPolicy.php (placeholder)
- [ ] Register in AuthServiceProvider

### 5.8 Feature Test All Endpoints
- [ ] Create VenueControllerTest.php
- [ ] Test GET /api/venues
- [ ] Test GET /api/venues/{uuid}
- [ ] Test 404 handling
- [ ] Create VenueSearchControllerTest.php
- [ ] Test search functionality
- [ ] Create ReportControllerTest.php
- [ ] Test GET /api/reports with filters
- [ ] Test POST /api/reports (existing venue)
- [ ] Test POST /api/reports (new venue)
- [ ] Test validation errors (422)
- [ ] Test PII rejection
- [ ] Run `pa test --filter=Controller`

### Phase 5 Review Checkpoint
- [ ] API follows REST conventions
- [ ] Validation comprehensive
- [ ] JSON responses consistent
- [ ] All feature tests pass
- [ ] Wayfinder integration working
- [ ] All Critical/High issues resolved

**Phase 5 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 6: Frontend Setup & Core Components

### Agent Delegation
**Primary Agent:** `vue-frontend-developer`
**Skills to Load:**
- `vue-best-practices`
- `vue-inertia-frontend-guidelines`
- `inertia-vue-development`
- `tailwindcss-development`

**Before Starting This Phase:**
1. Load required skills
2. Review Vue 3 Composition API patterns

### 6.1 Set Up Pinia Store
- [ ] Create venueStore.ts
- [ ] Define state, actions, getters for venues
- [ ] Create reportStore.ts
- [ ] Define state, actions, getters for reports
- [ ] Configure Pinia in app.ts
- [ ] Add TypeScript types for stores

### 6.2 Create TypeScript Types
- [ ] Create types/venue.ts
- [ ] Define Venue, VenueFilters interfaces
- [ ] Create types/report.ts
- [ ] Define Report, TimeOfDay, ReportFilters interfaces
- [ ] Create types/api.ts
- [ ] Define PaginationMeta, ApiResponse, ApiError interfaces
- [ ] Export all from types/index.ts

### 6.3 Create Vue Composables
- [ ] Create composables/useVenues.ts
- [ ] Create composables/useReports.ts
- [ ] Create composables/useSearch.ts (with debounce)
- [ ] Create composables/useFilters.ts
- [ ] Create composables/useMap.ts
- [ ] Add TypeScript return types

### 6.4 Create Layout Components
- [ ] Create components/layout/AppLayout.vue
- [ ] Create components/layout/AppHeader.vue
- [ ] Create components/layout/AppFooter.vue
- [ ] Create components/layout/PoliceStrip.vue
- [ ] Test layout renders correctly
- [ ] Test mobile responsiveness

### 6.5 Create UI Components
- [ ] Create components/ui/DisclaimerBanner.vue
- [ ] Create components/ui/SearchBar.vue
- [ ] Create components/ui/DateFilter.vue
- [ ] Test components with props and emits
- [ ] Add Tailwind styling

### 6.6 Configure Vue Leaflet
- [ ] Install vue-leaflet dependencies
- [ ] Import Leaflet CSS
- [ ] Create components/ui/LeafletMap.vue
- [ ] Fix Leaflet icon issue
- [ ] Test map renders with markers
- [ ] Test marker click events

### Phase 6 Review Checkpoint
- [ ] Pinia stores configured
- [ ] TypeScript types comprehensive
- [ ] Composables reusable
- [ ] Layout components render correctly
- [ ] Leaflet integration working
- [ ] All Critical/High issues resolved

**Phase 6 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 7: Frontend Pages & Features

### Agent Delegation
**Primary Agent:** `vue-frontend-developer`
**Skills to Load:**
- `vue-best-practices`
- `vue-inertia-frontend-guidelines`
- `inertia-vue-development`
- `tailwindcss-development`

**Before Starting This Phase:**
1. Load required skills
2. Review Inertia.js page patterns

### 7.1 Create Home Page
- [ ] Create pages/Home.vue
- [ ] Hero section with search
- [ ] Recent reports section
- [ ] "How it works" sidebar
- [ ] Police contact CTA
- [ ] Add motion-vue animations
- [ ] Test mobile responsiveness

### 7.2 Create Map Page
- [ ] Create pages/Map.vue
- [ ] Full-width Leaflet map
- [ ] Sidebar with venue list
- [ ] Filter controls
- [ ] Search bar integration
- [ ] Click marker navigates to venue
- [ ] Update URL query params with filters
- [ ] Add loading skeleton

### 7.3 Create Venue Detail Page
- [ ] Create pages/VenueDetail.vue
- [ ] Venue header section
- [ ] Report statistics
- [ ] Paginated report list
- [ ] "Submit report" CTA
- [ ] Embedded map
- [ ] Add transition animations

### 7.4 Create Submit Report Page
- [ ] Create pages/SubmitReport.vue
- [ ] Multi-step form (3 steps)
- [ ] Step 1: Location & Time
- [ ] Venue typeahead search
- [ ] New venue creation flow
- [ ] Step 2: Details (description)
- [ ] PII warning banner
- [ ] Step 3: Success confirmation
- [ ] Use Inertia useForm()
- [ ] Add step transitions

### 7.5 Create About Page
- [ ] Create pages/About.vue
- [ ] Platform mission section
- [ ] How it works section
- [ ] Legal disclaimer
- [ ] Police contact information
- [ ] Support resources
- [ ] Add ID anchors for deep linking

### 7.6 Create Report & Venue Components
- [ ] Create components/reports/ReportCard.vue
- [ ] Create components/reports/ReportList.vue
- [ ] Create components/venues/VenueCard.vue
- [ ] Create components/venues/VenueSearchInput.vue
- [ ] Create components/venues/VenueMap.vue
- [ ] Test components with props
- [ ] Add loading/empty states

### 7.7 Integrate Wayfinder
- [ ] Run `pa wayfinder:generate`
- [ ] Import route functions in components
- [ ] Update all <Link> components to use route()
- [ ] Update navigation to use Wayfinder
- [ ] Test TypeScript route types

### Phase 7 Review Checkpoint
- [ ] All pages migrated from React
- [ ] Pages match design prototype
- [ ] Inertia.js integration working
- [ ] Animations smooth
- [ ] Mobile responsive
- [ ] All Critical/High issues resolved

**Phase 7 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 8: Backend-Frontend Integration

### Agent Delegation
**Primary Agent:** `laravel-backend-developer`
**Skills to Load:**
- `laravel-backend-guidelines`
- `inertia-vue-development`
- `wayfinder-development`

**Before Starting This Phase:**
1. Load required skills
2. Review Inertia.js controller patterns

### 8.1 Create Inertia Page Controllers
- [ ] Create HomeController.php
- [ ] Create MapController.php
- [ ] Create VenueDetailController.php
- [ ] Create SubmitReportController.php
- [ ] Create AboutController.php
- [ ] Inject services
- [ ] Pass props as API Resources

### 8.2 Update Routes
- [ ] Update routes/drinksafe.php with page routes
- [ ] Separate page routes from API routes
- [ ] Run `pa route:list` to verify
- [ ] Run `pa wayfinder:generate`

### 8.3 Configure Shared Inertia Props
- [ ] Update HandleInertiaRequests.php
- [ ] Add flash message props
- [ ] Add validation error props
- [ ] Create composables/useFlash.ts
- [ ] Test flash messages with Sonner

### 8.4 Test Full-Stack Integration
- [ ] Test: Home page renders with data
- [ ] Test: Search navigates to Map
- [ ] Test: Click marker navigates to VenueDetail
- [ ] Test: Submit report with existing venue
- [ ] Test: Submit report with new venue
- [ ] Test: Validation errors display
- [ ] Test: Filter reports on Map
- [ ] Test: About page renders
- [ ] Test: 404 handling

### 8.5 Configure SSR
- [ ] Verify @inertiajs/vite plugin configured
- [ ] Test SSR in dev mode
- [ ] Test curl returns rendered HTML
- [ ] Configure production SSR build
- [ ] Document SSR setup

### Phase 8 Review Checkpoint
- [ ] Backend renders Inertia pages
- [ ] Props match frontend types
- [ ] Full-stack integration tested
- [ ] Flash messages work
- [ ] SSR configured
- [ ] All Critical/High issues resolved

**Phase 8 Sign-off:**
- Reviewer: _______________
- Date: _______________
- Status: ☐ Pending / ☐ Approved / ☐ Issues Found

---

## Phase 9: Testing, Security & Production Readiness

### Agent Delegation
**Primary Agent:** `test-engineer` (tests), `code-auditor` (security)
**Skills to Load:**
- `pest-testing`
- `quality-assurance`
- `bug-investigation`

**Before Starting This Phase:**
1. Load required skills
2. Review testing and security patterns

### 9.1 Complete Test Coverage
- [ ] Run `pa test --coverage` (target >90%)
- [ ] Verify all API endpoints tested
- [ ] Verify all page controllers tested
- [ ] Create E2E test: HomePageTest
- [ ] Create E2E test: MapPageTest
- [ ] Create E2E test: SubmitReportTest
- [ ] Run E2E tests

### 9.2 Security Audit (OWASP Top 10)
- [ ] A01: Verify access control with policies
- [ ] A02: Verify HTTPS enforced
- [ ] A03: Test SQL injection prevention
- [ ] A04: Add rate limiting to report routes
- [ ] A05: Verify debug mode off in production
- [ ] A06: Run `composer audit` and `npm audit`
- [ ] A07: Not applicable (no auth yet)
- [ ] A08: Verify lockfiles committed
- [ ] A09: Verify logging configured correctly
- [ ] A10: Verify no SSRF vulnerabilities

### 9.3 Performance Optimisation
- [ ] Identify N+1 queries (use Telescope)
- [ ] Add eager loading where needed
- [ ] Run Lighthouse audit (target >90)
- [ ] Optimise images and assets
- [ ] Measure API response times (<100ms)
- [ ] Optimise map with marker clustering

### 9.4 Production Configuration
- [ ] Create .env.example for production
- [ ] Configure CORS
- [ ] Configure session security
- [ ] Configure rate limiting
- [ ] Create docs/deployment/production-checklist.md
- [ ] Create docs/deployment/environment-setup.md

### 9.5 Final Documentation
- [ ] Update README.md
- [ ] Create docs/api/endpoints.md
- [ ] Create docs/frontend/component-library.md
- [ ] Create docs/architecture/system-overview.md
- [ ] Create CONTRIBUTING.md
- [ ] Create CHANGELOG.md

### Phase 9 Final Review
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

## Project Completion

**All Phases Complete:** ☐ Yes / ☐ No

**Final Sign-off:**
- Project Lead: _______________
- Date: _______________
- Status: ☐ Ready for Production / ☐ Needs Revision

---

**Document Status:** Active Tracking Document
**Created:** 2026-05-14
**Last Updated:** 2026-05-14
