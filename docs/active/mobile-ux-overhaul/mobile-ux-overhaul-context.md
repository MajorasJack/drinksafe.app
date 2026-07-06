# Mobile UX Overhaul — Context

**Last Updated: 2026-06-30**

## Key Files

| Area | File(s) |
|------|---------|
| Report form (steps, validation) | `resources/js/components/report/ReportForm.vue` |
| Report page wrapper / submit handler | `resources/js/pages/SubmitReport.vue` |
| Report submit store/composable | `resources/js/composables/useReports.ts`, report store (`reportStore.ts`) |
| Reusable venue autocomplete | `resources/js/components/venue/VenueSearchInput.vue`, `composables/useSearch.ts` |
| Report types | `resources/js/types/report.ts` |
| Backend validation | `src/DrinkSafe/Reports/Requests/StoreReportRequest.php` |
| Report model | `src/DrinkSafe/Reports/Models/Report.php` |
| Report service (persists) | `src/DrinkSafe/Reports/Services/ReportService.php` |
| Report resource | `src/DrinkSafe/Reports/Resources/ReportResource.php` |
| Report factory | `database/factories/DrinkSafe/ReportFactory.php` |
| Reports migration | `database/migrations/drinksafe/2026_05_14_175836_create_reports_table.php` |
| Map page + map | `resources/js/pages/Map.vue`, `components/map/VenueMap.vue`, `components/map/HeatmapControls.vue` |
| Layout / nav / footer | `components/layout/AppLayout.vue`, `AppHeader.vue`, `AppFooter.vue`, `PoliceStrip.vue` |
| Content pages | `resources/js/pages/About.vue`, `Contact.vue`, `Support.vue` |
| Design tokens / CSS | `resources/css/app.css` (Tailwind v4, `--color-brand-teal`, Instrument Sans) |
| Reusable UI (Reka UI) | `resources/js/components/ui/*` — incl. `collapsible`, `sheet`, `card`, `alert`, `select` |

## Key Decisions

- **Sequencing:** P0 report-flow repair (Phases 1–3) before mobile polish (4–8).
- **Time field:** add a persisted optional exact time → new nullable `incident_time` column (migration in `database/migrations/drinksafe/`). Keep Time-of-Day band but make it optional.
- **Pickers:** styled native `<input type=date|time>` (best mobile UX), fix dark-on-dark via `color-scheme: dark`, enforce `max=today`.
- **Validation single source of truth:** `description` min = 20 (matches backend). Align frontend copy + counter.
- **Validation UX:** replace silently-disabled Next with on-attempt inline per-field errors; surface backend 422 `response.data.errors`.
- **No new dependencies.** Reuse installed Reka UI, Leaflet, `VenueSearchInput`/`useSearch`, Turnstile.

## Status (2026-06-30)

All 8 phases implemented and verified. P0 report-flow repair complete and tested (33 tests / 76 assertions green via sqlite :memory:). Code-auditor pass: approved; HIGH + both MEDIUM findings fixed. Pint + eslint + build all clean.

**Outstanding (requires user action):**
- Run the new migration `2026_06_30_000000_add_incident_time_to_reports_table.php` on real environments (not run here per DB-safety rules).
- Browser tests (`SubmitReportPageTest`): **run in CI, no local hacks.**
  - `.github/workflows/tests.yml` now installs browsers (`npx playwright install --with-deps chromium`, with a cache step) before `./vendor/bin/pest`. That was the only missing piece — CI already built assets and ran the suite, but never installed Playwright's browsers.
  - `package.json` left at the repo's `playwright ^1.60.0` (an earlier `1.59.1` pin was reverted — it was based on a sandbox artifact, not a real incompatibility; both satisfy the plugin's `>= 1.59.1` requirement).
  - `ReportForm` keeps the resilience guard: it only injects the external Turnstile `<script>` when a site key is configured (so tests/local without Turnstile don't wait on an external resource).
  - Local gotcha (not a CI issue): browser tests need the app's JS served — either `npm run dev` running, or built assets with **no `public/hot`** (which is gitignored, so CI is fine). The Playwright *installer* itself can hang on some local networks (a known Playwright bug; the CDN/files are fine) — CI runners don't hit this.

**Optional follow-ups (LOW, from audit):** surface a turnstile server error inline on step 3; paint the notch safe-area with the PoliceStrip colour instead of page background.

## Constraints (global rules)

- Modular architecture: code lives in the relevant `src/DrinkSafe/<Module>/...`.
- PHP 8+ syntax, return types, typehints, docblocks, `sprintf`, module-specific custom exceptions (never `\Exception`).
- Models: `$fillable`/`$guarded`, factories updated with new migrations.
- Tests: PestPHP, `it()`/`test…` camelCase, AAA, `fake()`, JSON methods + response helpers, single-assertion focus, no comments.
- **DB safety:** never run migrations/seeders/DB-backed tests without explicit user approval; verify DB name contains test/testing first.
