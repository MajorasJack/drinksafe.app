# Agent Delegation - React to Vue.js Migration

**Purpose:** Map each phase to the appropriate agent and required skills for execution.

**CRITICAL:** AI agents MUST load the specified skills BEFORE executing any phase.

---

## Quick Reference

| Phase | Primary Agent | Skills to Load |
|-------|---------------|----------------|
| 1 | technical-architect | N/A (exploration) |
| 2 | laravel-backend-developer | laravel-backend-guidelines, pest-testing |
| 3 | laravel-backend-developer | laravel-backend-guidelines, pest-testing |
| 4 | laravel-backend-developer | laravel-backend-guidelines, pest-testing |
| 5 | laravel-backend-developer | laravel-backend-guidelines, pest-testing, wayfinder-development |
| 6 | vue-frontend-developer | vue-best-practices, vue-inertia-frontend-guidelines, inertia-vue-development, tailwindcss-development |
| 7 | vue-frontend-developer | vue-best-practices, vue-inertia-frontend-guidelines, inertia-vue-development, tailwindcss-development |
| 8 | laravel-backend-developer | laravel-backend-guidelines, inertia-vue-development, wayfinder-development |
| 9 | test-engineer, code-auditor | pest-testing, quality-assurance, bug-investigation |

---

## Phase Details

### Phase 1: Project Setup & Architecture Foundation

**Primary Agent:** `technical-architect`

**Before Starting - Load These Skills:**
- N/A (This phase uses exploration and documentation)

**Key Responsibilities:**
- Create modular directory structure in `src/DrinkSafe/`
- Configure PSR-4 autoloading for new namespace
- Document architecture decisions
- Set up development environment
- Install frontend dependencies (Vue Leaflet, Motion Vue, Pinia)

**Key Patterns:**
- Modular architecture: Domain-driven design with clear module boundaries
- PSR-4 autoloading: Map `DrinkSafe\\` namespace to `src/DrinkSafe/`
- Documentation-first: Create architectural docs before implementation

**Deliverables:**
- `src/DrinkSafe/` directory structure created
- `composer.json` updated with namespace mapping
- `docs/architecture/` documentation created
- All dependencies installed and verified

---

### Phase 2: Database Schema & Migrations

**Primary Agent:** `laravel-backend-developer`

**Before Starting - Load These Skills:**
- Skill: "laravel-backend-guidelines"
- Skill: "pest-testing"

**Key Patterns from Skills:**
- **Migrations:** Use descriptive names, add indexes for performance, plan ahead with foreign keys
- **Indexing:** Index foreign keys, filter columns, and full-text search columns
- **Testing:** Use PestPHP with `it()` syntax, AAA pattern, factories for data

**Key Responsibilities:**
- Create venues table migration with geospatial and full-text indexes
- Create reports table migration with soft deletes and foreign keys
- Create seeders for development data (50 venues, 200+ reports)
- Write schema unit tests to verify structure

**Critical Implementation Notes:**
- Use UUID primary keys (not auto-increment)
- Add composite indexes for common query patterns: `(city, name)`, `(venue_uuid, incident_date DESC)`
- Use full-text indexes for search: `name`, `description`
- Add geospatial index for location queries
- Foreign key: `reports.venue_uuid` → `venues.uuid` ON DELETE CASCADE
- Soft deletes on reports table: `deleted_at` timestamp

**Deliverables:**
- `database/migrations/drinksafe/create_venues_table.php`
- `database/migrations/drinksafe/create_reports_table.php`
- `database/seeders/DrinkSafe/VenueSeeder.php`
- `database/seeders/DrinkSafe/ReportSeeder.php`
- Schema tests in `src/DrinkSafe/{Module}/Tests/Unit/`

---

### Phase 3: Backend Models & Relationships

**Primary Agent:** `laravel-backend-developer`

**Before Starting - Load These Skills:**
- Skill: "laravel-backend-guidelines"
- Skill: "pest-testing"

**Key Patterns from Skills:**
- **Models:** Use traits (HasFactory, SoftDeletes, HasUuid), define $fillable and $casts
- **Relationships:** Define bidirectional relationships, use proper foreign key columns
- **Factories:** Create factories for all models, use factory states for variations
- **Testing:** Test relationships, scopes, and factory data validity

**Key Responsibilities:**
- Create Venue model with relationships and scopes
- Create Report model with enum casting and soft deletes
- Create TimeOfDay enum (Morning, Afternoon, Evening, Night, Unknown)
- Create HasUuid trait for automatic UUID generation
- Create factories for testing
- Write comprehensive unit tests

**Critical Implementation Notes:**
- **Venue Model:**
  - Traits: `HasFactory`, `HasUuid`
  - Relationship: `reports()` → hasMany(Report)
  - Scopes: `scopeNearby($lat, $lng, $radiusKm)`, `scopeInCity($city)`, `scopeSearch($term)`
  - Casts: latitude/longitude to decimal:8
- **Report Model:**
  - Traits: `HasFactory`, `SoftDeletes`, `HasUuid`
  - Relationship: `venue()` → belongsTo(Venue, 'venue_uuid')
  - Scopes: `scopeRecent($days)`, `scopeByTimeOfDay($timeOfDay)`
  - Casts: `time_of_day` to TimeOfDay enum
- **HasUuid Trait:**
  - Override `getKeyType()` → return `'string'`
  - Override `getIncrementing()` → return `false`
  - Boot method: Generate UUID on model creation

**Deliverables:**
- `src/DrinkSafe/Venues/Models/Venue.php`
- `src/DrinkSafe/Reports/Models/Report.php`
- `src/DrinkSafe/Reports/Enums/TimeOfDay.php`
- `src/DrinkSafe/Shared/Traits/HasUuid.php`
- `database/factories/DrinkSafe/VenueFactory.php`
- `database/factories/DrinkSafe/ReportFactory.php`
- Unit tests for all models

---

### Phase 4: Backend Services & Business Logic

**Primary Agent:** `laravel-backend-developer`

**Before Starting - Load These Skills:**
- Skill: "laravel-backend-guidelines"
- Skill: "pest-testing"

**Key Patterns from Skills:**
- **Service Layer:** Business logic in services, controllers stay thin
- **Dependency Injection:** Inject services into controllers
- **Custom Exceptions:** Throw module-specific exceptions, never generic `\Exception`
- **Testing:** Test services in isolation with mocking where needed

**Key Responsibilities:**
- Create VenueService for CRUD operations
- Create VenueSearchService for search and filtering
- Create ReportService for report management
- Create ReportModerationService for PII detection
- Create GeolocationService for distance calculations
- Write comprehensive unit tests for all services

**Critical Implementation Notes:**
- **VenueService:**
  - Methods: `getAllVenues()`, `getVenueById()`, `createVenue()`, `updateVenue()`, `deleteVenue()`, `getVenueReports()`
  - Throw: `VenueNotFoundException` when venue not found
  - Throw: `VenueDuplicateException` when duplicate name+city
- **VenueSearchService:**
  - Methods: `search($query)`, `filterByCity($city)`, `nearby($lat, $lng, $radiusKm)`, `withReportCounts()`
  - Optimise: Use eager loading to prevent N+1 queries
  - Use: Eloquent scopes from Venue model
- **ReportService:**
  - Methods: `createReport()`, `getReportById()`, `getRecentReports()`, `filterByDateRange()`, `filterByTimeOfDay()`, `deleteReport()`
  - Handle: Venue creation if venue doesn't exist
  - Throw: `ReportNotFoundException`, `ReportValidationException`
- **ReportModerationService:**
  - Methods: `validateDescription()`, `sanitizeDescription()`, `flagSuspiciousReport()`
  - Detect: Email addresses, phone numbers, full names (regex-based)
  - Sanitise: Mask or remove PII before storage
- **GeolocationService:**
  - Methods: `distance($lat1, $lng1, $lat2, $lng2)`, `isWithinRadius()`, `getBoundingBox()`
  - Use: Haversine formula for distance calculations
  - Return: Distance in kilometres

**Deliverables:**
- `src/DrinkSafe/Venues/Services/VenueService.php`
- `src/DrinkSafe/Venues/Services/VenueSearchService.php`
- `src/DrinkSafe/Reports/Services/ReportService.php`
- `src/DrinkSafe/Reports/Services/ReportModerationService.php`
- `src/DrinkSafe/Shared/Services/GeolocationService.php`
- Custom exceptions in each module
- Unit tests for all services

---

### Phase 5: Backend API Layer (Controllers, Requests, Resources)

**Primary Agent:** `laravel-backend-developer`

**Before Starting - Load These Skills:**
- Skill: "laravel-backend-guidelines"
- Skill: "pest-testing"
- Skill: "wayfinder-development"

**Key Patterns from Skills:**
- **Controllers:** Thin controllers, delegate to services
- **FormRequests:** All validation in FormRequest classes, not controllers
- **API Resources:** Consistent JSON responses using Eloquent API Resources
- **Wayfinder:** Generate TypeScript route functions for type-safe frontend routing
- **Testing:** Feature tests for all endpoints using JSON test methods

**Key Responsibilities:**
- Create FormRequests for validation
- Create API Resources for consistent JSON structure
- Create controllers (VenueController, VenueSearchController, ReportController)
- Define routes with Wayfinder integration
- Create placeholder policies for future authorization
- Write comprehensive feature tests for all endpoints

**Critical Implementation Notes:**
- **FormRequests:**
  - `StoreVenueRequest`: Validate name, city, address, lat/lng with custom duplicate check
  - `StoreReportRequest`: Validate venue_uuid OR (venue_name + venue_city), incident_date, time_of_day, description with PII detection
  - Custom error messages for better UX
- **API Resources:**
  - `VenueResource`: Return uuid, name, city, address, latitude, longitude, reports_count, created_at
  - `ReportResource`: Return uuid, incident_date, time_of_day, description, created_at, optionally venue
  - Collections include pagination metadata
- **Controllers:**
  - Inject services via constructor
  - Return API Resources for all responses
  - Use Response constants: `Response::HTTP_OK`, `Response::HTTP_NOT_FOUND`, etc.
  - Catch exceptions and return JSON errors
- **Routes:**
  - API routes: `/api/venues`, `/api/venues/search`, `/api/venues/{uuid}`, `/api/reports`, `/api/reports/{uuid}`
  - Page routes: `/`, `/map`, `/venue/{uuid}`, `/report`, `/about` (created in Phase 8)
  - Run `pa wayfinder:generate` after defining routes
- **Testing:**
  - Use JSON test methods: `getJson()`, `postJson()`, `putJson()`, `deleteJson()`
  - Use response helpers: `assertOk()`, `assertNotFound()`, `assertUnprocessable()`
  - Test happy path and error cases (404, 422 validation)
  - Test PII detection in report submission

**Deliverables:**
- FormRequests in `src/DrinkSafe/{Module}/Requests/`
- API Resources in `src/DrinkSafe/{Module}/Resources/`
- Controllers in `src/DrinkSafe/{Module}/Controllers/`
- `routes/drinksafe.php` with all routes
- Policies in `src/DrinkSafe/{Module}/Policies/` (placeholder)
- Feature tests in `src/DrinkSafe/{Module}/Tests/Feature/`

---

### Phase 6: Frontend Setup & Core Components

**Primary Agent:** `vue-frontend-developer`

**Before Starting - Load These Skills:**
- Skill: "vue-best-practices"
- Skill: "vue-inertia-frontend-guidelines"
- Skill: "inertia-vue-development"
- Skill: "tailwindcss-development"

**Key Patterns from Skills:**
- **Vue 3:** Use Composition API with `<script setup>` syntax
- **TypeScript:** Define types for all props, emits, and composables
- **Pinia:** State management with Composition API stores
- **Composables:** Reusable logic extracted to composables
- **Inertia.js:** Use `<Link>` for navigation, `useForm()` for forms
- **Tailwind CSS:** Utility-first styling, responsive design

**Key Responsibilities:**
- Set up Pinia stores for venues and reports
- Create TypeScript types matching backend API responses
- Create composables for reusable logic (useVenues, useReports, useSearch, useFilters, useMap)
- Create layout components (AppLayout, AppHeader, AppFooter, PoliceStrip)
- Create UI components (DisclaimerBanner, SearchBar, DateFilter)
- Configure Vue Leaflet for map functionality

**Critical Implementation Notes:**
- **Pinia Stores:**
  - `venueStore.ts`: State (venues, currentVenue, loading), Actions (fetchVenues, fetchVenueById, searchVenues), Getters (venuesByCity)
  - `reportStore.ts`: State (reports, recentReports, loading), Actions (fetchReports, fetchRecentReports, submitReport), Getters (reportsByVenue)
  - Use Composition API syntax: `defineStore('venue', () => { ... })`
- **TypeScript Types:**
  - Match backend API Resources exactly
  - `types/venue.ts`: Venue, VenueFilters, VenueCollection
  - `types/report.ts`: Report, TimeOfDay enum, ReportFilters, ReportSubmitData, PopulatedReport
  - `types/api.ts`: PaginationMeta, ApiResponse<T>, ApiError
- **Composables:**
  - `useVenues()`: Wraps venueStore, exposes reactive venues and methods
  - `useReports()`: Wraps reportStore
  - `useSearch()`: Debounced search with VueUse `useDebounceFn` (300ms delay)
  - `useFilters()`: Filter state management with apply/clear methods
  - `useMap()`: Leaflet map initialization and marker management
- **Layout Components:**
  - `AppLayout.vue`: Main layout wrapper with PoliceStrip, AppHeader, slot, AppFooter
  - `AppHeader.vue`: Navigation with Inertia `<Link>`, mobile hamburger menu
  - `PoliceStrip.vue`: Dismissible banner with localStorage persistence (use VueUse `useLocalStorage`)
- **Vue Leaflet:**
  - Import Leaflet CSS in `app.ts`
  - Fix default marker icons (import icon images explicitly)
  - Create `LeafletMap.vue` wrapper component with props: center, zoom, venues
  - Emit `@marker-click(venue)` event

**Deliverables:**
- `resources/js/stores/venueStore.ts`
- `resources/js/stores/reportStore.ts`
- `resources/js/types/{venue,report,api}.ts`
- `resources/js/composables/{useVenues,useReports,useSearch,useFilters,useMap}.ts`
- `resources/js/components/layout/{AppLayout,AppHeader,AppFooter,PoliceStrip}.vue`
- `resources/js/components/ui/{DisclaimerBanner,SearchBar,DateFilter,LeafletMap}.vue`

---

### Phase 7: Frontend Pages & Features

**Primary Agent:** `vue-frontend-developer`

**Before Starting - Load These Skills:**
- Skill: "vue-best-practices"
- Skill: "vue-inertia-frontend-guidelines"
- Skill: "inertia-vue-development"
- Skill: "tailwindcss-development"

**Key Patterns from Skills:**
- **Inertia Pages:** Receive props from backend, use `defineProps<{ ... }>()`
- **Navigation:** Use Inertia `<Link>` component, Wayfinder `route()` functions
- **Forms:** Use Inertia `useForm()` for validation errors and submission
- **Animations:** Use motion-vue for page transitions and animations
- **Responsive Design:** Mobile-first approach with Tailwind breakpoints

**Key Responsibilities:**
- Migrate all React pages to Vue.js (Home, Map, VenueDetail, SubmitReport, About)
- Create reusable venue and report components
- Integrate Wayfinder for type-safe routing
- Maintain design fidelity from React prototype
- Add animations and transitions
- Ensure mobile responsiveness

**Critical Implementation Notes:**
- **Home.vue:**
  - Props: `recentReports: Report[]` (from backend)
  - Sections: Hero with search, recent reports cards, "how it works" sidebar, police CTA
  - Search: Navigate to Map page with query param using Wayfinder
  - Animations: Fade in on mount with motion-vue
- **Map.vue:**
  - Props: `venues: Venue[]`, `reports: Report[]`, `filters?: ReportFilters`
  - Full-width Leaflet map with venue markers
  - Sidebar with filterable venue list
  - Click marker: Navigate to VenueDetail with Wayfinder
  - Filters: Update URL query params, reactive map updates
  - Loading skeleton while fetching
- **VenueDetail.vue:**
  - Props: `venue: Venue`, `reports: Report[]`, `reportsMeta: PaginationMeta`
  - Venue header with map preview
  - Report statistics (total count, most common time)
  - Paginated report list (10 per page)
  - CTA button: Navigate to SubmitReport with venue pre-filled
  - Transition animations for report list
- **SubmitReport.vue:**
  - Props: `venues: Venue[]`, `prefilledVenue?: Venue`
  - Multi-step form: Step 1 (location/time) → Step 2 (details) → Step 3 (success)
  - Step 1: Venue typeahead search (VenueSearchInput), date picker, time of day dropdown
  - If new venue: Show city input field
  - Step 2: Description textarea (20-1000 chars), PII warning banner, disclaimer
  - Use Inertia `useForm()` for validation errors
  - Step 3: Success message with navigation buttons
  - Step transitions with motion-vue
- **About.vue:**
  - Static content: Platform purpose, how it works, legal disclaimer, police contacts, support resources
  - Deep linking support: `#police` anchor for police section
- **Component Delegation:**
  - `ReportCard.vue`: Single report display (date badge, time icon, description preview, expand/collapse)
  - `ReportList.vue`: List of ReportCard with loading skeleton and empty state
  - `VenueCard.vue`: Venue display with report count, click to navigate
  - `VenueSearchInput.vue`: Typeahead search with dropdown, "Add new venue" option
  - `VenueMap.vue`: Wrapper around LeafletMap with custom marker popups
- **Wayfinder Integration:**
  - Import: `import { route } from '@/routes'`
  - Usage: `<Link :href="route('venues.show', { uuid: venue.uuid })">`
  - Generate: Run `pa wayfinder:generate` after route changes

**Deliverables:**
- `resources/js/pages/{Home,Map,VenueDetail,SubmitReport,About}.vue`
- `resources/js/components/reports/{ReportCard,ReportList}.vue`
- `resources/js/components/venues/{VenueCard,VenueSearchInput,VenueMap}.vue`
- All pages use Wayfinder for routing
- Mobile responsive design
- Animations and transitions

---

### Phase 8: Backend-Frontend Integration & Inertia Setup

**Primary Agent:** `laravel-backend-developer`

**Before Starting - Load These Skills:**
- Skill: "laravel-backend-guidelines"
- Skill: "inertia-vue-development"
- Skill: "wayfinder-development"

**Key Patterns from Skills:**
- **Inertia Controllers:** Return `Inertia::render('PageName', [...props])`
- **Props:** Pass API Resources, not raw Eloquent models
- **Shared Props:** Configure flash messages, validation errors in HandleInertiaRequests middleware
- **SSR:** Use @inertiajs/vite plugin for automatic SSR in dev mode
- **Wayfinder:** Generate TypeScript routes after defining page routes

**Key Responsibilities:**
- Create Inertia page controllers (render Vue pages)
- Define page routes separate from API routes
- Configure shared Inertia props (flash messages, errors)
- Test full-stack integration end-to-end
- Configure SSR (optional but recommended)

**Critical Implementation Notes:**
- **Page Controllers:**
  - `HomeController::__invoke()`: Render Home page, pass `recentReports: ReportCollection`
  - `MapController::__invoke()`: Render Map page, pass `venues: VenueCollection`, `reports: ReportCollection`, `filters: array`
  - `VenueDetailController::__invoke()`: Render VenueDetail page, pass `venue: VenueResource`, `reports: ReportCollection` (paginated), `reportsMeta: PaginationMeta`
  - `SubmitReportController::create()`: Render SubmitReport page, pass `venues: VenueCollection`, `prefilledVenue?: VenueResource`
  - `AboutController::__invoke()`: Render About page, no props (static)
  - All controllers inject services, fetch data, return `Inertia::render()`
- **Routes:**
  - Add page routes to `routes/drinksafe.php`: `/`, `/map`, `/venue/{uuid}`, `/report`, `/about`
  - Keep API routes separate (already defined in Phase 5)
  - Run `pa route:list` to verify no conflicts
  - Run `pa wayfinder:generate` to update TypeScript routes
- **Shared Props:**
  - Update `app/Http/Middleware/HandleInertiaRequests.php`
  - Add: `flash.success`, `flash.error` (from session)
  - Add: `errors` (validation errors bag)
  - Add: `auth.user` (null for now, future authentication)
  - Create `composables/useFlash.ts` to access flash messages
  - Use Sonner for toast notifications (auto-dismiss after 5s)
- **Full-Stack Testing:**
  - Test: All navigation works without page reloads (Inertia SPA behavior)
  - Test: Props passed from backend match frontend TypeScript types
  - Test: Validation errors display on frontend forms
  - Test: Flash messages show after form submission
  - Test: 404 errors render error page
- **SSR:**
  - Verify `@inertiajs/vite` plugin in `vite.config.ts`
  - SSR works automatically in Vite dev mode (no separate Node.js server)
  - Test: `curl http://localhost:8000/` returns rendered HTML (not blank)
  - For production: Configure SSR build in vite config

**Deliverables:**
- `src/DrinkSafe/Pages/{Home,Map,VenueDetail,SubmitReport,About}Controller.php`
- Updated `routes/drinksafe.php` with page routes
- Updated `HandleInertiaRequests.php` with shared props
- `resources/js/composables/useFlash.ts`
- Full-stack integration verified
- SSR configured (optional)

---

### Phase 9: Testing, Security & Production Readiness

**Primary Agent:** `test-engineer` (tests), `code-auditor` (security), `laravel-backend-developer` (fixes)

**Before Starting - Load These Skills:**
- Skill: "pest-testing"
- Skill: "quality-assurance"
- Skill: "bug-investigation"

**Key Patterns from Skills:**
- **Testing:** Comprehensive coverage (Unit + Feature + E2E), AAA pattern, factories for data
- **Security:** OWASP Top 10 compliance, rate limiting, input validation, no PII logging
- **Quality Assurance:** Code review, linting, performance testing, documentation review

**Key Responsibilities:**
- Complete test coverage (>90% backend, E2E for critical flows)
- Security audit (OWASP Top 10)
- Performance optimisation (queries, frontend, API)
- Production configuration (env, rate limiting, CORS)
- Final documentation (README, API docs, deployment guides)

**Critical Implementation Notes:**
- **Testing:**
  - Backend: Run `pa test --coverage`, target >90%
  - E2E: Create HomePageTest, MapPageTest, SubmitReportTest using Laravel Dusk or Playwright
  - Critical flows: Search → View Venue → Submit Report → Success
- **Security (OWASP Top 10):**
  - A01 (Access Control): Verify UUID prevents enumeration, policies for future admin
  - A03 (Injection): Verify all queries use Eloquent, test SQL injection attempts
  - A04 (Insecure Design): Add rate limiting to report routes (5 per hour per IP)
  - A05 (Misconfiguration): Verify APP_DEBUG=false, no secrets in version control
  - A06 (Vulnerable Components): Run `composer audit`, `npm audit`, fix vulnerabilities
  - A09 (Logging): Verify PII not logged, exceptions logged correctly
- **Performance:**
  - Database: Use Laravel Telescope to identify N+1 queries, add eager loading
  - Frontend: Run Lighthouse audit, target >90 performance score
  - API: Measure response times, target <100ms for all endpoints
  - Map: Implement marker clustering for >50 venues
- **Production Configuration:**
  - Create `.env.example` with production settings (APP_ENV=production, APP_DEBUG=false, HTTPS URLs)
  - Configure CORS in `config/cors.php`
  - Configure session security: `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`
  - Configure rate limiting in `app/Http/Kernel.php`: 60 req/min for API, 5 req/hour for report submission
- **Documentation:**
  - Update `README.md`: Project overview, installation, development, testing, deployment
  - Create `docs/api/endpoints.md`: Document all API endpoints with examples
  - Create `docs/frontend/component-library.md`: Document Vue components with props/emits
  - Create `docs/architecture/system-overview.md`: System diagram with request flow
  - Create `CONTRIBUTING.md`: Contribution guidelines, code review checklist
  - Create `CHANGELOG.md`: Version tracking

**Deliverables:**
- E2E tests in `tests/Browser/DrinkSafe/`
- Security audit report with mitigations
- Performance optimisation applied
- `.env.example` for production
- `docs/deployment/production-checklist.md`
- `docs/deployment/environment-setup.md`
- Comprehensive documentation updated

---

## Execution Checklist

Before starting ANY phase, the executing agent MUST:

- [ ] Read this AGENT_DELEGATION.md file
- [ ] Identify the primary agent for the phase
- [ ] Load ALL required skills using the Skill tool
- [ ] Only THEN begin implementation

---

## Handoff Protocol

When one phase completes and hands off to another agent:

### Outgoing Agent:
1. Mark phase complete in `react-to-vue-migration-tasks.md`
2. Document any deviations or decisions in `react-to-vue-migration-context.md`
3. Create commit with phase completion message
4. Notify next agent (if different specialization)

### Incoming Agent:
1. Read AGENT_DELEGATION.md for next phase requirements
2. Load required skills BEFORE starting
3. Review files from previous phase
4. Read context document for any deviations
5. Continue execution

---

## Parallel Execution Opportunities

Phases can be executed in parallel where dependencies allow:

**Backend Track (Phases 2-5):**
- Phase 2: Database Schema
- Phase 3: Models & Relationships (depends on Phase 2)
- Phase 4: Services (depends on Phase 3)
- Phase 5: API Layer (depends on Phase 4)

**Frontend Track (Phases 6-7):**
- Phase 6: Frontend Setup (can start after Phase 1)
- Phase 7: Frontend Pages (depends on Phase 6)

**Integration Track (Phase 8):**
- Phase 8: Backend-Frontend Integration (depends on Phases 5 and 7)

**Quality Track (Phase 9):**
- Phase 9: Testing & Security (depends on Phase 8)

**Recommendation:** Execute Phases 2-5 and Phases 6-7 in parallel by assigning to different agents.

---

## Skill Loading Best Practices

### When to Load Skills

**Always load skills:**
- At the start of each phase (before any implementation)
- When switching between backend and frontend work
- When the phase explicitly requires specific patterns

**How to load skills:**
```
Use the Skill tool with the skill name:
- Skill: "laravel-backend-guidelines"
- Skill: "pest-testing"
- Skill: "vue-best-practices"
```

**What happens after loading:**
- Agent receives detailed patterns and conventions from the skill
- Agent references skill resources during implementation
- Code follows consistent patterns across the project

### Skill Descriptions

- **laravel-backend-guidelines:** Comprehensive backend patterns (controllers, services, models, policies, FormRequests, migrations, testing)
- **pest-testing:** PestPHP testing patterns (AAA pattern, factories, assertions, feature vs unit tests)
- **vue-best-practices:** Vue 3 best practices (Composition API, TypeScript, component patterns)
- **vue-inertia-frontend-guidelines:** Vue + Inertia.js integration patterns
- **inertia-vue-development:** Inertia.js v3 client-side patterns (pages, forms, navigation)
- **tailwindcss-development:** Tailwind CSS utility patterns and responsive design
- **wayfinder-development:** Laravel Wayfinder typed route generation
- **quality-assurance:** Pre-QA verification checklist and quality standards
- **bug-investigation:** Systematic bug investigation methodology

---

## Success Criteria

Each phase is considered complete when:

1. All tasks in the phase are completed (checked off in tasks document)
2. All tests pass (if applicable)
3. Code follows conventions from loaded skills
4. Documentation updated (if applicable)
5. Review checkpoint completed and signed off
6. No Critical or High priority issues remain

---

**Document Status:** Active Reference
**Created:** 2026-05-14
**Last Updated:** 2026-05-14
