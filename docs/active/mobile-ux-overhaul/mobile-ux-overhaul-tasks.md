# Mobile UX Overhaul — Tasks

**Last Updated: 2026-07-05**

Status: `[ ]` pending · `[~]` in progress · `[x]` done

> All 8 implementation phases below are complete. Genuinely-remaining work is tracked in **"Open items"** at the bottom of this file.

## Phase 0 — Scaffold tracking docs (documentation-architect)
- [x] Create `docs/active/mobile-ux-overhaul/` with plan, context, tasks, AGENT_DELEGATION

## Phase 1 — Report flow BACKEND (P0) — laravel-backend-developer
**Skills:** laravel-best-practices, pest-testing
- [x] 1.1 Migration `2026_06_30_000000_add_incident_time_to_reports_table.php` (nullable `time` after `incident_date`)
- [x] 1.2 `Report.php`: add `incident_time` to `$fillable` + docblock `@property` (stored as string, normalised in resource)
- [x] 1.3 `StoreReportRequest.php`: `incident_time` rule `nullable|date_format:H:i`; kept `description` min 20; added message
- [x] 1.4 `ReportService.php`: persist `incident_time`
- [x] 1.5 `ReportResource.php` (Carbon→H:i) + `types/report.ts`: expose `incident_time`
- [x] 1.6 `ReportFactory.php`: `incident_time` default (optional) + `withTime`/`withoutTime` states
- [x] Pint passed. Review checkpoint — migration NOT run until user approves

## Phase 2 — Report flow FRONTEND (P0) — vue-frontend-developer
**Skills:** inertia-vue-development, vue-inertia-frontend-guidelines, tailwindcss-development
- [x] 2.1 Wire `VenueSearchInput` into Step 1 (select/clear) + selected-venue card with Change
- [x] 2.2 Relabel create-venue → "Can't find your venue? Add it" fallback
- [x] 2.3 Inline per-field validation via touched-tracking (no silent dead-ends; Next always clickable)
- [x] 2.4 Surface backend 422 errors (typed `ReportSubmissionError` in store) + jump to offending step
- [x] 2.5 Align min length to 20 (validity, placeholder, counter)
- [x] 2.6 Make Time of Day optional ("Not sure"); removed from step2 gating
- [x] 2.7 Styled native date picker (max=today, `dark:[color-scheme:dark]`, h-11)
- [x] 2.8 Optional time input → `incident_time` payload (omitted when blank)
- [x] 2.9 Step 3 summary shows date + optional time + time-of-day label
- [x] eslint + prettier clean; `npm run build` green. Review checkpoint pending manual 375px run

## Phase 3 — Report flow TESTS (P0) — test-engineer
**Skills:** pest-testing, laravel-best-practices
- [x] 3.1 Feature tests (`ReportControllerStoreTest`): incident_time accepted/omitted; future date 422; description min 20; malformed time; PII — uses `Turnstile::fake()`
- [x] 3.2 Unit tests (`ReportIncidentTimeTest`): schema column; model persistence; resource H:i format + null
- [x] 3.3 Updated existing browser test (`SubmitReportPageTest`) to new UI + added optional-time/validation-error cases
- [x] Ran feature + unit: 10/10 pass (20 assertions); no regressions (27 pass in Reports+Venues). Browser tests not run (need Playwright/dev server)

## Phase 4 — Map mobile UX — vue-frontend-developer
- [x] 4.1 Fixed cut-off button: corrected layout chain (`AppLayout` flex column + `min-h-[100dvh]`), Map now flex-fills (no `100vh-80px` guess), button uses `bottom-[calc(1.5rem+env(safe-area-inset-bottom))]` + `min-h-12`
- [x] 4.2 Map/list toggle retained (works) — outer div made `relative` so the absolute sidebar resolves correctly; singular/plural "Venue(s)" label
- [x] 4.3 Heatmap overlay reviewed — already a compact backdrop-blur card; left as-is (low risk)
- [x] eslint clean; build green. Review checkpoint pending device walkthrough

## Phase 5 — Navigation & layout — vue-frontend-developer
- [x] 5.1 Added Support Us + Contact to header nav (desktop + mobile)
- [x] 5.2 Active-route styling via `usePage().url` + `aria-current`
- [x] 5.3 Mobile menu → Reka `Sheet` drawer with large tap targets + "Share a Report" CTA
- [x] Build/lint green. No existing nav-test breakage (ambiguity pre-existed)

## Phase 6 — Content pages density — vue-frontend-developer
- [x] 6.1 Contact FAQ → Reka `Collapsible` accordions (collapsed by default)
- [x] 6.2 About: `space-y-8 md:space-y-12`, compact icon-left "How to Use" rows on mobile, resources side-by-side on sm+
- [x] 6.3 Support: tightened hero/CTA (responsive icon/heading), compact "support goes" rows on mobile
- [x] 6.4 Responsive hero type scale (text-3xl sm:text-4xl) + spacing sweep across all three
- [x] Build/lint/prettier green; no tests on these pages

## Phase 7 — Global mobile polish — vue-frontend-developer
- [x] 7.1 Global `color-scheme: light/dark` in app.css (fixes all native pickers incl. Map DateFilter); ≥44px CTAs via min-h-12; focus rings already global
- [x] 7.2 `viewport-fit=cover` added; top safe-area inset on layout root; bottom inset on map button
- [x] 7.3 Responsive hero type scale applied; container rhythm tightened on content pages
- [x] Build green

## Phase 8 — QA & standards audit — code-auditor
**Skills:** quality-assurance, bug-investigation
- [x] Independent `code-auditor` pass on all 18 changed files — approved with notes
- [x] Fixed HIGH: removed `text-white` from VenueSearchInput (was invisible in light mode)
- [x] Fixed MEDIUM: `incidentTime` accessor normalises to H:i (driver-safe); resource simplified; Carbon import dropped
- [x] Fixed MEDIUM: typehinted `withValidator(Validator)` + closure
- [x] Fixed LOW: removed stray `console.log` (Map); dropped can't-fail `time_of_day` from server-error routing
- [x] Re-verified: pint passed, 33 tests/76 assertions green, eslint clean, build green
- [x] Final sign-off: APPROVED. Remaining LOWs (turnstile inline surface, notch paint colour) noted as optional follow-ups

---

## Open items (what's actually left)

**Blocking / needed to close out:**
- [x] Run the `incident_time` migration — DONE, verified on live MySQL `drinksafe` schema (`incident_time time NULL` after `incident_date`).
- [ ] **Execute browser tests** (`SubmitReportPageTest`) — Playwright pinned to `1.59.1` to match `pest-plugin-browser@4.3.1`; run `npx playwright install chromium` + `php artisan test tests/Browser/SubmitReportPageTest.php` in a network-capable env (CI/local). Not runnable in this sandbox (browser-binary download stalls).
- [ ] **Commit the work** — 22 changed/new files, currently uncommitted on `main`. Needs a branch + commit (+ optional PR).

**Deferred review checkpoints (manual):**
- [ ] Phase 2 — manual 375px walkthrough of the report flow (search venue → future-date error → <20-char error → submit with optional time).
- [ ] Phase 4 — device walkthrough of the map (button fully visible, list reachable, toggle obvious).
- [ ] Phases 5–7 — quick cross-page mobile pass (nav drawer, content-page density, native pickers in light+dark).

**Optional follow-ups (LOW, from audit — deferrable):**
- [ ] Surface a Turnstile *server* error inline on step 3 (currently toast-only).
- [ ] Paint the notch safe-area with the PoliceStrip colour instead of page background.
