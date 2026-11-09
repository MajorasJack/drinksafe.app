# Admin Area Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give Drink Safe's single admin a browser-based admin area: a dashboard, a safer review queue, report search, venue editing, partner and coverage pages, targeted cache invalidation and enforced two-factor login.

**Architecture:** Each feature lives in its owning module under `src/DrinkSafe/*` (Reports, Venues, Partners, Analytics, Heatmap, Shared) with an Inertia controller, FormRequests, the existing `moderate` policy, and a Vue page under `resources/js/pages/admin/`. Admin queries opt out of the public `PublishedScope`/`PublishedVenueScope` with `withoutGlobalScope`. Every admin route sits in one route group with `auth`, `auth.session`, a new `two-factor.confirmed` middleware and `can:moderate`.

**Tech Stack:** Laravel 13, Fortify, Inertia v3, Vue 3 `<script setup>` + TypeScript, Tailwind 4, the UI components in `resources/js/components/ui`, Wayfinder, Pest 4 (feature, unit and browser tests), Leaflet with CARTO tiles (already used by `LeafletMap.vue`).

**Spec:** `docs/superpowers/specs/2026-11-09-admin-area-design.md`

## Global Constraints

- No new Composer or npm dependencies.
- The repo is public. No server addresses, hostnames, users, paths, PHP builds or hosting details in code, comments, commits or PRs.
- No admin page may show IP addresses, user agents, session data, log lines, or partner scans finer than daily totals.
- Never call `Cache::flush()`. Invalidation is targeted.
- Every admin route uses `auth`, `auth.session`, `two-factor.confirmed` and `can:moderate`. Each one has tests for guest (redirect to login), non-admin (403), admin without confirmed two-factor (redirect to two-factor setup) and admin (OK).
- Public pages keep their behaviour: `PublishedScope` and `PublishedVenueScope` stay global.
- House rules from `CLAUDE.md` and the owner's standards apply: return types and parameter types everywhere, FormRequests for validation, Policies for authorisation, `$fillable` only, `sprintf` rather than concatenation, British English, docblocks on public methods, Pest `it()`/`test()` with readable descriptions, AAA, `fake()`, JSON test helpers and response assertions for JSON endpoints, no comments in tests, and all-or-nothing named arguments on our own methods.
- Commits and PR text follow the `writing-in-tinks-voice` skill, with no AI attribution. One PR per task. Each PR gets a tink-review (`~/.claude/skills/automattic/tink-review.md`) before merging.
- Before each PR: `npm run build`, `php -d memory_limit=1G vendor/bin/pest --compact`, `vendor/bin/pest --compact tests/Browser`, `vendor/bin/pint --dirty --format agent`, `npm run lint`.

## Review Focus

1. **A takedown on a venue that still has other published reports** must not offer to hide the venue, and a takedown that leaves it empty always asks. Test: `it('does not offer to hide a venue that still has published reports')` in Task 3.
2. **Bulk actions given a mix of pending, already decided and nonexistent UUIDs** must act only on pending ones, report how many changed, and never 500. Test: `it('skips reports that are no longer pending in a bulk review')` in Task 3.
3. **Venue edits that move coordinates onto or off the London default** must update the warning and clear the venue's cached safety score and the heatmap. Test: `it('clears the safety score and heatmap version when a venue moves')` in Task 5.
4. **Report search with no filters, every filter, and a description search containing `%` or `_`** must return the right rows and treat those characters literally. Test: `it('treats percent and underscore in the description search literally')` in Task 4.
5. **Partner date ranges with `from` after `to`, or blank** must fail validation (or default to the last 30 days when blank), never return a 500 or an empty-but-misleading table. Test: `it('rejects a range that ends before it starts')` in Task 6.

---

### Task 1: Admin shell, navigation and enforced two-factor

**Files:**
- Create: `app/Http/Middleware/EnsureTwoFactorConfirmed.php` (alias `two-factor.confirmed` in `bootstrap/app.php`)
- Modify: `app/Http/Middleware/HandleInertiaRequests.php` (share `auth.user.is_admin`), `routes/drinksafe.php` (one `admin.` group), `resources/js/components/AppSidebar.vue`, `resources/js/types/` (User gets `is_admin: boolean`), `config/fortify.php` (`'home' => '/admin'`), `src/DrinkSafe/Reports/Commands/MakeAdminCommand.php` (`--reset-two-factor`)
- Test: `tests/Feature/Admin/AdminAccessTest.php`, `src/DrinkSafe/Reports/Tests/Feature/MakeAdminCommandTest.php`

**Interfaces:**
- Produces: route group prefix `admin`, name prefix `admin.`, middleware `['auth', 'auth.session', 'two-factor.confirmed', 'can:moderate,'.Report::class]`. Later tasks add routes inside it. `GET /admin` is named `admin.dashboard` (it renders a stub `admin/Dashboard` page here; Task 8 fills it). The existing queue routes move into the group, keeping the names `admin.reports.index` and `admin.reports.update`.
- Produces: sidebar items Dashboard (`admin.dashboard`), Review queue (`admin.reports.index`), Reports (`admin.reports.search`, Task 4), Venues (`admin.venues.index`, Task 5), Partners (`admin.partners.index`, Task 6), Coverage (`admin.coverage`, Task 6). Each item is shown only when its route exists.

- [ ] **Step 1: Write failing tests.** In `AdminAccessTest`, a dataset over `admin.dashboard` and `admin.reports.index` with these tests:
  - `it('redirects guests to login')` asserts a redirect to `route('login')`.
  - `it('forbids users who are not admins')` asserts `assertForbidden()`.
  - `it('sends an admin without confirmed two-factor to set it up')` asserts a redirect to `route('security.edit')`, or whatever the existing two-factor settings route is called, plus a flash message key `status` containing "two-factor".
  - `it('lets a confirmed admin in')` asserts `assertOk()`.
  - `it('shares whether the user is an admin')` asserts the Inertia prop `auth.user.is_admin` is `true` for an admin.
  - `it('sends admins to the admin dashboard after login')` asserts the login redirect is `/admin`.

  In `MakeAdminCommandTest`, `it('resets two-factor for an admin')` asserts `two_factor_secret`, `two_factor_recovery_codes` and `two_factor_confirmed_at` are null afterwards, and that `remember_token` changed.
- [ ] **Step 2: Run them and see them fail.** `php -d memory_limit=1G vendor/bin/pest --compact --filter=AdminAccess`, expected FAIL.
- [ ] **Step 3: Implement.**
  - `EnsureTwoFactorConfirmed::handle(Request $request, Closure $next): Response` redirects when `$request->user()->two_factor_confirmed_at === null`.
  - `--reset-two-factor` reuses the session-ending code from `--password`. It refuses `--no-interaction` only if a prompt is needed (none is), and it can't be combined with `--revoke` or `--password`.
  - Settings routes stay outside the group so setup stays reachable.
- [ ] **Step 4: Run the tests and see them pass.** Run the full suite too, because the moved queue routes have existing tests.
- [ ] **Step 5: Commit.** `git commit -m "Require two-factor for the admin area and give it its own navigation"`

### Task 2: Targeted cache invalidation

**Files:**
- Create: `src/DrinkSafe/Shared/Services/PublicCacheVersion.php`
- Modify: `src/DrinkSafe/Heatmap/Services/HeatmapService.php` (`buildCacheKey` includes the version, and `invalidateCache()` bumps it, no flush), `src/DrinkSafe/Analytics/Services/TemporalAnalyticsService.php` (same), `src/DrinkSafe/Reports/Observers/ReportObserver.php` (implement `ShouldHandleEventsAfterCommit`, and on a status change call `PublicCacheVersion::bump()` as well as the existing safety score forget)
- Test: `src/DrinkSafe/Shared/Tests/Unit/PublicCacheVersionTest.php`, additions to the existing Heatmap and Analytics feature tests

**Interfaces:**
- Produces: `PublicCacheVersion::current(): int`, `PublicCacheVersion::bump(): int`. It's stored under the cache key `public-cache-version`, forever. It's injected, not static, so tests can use the real cache.

- [ ] **Step 1: Write failing tests.**
  - `it('starts at one and goes up by one when bumped')`.
  - `it('serves fresh heatmap data after a report is taken down')`: publish 3 reports in one cell, call the heatmap endpoint, reject one with `reviewReport`, call again, and assert the cell is gone.
  - `it('does not clear rate limiter counters when public caches are invalidated')`: hit a throttled route, bump the version, and assert `X-RateLimit-Remaining` keeps decreasing.
  - `it('clears caches only after the review transaction commits')`: wrap `reviewReport` in a rolled-back transaction and assert the version is unchanged.
- [ ] **Step 2: Run and fail.**
- [ ] **Step 3: Implement.** Remove the `Cache::flush()` calls from both services.
- [ ] **Step 4: Run and pass, full suite.**
- [ ] **Step 5: Commit.** `git commit -m "Invalidate the heatmap and analytics caches by version instead of flushing"`

### Task 3: Review queue improvements

**Files:**
- Modify: `src/DrinkSafe/Reports/Services/ReportService.php`:
  - add `DEFAULT_LATITUDE = 51.5074` and `DEFAULT_LONGITUDE = -0.1278` as public constants, used where a submission has no coordinates
  - `getPendingReports` becomes a paginator
  - add `reviewMany(array $uuids, ReportStatus $status): int`
- Modify: `src/DrinkSafe/Reports/Controllers/ReportModerationController.php` (pending paginator, flags, bulk endpoint), `resources/js/pages/admin/Reports.vue`
- Modify: `src/DrinkSafe/Venues/Models/Venue.php` and a migration adding a nullable `unpublished_at` timestamp (indexed) to venues
- Create: `src/DrinkSafe/Reports/Requests/BulkReviewReportsRequest.php`, `src/DrinkSafe/Reports/Services/ReportFlagService.php`, `resources/js/components/admin/VenueMiniMap.vue` (a small read-only Leaflet map with CARTO tiles, one marker)
- Test: `src/DrinkSafe/Reports/Tests/Feature/ReportModerationControllerTest.php`, `src/DrinkSafe/Reports/Tests/Unit/ReportFlagServiceTest.php`, `tests/Browser/AdminReviewQueueTest.php`

**Interfaces:**
- Produces:
  - `ReportFlagService::flagsFor(Report $report): array<int, string>`, returning any of `personal_details_removed`, `new_venue_several_pending`, `venue_report_spike`, `default_coordinates`.
  - `config('drinksafe.admin.report_spike_threshold')`, default `5` per 24 hours.
  - `Venue::isAtDefaultCoordinates(): bool`.
  - `Venue::wasUnpublishedByAdmin(): bool`, true when `unpublished_at` isn't null.
- Produces: route `POST admin/reports/bulk` named `admin.reports.bulk`, body `{uuids: string[], status: 'published'|'rejected', hide_venues?: string[]}`. It redirects back with flash `status` "N reports published" or "N reports rejected".
- Changes `reviewReport(string $uuid, ReportStatus $status, bool $hideVenueIfEmpty = false): Report`:
  - Publishing auto-publishes the venue only when it was never unpublished by the admin.
  - Publishing a report whose venue was unpublished by the admin throws `VenueUnpublishedException` (a new exception in Reports/Exceptions). The controller turns it into a validation error "This venue is unpublished. Publish the venue first."
  - With `$hideVenueIfEmpty` and a status of rejected, if the venue then has no published reports, the service sets `published_at = null` and `unpublished_at = now()`.

- [ ] **Step 1: Write failing tests.**
  - `it('shows the coordinates and a default-location warning for a pending report')` asserts the props include the venue's `latitude`, `longitude` and `is_at_default_coordinates: true` for a venue at the default.
  - `it('paginates pending reports')`: 60 pending, and page 2 has 10.
  - `it('publishes and rejects several pending reports at once')`.
  - `it('skips reports that are no longer pending in a bulk review')`: a mix of pending, published and random UUIDs, and only the pending ones change. The count in the flash matches. No 500.
  - `it('offers to hide a venue when its last published report is taken down')` asserts the props for the takedown include `would_empty_venue: true` for that venue.
  - `it('hides the venue when asked during a takedown')`.
  - `it('keeps the venue when not asked during a takedown')`.
  - `it('does not offer to hide a venue that still has published reports')`.
  - `it('refuses to publish a report whose venue the admin unpublished')` asserts a validation error with exactly "This venue is unpublished. Publish the venue first." and the report stays rejected.
  - Unit tests for each flag.
  - Browser test: `it('asks whether to hide the venue before taking down its last report')`.
- [ ] **Step 2: Run and fail.**
- [ ] **Step 3: Implement.**
  - `reviewMany` loops over `reviewReport` inside one transaction per report, skipping non-pending reports.
  - The flags are computed in the controller for the current page only, not N+1: eager-load the venue, and count pending and 24-hour reports per venue with grouped queries.
- [ ] **Step 4: Run and pass, full suite and browser.**
- [ ] **Step 5: Commit.** `git commit -m "Show each venue's location in the review queue and add bulk review"`

### Task 4: Report search

**Files:**
- Create: `src/DrinkSafe/Reports/Controllers/AdminReportSearchController.php`, `src/DrinkSafe/Reports/Requests/AdminReportSearchRequest.php`, `resources/js/pages/admin/ReportSearch.vue`
- Modify: `routes/drinksafe.php` (`GET admin/reports/all` named `admin.reports.search`), `resources/js/pages/admin/Reports.vue` (the "Recent decisions" heading links to the search page, and the hard-coded "last 20" text goes)
- Test: `src/DrinkSafe/Reports/Tests/Feature/AdminReportSearchControllerTest.php`

**Interfaces:**
- Consumes: `reviewReport()` and `VenueUnpublishedException` from Task 3.
- Produces: query params `venue` (string, matched on name), `city`, `status` (`pending|published|rejected`), `from`, `to` (dates, inclusive, incident date) and `q` (description). Paginated 25 per page, newest `created_at` first. Each row carries `venue.admin_url` once Task 5 exists. Until then, link to the public venue page.

- [ ] **Step 1: Write failing tests.**
  - `it('lists reports of every status')`.
  - One test per filter: `it('filters by status')`, `by city`, `by venue name`, `by incident date range`, `by description text`.
  - `it('treats percent and underscore in the description search literally')`.
  - `it('rejects a date range that ends before it starts')`.
- [ ] **Step 2: Run and fail.**
- [ ] **Step 3: Implement.** Use the full-text index when the connection isn't sqlite. Otherwise use `LIKE` with `%` and `_` escaped.
- [ ] **Step 4: Run and pass.**
- [ ] **Step 5: Commit.** `git commit -m "Add a searchable list of every report to the admin area"`

### Task 5: Venues admin

**Files:**
- Create:
  - `src/DrinkSafe/Venues/Controllers/AdminVenueController.php` (index, show, update, publish, unpublish)
  - `src/DrinkSafe/Venues/Requests/AdminUpdateVenueRequest.php`
  - `resources/js/pages/admin/Venues.vue`
  - `resources/js/pages/admin/VenueShow.vue`
- Modify: `routes/drinksafe.php` (`admin.venues.index|show|update|publish|unpublish`), `src/DrinkSafe/Venues/Services/VenueService.php` (`updateVenue` clears that venue's safety score and bumps `PublicCacheVersion` when the coordinates change; add `publishVenue(string $uuid): Venue` and `unpublishVenue(string $uuid): Venue`)
- Test: `src/DrinkSafe/Venues/Tests/Feature/AdminVenueControllerTest.php`

**Interfaces:**
- Consumes: `PublicCacheVersion::bump()` (Task 2), `Venue::isAtDefaultCoordinates()` and `unpublished_at` (Task 3).
- Produces:
  - `unpublishVenue` sets `published_at = null` and `unpublished_at = now()`.
  - `publishVenue` sets `published_at = now()` and `unpublished_at = null`. It doesn't publish any reports.
  - Index filters: `q` (name), `city`, `state` (`published|unpublished|orphaned`, where orphaned means unpublished with no pending report).
  - The show page lists the venue's reports in every status, using the same row component and actions as Task 4.

- [ ] **Step 1: Write failing tests.**
  - `it('lists published and unpublished venues')`.
  - `it('filters to unpublished venues with no pending report')`.
  - `it('updates a venue and keeps its slug')`.
  - `it('refuses a name and city that another venue already has')` asserts a validation error naming the other venue.
  - `it('rejects coordinates outside valid latitude and longitude')`.
  - `it('clears the safety score and heatmap version when a venue moves')`.
  - `it('unpublishes a venue and hides it from the public site')` asserts the public venue page is `assertNotFound()`.
  - `it('publishes a venue without publishing its reports')`.
- [ ] **Step 2: Run and fail.**
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run and pass.**
- [ ] **Step 5: Commit.** `git commit -m "Let the admin edit, publish and unpublish venues"`

### Task 6: Partners and coverage pages

**Files:**
- Create:
  - `src/DrinkSafe/Partners/Services/PartnerScanReport.php`
  - `src/DrinkSafe/Partners/Controllers/AdminPartnerController.php` (index, update)
  - `src/DrinkSafe/Partners/Requests/AdminPartnerRangeRequest.php`
  - `src/DrinkSafe/Partners/Requests/AdminUpdatePartnerLinkRequest.php`
  - `resources/js/pages/admin/Partners.vue`
  - `src/DrinkSafe/Reports/Services/CoverageReport.php`
  - `src/DrinkSafe/Reports/Controllers/AdminCoverageController.php`
  - `resources/js/pages/admin/Coverage.vue`
- Modify: `PartnerReportCommand` and `CoverageCommand` to call the new services (their output is unchanged, and their existing tests still pass), `routes/drinksafe.php` (`admin.partners.index|update`, `admin.coverage`)
- Test: `src/DrinkSafe/Partners/Tests/Feature/AdminPartnerControllerTest.php`, `src/DrinkSafe/Reports/Tests/Feature/AdminCoverageControllerTest.php`

**Interfaces:**
- Produces:
  - `PartnerScanReport::totals(CarbonImmutable $from, CarbonImmutable $to): Collection` of `{code, label, destination, active, total}`. Sorted by total descending, zero-scan links included, inclusive UK dates.
  - `CoverageReport::forCities(array $cities): array` of `{city, venues, published_reports, pending_reports}`. Same matching as the command, including the Newcastle alias.
- `update` edits `label`, `destination` and `active` only, using `PartnerLink`'s existing destination rule.

- [ ] **Step 1: Write failing tests.**
  - `it('totals scans per partner over the last 30 days by default')`.
  - `it('totals scans over a chosen range inclusively')`.
  - `it('rejects a range that ends before it starts')`.
  - `it('switches a partner link off')`.
  - `it('refuses an external destination')`.
  - `it('shows coverage for the default cities')`.
  - `it('shows coverage for cities typed by the admin')`.
  - The existing command tests still pass.
- [ ] **Step 2: Run and fail.**
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run and pass.**
- [ ] **Step 5: Commit.** `git commit -m "Show partner scans and coverage in the admin area"`

### Task 7: Dashboard

**Files:**
- Create: `src/DrinkSafe/Shared/Controllers/AdminDashboardController.php`, `src/DrinkSafe/Shared/Services/AdminDashboardStats.php`, `resources/js/pages/admin/Dashboard.vue`
- Modify: delete the starter `resources/js/pages/Dashboard.vue` and its `/dashboard` route, after checking nothing else links to it, and point any remaining links at `admin.dashboard`
- Test: `tests/Feature/Admin/AdminDashboardTest.php`

**Interfaces:**
- Consumes: `PublicCacheVersion::bump()` (Task 2) and `PartnerScanReport::totals()` (Task 6).
- Produces:
  - `AdminDashboardStats::summary(): array{pending_count:int, oldest_pending_at:?string, received_per_day: array<string,int>, published_30d:int, rejected_30d:int, top_partners: array, orphaned_venues:int}`.
  - `received_per_day` covers the last 30 UK days, zero-filled.
  - Route `POST admin/caches/refresh` named `admin.caches.refresh`. It bumps the version and redirects back with "Public caches refreshed."

- [ ] **Step 1: Write failing tests.**
  - `it('summarises the review queue')`.
  - `it('counts reports received per day for the last 30 days including empty days')`.
  - `it('lists the top partners including switched-off ones')`.
  - `it('refreshes the public caches')`.
- [ ] **Step 2: Run and fail.**
- [ ] **Step 3: Implement.** The per-day strip is plain Tailwind bars, with no chart library.
- [ ] **Step 4: Run and pass.**
- [ ] **Step 5: Commit.** `git commit -m "Replace the placeholder dashboard with an admin summary"`
