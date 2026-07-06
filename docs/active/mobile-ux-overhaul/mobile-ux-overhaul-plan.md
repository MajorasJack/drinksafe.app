# Drink Safe — Mobile UX Overhaul & Report Flow Repair

**Last Updated: 2026-06-30**

## Context

Drink Safe is an anonymous platform for reporting/viewing venue spiking incidents. ~99% of usage is mobile, on-the-go, yet the current mobile experience has both **functional blockers** and **cosmetic gaps**. The most serious problem is not cosmetic: **the report submission flow is broken and users cannot complete it.**

Root causes confirmed in code:

1. **Silent dead-end on Step 2.** `ReportForm.vue` `step2Valid` gates the Next button but renders no error explaining why it's disabled. A *future* incident date (`incidentDate <= today` false) or leaving Time of Day as `Unknown` silently disables Next.
2. **Dead venue search.** `venueSearchQuery` is bound to an input but never used. A working autocomplete already exists (`VenueSearchInput.vue` + `useSearch`) and is simply not wired in.
3. **Front/back validation mismatch.** UI says "minimum 10 characters" but `StoreReportRequest` enforces `min:20`. The 422 body is swallowed (`SubmitReport.vue` only reads `error.message`).
4. **Dated/invisible date input.** Native `<input type=date>` with no `color-scheme` (dark-on-dark). User also wants an optional exact time, persisted.

Beyond the report flow: the Map's "View N Venues" button is cut off on short viewports; navigation is missing Support Us & Contact (footer-only); About/Contact/Support are very tall on mobile.

**Decisions:** (1) fix blockers first as P0; (2) add an optional exact time that is persisted (migration); (3) keep styled native pickers.

## Phases

- **Phase 0** — Scaffold tracking docs · documentation-architect
- **Phase 1** — Report flow BACKEND (P0) · laravel-backend-developer
- **Phase 2** — Report flow FRONTEND (P0) · vue-frontend-developer
- **Phase 3** — Report flow TESTS (P0) · test-engineer
- **Phase 4** — Map mobile UX · vue-frontend-developer
- **Phase 5** — Navigation & layout · vue-frontend-developer
- **Phase 6** — Content pages density · vue-frontend-developer
- **Phase 7** — Global mobile polish · vue-frontend-developer
- **Phase 8** — QA & standards audit · code-auditor

See `mobile-ux-overhaul-tasks.md` for the full task checklist and `AGENT_DELEGATION.md` for the agent/skill map.

## Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Running the new migration on a non-test DB | Do not run without explicit user approval; verify DB name first |
| `VenueSearchInput` coupling | It only emits `select`/`clear`; verify standalone before relabel |
| Min-length change (10→20) surprises copy | Update placeholder + counter together; boundary test covers it |
| Native pickers vary by browser | Accept OS-native rendering; fix visibility + `max`; test iOS/Android |
| 422 mapping breaks on non-validation errors | Guard `response?.data?.errors`; fall back to toast |

## Success Metrics

- A test user completes a report (existing + new venue) end-to-end at 375px.
- Every invalid state shows a field-level reason — zero silent dead-ends.
- Front/back validation aligned (no surprise 422s).
- Reduced mobile scroll length on About/Contact/Support.
- Support Us & Contact reachable from header; current route indicated.
- Full Pest suite green; pint + eslint clean.

## Verification

1. `npm run build` succeeds.
2. Manual walk of `/submit-report` at 375px (future date → error, <20 chars → error, valid + optional time → submit → redirect; repeat via add-venue fallback).
3. After user-approved DB run: `php artisan test --compact --filter=Report`.
4. Spot-check Map, header nav, and content-page scroll length on mobile.
5. `vendor/bin/pint --dirty --format agent` and eslint clean.
