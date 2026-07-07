# Drink Safe — SEO Sweep

**Last Updated: 2026-07-06**

## Executive Summary

Drink Safe currently ships **no meaningful SEO metadata**: the root Blade `<head>` has only charset, viewport, favicons and a static `<title>{{ config('app.name') }}</title>`. There are no meta descriptions, canonical URLs, Open Graph / Twitter cards, or structured data (JSON-LD) anywhere, no `sitemap.xml`, a permissive `robots.txt` with no `Sitemap:` line, and no social share image. Inertia SSR is `enabled=true` but has **no built bundle**, so the client-side `<Head>` meta is never server-rendered — meaning social scrapers (Facebook, LinkedIn, WhatsApp, Slack, Twitter/X), which don't run JS, see nothing.

This plan adds a **server-side SEO layer rendered in the root Blade view** (a `seo` prop each controller supplies), so every crawler and scraper gets complete, correct metadata on the initial page load with **no SSR Node server to run** — the lowest-ops, most robust approach for this app (whose deploy pipeline we've kept deliberately simple). It also adds structured data, a dynamic sitemap, a real `robots.txt`, and a branded OG image.

## Context / Why

- 99% mobile, safety-focused community app that should be discoverable and share cleanly when links are posted (social is a primary distribution channel for this kind of tool).
- Right now, sharing any drinksafe.app link produces a bare, untitled preview — poor trust and reach.
- The domain is sensitive (drink-spiking reports), so venue-page SEO is handled conservatively: **index venue pages with neutral `Place` structured data only — no review/rating/incident markup** that could imply wrongdoing or create defamation exposure.

**Decisions (confirmed with user):**
1. **Render SEO server-side in the Blade root view** (controllers pass a `seo` prop) — not via a production SSR Node server.
2. **Index venue pages + neutral `Place` JSON-LD** (name/address/geo), no review/rating markup.
3. **Canonical domain `https://drinksafe.app`**; generate a branded 1200×630 default OG image (per-venue dynamic images deferred).

## Current State (key facts)

| Area | State |
|------|-------|
| Root head | `resources/views/app.blade.php` — charset, viewport, favicons, `@fonts`, static `<title>`. Nothing else. |
| Title callback | `resources/js/app.ts` → `title ? \`${title} - ${appName}\` : appName` (good; keep). |
| Head adoption | 19/22 pages set only `<Head title>`; **Home/Map/Support set nothing**; no page sets description/OG. |
| SSR | `config/inertia.php` `ssr.enabled=true`, no `bootstrap/ssr` bundle → **meta not server-rendered**. |
| Shared props | `HandleInertiaRequests` shares `app.name`, `app.url` — good hook for global SEO defaults. |
| Structured data | None. No `spatie/schema-org` / seo packages. |
| Sitemap | **None**. |
| robots.txt | `public/robots.txt` = `User-agent: * / Disallow:` — no `Sitemap:`, no private-path blocks. |
| OG image | **None** in `public/`. Favicons present (ico/svg/apple-touch). |
| Config | `APP_URL` in `.env.production.example` is a placeholder (`safespot.example.com`); `APP_NAME` "DrinkSafe". |
| Dynamic page | `/venues/{slug}` (slug binding) → `VenueDetail.vue`; venue has name, slug, city, address, lat/lng, `reports_count`. |
| ⚠️ Security | Venue `name`/`city`/`address` are **user-submitted** (new-venue creation) → user-controlled data flows into title/OG/JSON-LD → **XSS risk**; must escape. |

**Public/indexable:** Home `/`, Map `/map`, Venue `/venues/{slug}`, About `/about`, Support `/support`, Analytics `/analytics`, Privacy `/privacy`, Terms `/terms`, Contact `/contact`.
**Non-indexable (noindex):** `/submit-report`, `/dashboard`, `/settings/*`, `/auth/*`, `/api/*`.

## Proposed Future State

- A small, dependency-free **`Seo` value object** (Shared module) + a default builder; each public controller supplies a page-specific `seo` prop, with a sensible sitewide default shared via middleware.
- The root Blade view renders, server-side, on every initial load: `<title>`, `meta description`, `canonical`, `robots`, full **Open Graph** + **Twitter `summary_large_image`**, and **JSON-LD** — all properly escaped.
- Sitewide **Organization** + **WebSite** (with `SearchAction`) JSON-LD; **BreadcrumbList** + neutral **Place** on venue pages.
- Dynamic cached **`/sitemap.xml`** (static pages + all venues) and a real **`robots.txt`** with `Sitemap:` + private-path disallows.
- Branded **default OG image**; correct `APP_URL`/`APP_NAME`.
- Full **feature-test coverage** asserting the server-rendered HTML (no browser needed — meta is in the initial response body).

---

## Phase 0 — Scaffold tracking docs
**Agent:** `documentation-architect` · **Skills:** none
Create `docs/active/seo-sweep/` with plan, context, tasks, AGENT_DELEGATION. **Acceptance:** four files exist, dated. **Effort: S**
> Review: structure matches dev-docs template.

## Phase 1 — SEO foundation: data layer + Blade rendering
**Agent:** `laravel-backend-developer` · **Skills:** `laravel-best-practices`, `pest-testing`

| # | Task | Acceptance | Effort |
|---|------|-----------|--------|
| 1.1 | Create `Seo` value object in `src/DrinkSafe/Shared/Seo/Seo.php` — typed props: `title`, `description`, `canonical`, `robots` (default `index,follow`), `image` (abs URL), `type` (default `website`), `jsonLd` (array). `toArray()` for the prop. Named constructors: `Seo::for(...)`. | Immutable, typed, return types, docblocks | M |
| 1.2 | `Seo::default()` builder pulling `config('app.name')`, `config('app.url')`, a default description, default OG image URL, and sitewide Organization + WebSite JSON-LD (Phase 3 fills schema). | Returns a complete default Seo | M |
| 1.3 | Share a default `seo` prop via `HandleInertiaRequests::share()` so **every** page has metadata even before its controller is updated (controller `seo` overrides the shared default). | Any Inertia page has `props.seo` | S |
| 1.4 | New Blade partial `resources/views/partials/seo.blade.php` rendering from `$page['props']['seo']`: `<title>`, description, canonical, robots, `og:*` (title/description/image/url/type/site_name), `twitter:card=summary_large_image` + title/description/image, and a `<script type="application/ld+json">` per JSON-LD block. **Escape everything** (`{{ }}`; JSON-LD via `json_encode(..., JSON_UNESCAPED_SLASHES\|JSON_HEX_TAG)`). Include it in `app.blade.php` `<head>` and remove the old static `<title>` (title now comes from `seo`). | View-source shows fully escaped, server-rendered meta on every page | M |
| 1.5 | Fix config: set real `APP_URL=https://drinksafe.app` + `APP_NAME="Drink Safe"` in `.env.production.example` (and note the live `.env` must match — user action). Ensure canonical/OG use `config('app.url')`, not the request host. | Canonical/OG use the production domain | S |

**Acceptance (phase):** every public route's raw HTML contains a complete, correctly-escaped meta block sourced from `seo`. `pint` clean.
> **Phase 1 Review** — code review; confirm XSS-safe escaping with a venue name containing `<script>`. Sign-off: __ / __ / Pending.

## Phase 2 — Per-page SEO content
**Agent:** `laravel-backend-developer` (+ minor `vue-frontend-developer`) · **Skills:** `laravel-best-practices`, `inertia-vue-development`

| # | Task | Acceptance | Effort |
|---|------|-----------|--------|
| 2.1 | Each static public controller sets a page-specific `seo` (unique title ≤60 chars, description ~150–160): Home, Map, About, Support, Analytics, Privacy, Terms, Contact. | Unique, accurate title+description per page | M |
| 2.2 | `VenueDetailController`: dynamic `seo` — title e.g. *"{Venue}, {City} — community safety reports"*, description summarising neutrally (report count, city), canonical `route('venues.show', slug)`, `og:type=article`. Copy reviewed for tone (no defamatory phrasing). | Venue pages have unique dynamic meta | M |
| 2.3 | Non-indexable pages set `robots` = `noindex,follow`: `SubmitReport`, dashboard, settings, auth. | Private pages carry noindex | S |
| 2.4 | Add `<Head title>` to the 3 pages missing it (Home, Map, Support) and confirm client-side title updates on SPA nav (server `seo.title` remains source of truth for the initial load). | No page shows the bare app name; titles update on nav | S |

**Acceptance (phase):** every public page unique; private pages noindex.
> **Phase 2 Review** — copy/tone check on venue titles+descriptions; `code-reviewer`. Sign-off: __ / __ / Pending.

## Phase 3 — Structured data (JSON-LD)
**Agent:** `laravel-backend-developer` + `technical-architect` (schema decisions) · **Skills:** `laravel-best-practices`

| # | Task | Acceptance | Effort |
|---|------|-----------|--------|
| 3.1 | Sitewide **Organization** (name, url, logo) + **WebSite** with `potentialAction` SearchAction (→ `/map?search=`) in `Seo::default()`. | Valid in Rich Results Test | M |
| 3.2 | Venue page **BreadcrumbList** (Home › Map › {Venue}). | Valid breadcrumb | S |
| 3.3 | Venue page **neutral `Place`** JSON-LD: `name`, `address` (PostalAddress from city/address), `geo` (lat/lng). **No `Review`, `AggregateRating`, or incident markup.** Escaped via safe JSON encoding. | Place validates; zero review/rating markup | M |

**Acceptance (phase):** all JSON-LD validates; venue schema is neutral. Legal/product sign-off on venue markup.
> **Phase 3 Review** — `technical-architect` confirms no defamation-risk markup; Rich Results Test passes. Sign-off: __ / __ / Pending.

## Phase 4 — Crawlability: sitemap + robots
**Agent:** `laravel-backend-developer` · **Skills:** `laravel-best-practices`, `pest-testing`

| # | Task | Acceptance | Effort |
|---|------|-----------|--------|
| 4.1 | `SitemapController` + `GET /sitemap.xml` — static public pages + all venues (`venues.show` slug URLs, `lastmod` = venue `updated_at`). `Cache::remember` (e.g. 6h). `Content-Type: application/xml`. | Valid XML lists all public + venue URLs | M |
| 4.2 | Real `public/robots.txt`: `Allow` public; `Disallow: /submit-report`, `/dashboard`, `/settings`, `/api`; `Sitemap: https://drinksafe.app/sitemap.xml`. | robots blocks private/api + references sitemap | S |
| 4.3 | Canonical guard for param URLs (e.g. `/map?search=`) → canonical points to the clean path to avoid duplicate indexing. | Param variants canonicalise to clean URL | S |

**Acceptance (phase):** sitemap valid + complete; robots correct.
> **Phase 4 Review** — validate XML + robots syntax. Sign-off: __ / __ / Pending.

## Phase 5 — Social preview image
**Agent:** `documentation-architect` / `vue-frontend-developer` (asset) · **Skills:** `frontend-design`

- 5.1 Produce a branded **1200×630 `public/og-image.png`** (Drink Safe wordmark + tagline, teal/navy theme) and set it as the default `og:image`/`twitter:image`. **(M)**
- 5.2 (Deferred/optional) dynamic per-venue OG images — note as a follow-up, not in scope. **(—)**

**Acceptance:** default OG image renders in FB/Twitter/LinkedIn debuggers. **Effort: M**
> **Phase 5 Review** — debugger previews look correct. Sign-off: __ / __ / Pending.

## Phase 6 — Testing
**Agent:** `test-engineer` · **Skills:** `pest-testing`, `laravel-best-practices`

Because meta is **server-rendered in the initial HTML**, everything is testable via response content — **no browser needed**.

| # | Task | Acceptance | Effort |
|---|------|-----------|--------|
| 6.1 | Feature test per public route: response 200 and body contains correct `<title>`, `meta description`, `canonical`, `og:*`, `twitter:*`. | `assertSee`/regex on `$response->getContent()` | M |
| 6.2 | Private routes assert `noindex`. Venue route asserts dynamic values + neutral `Place` JSON-LD and **absence** of `Review`/`AggregateRating`. | Coverage of noindex + venue schema | M |
| 6.3 | **XSS test**: create a venue whose name contains `"><script>` and assert it is escaped in title/OG/JSON-LD (no raw script). | No unescaped injection | S |
| 6.4 | `sitemap.xml` returns valid XML + contains a seeded venue; `robots.txt` contains disallows + `Sitemap:`. | Sitemap/robots covered | S |

**Acceptance:** green suite; confirm test DB before running (global rule).
> **Phase 6 Review** — green evidence. Sign-off: __ / __ / Pending.

## Phase 7 — QA & external validation
**Agent:** `code-auditor` · **Skills:** `quality-assurance`, `bug-investigation`

- Standards audit of changed files; confirm no review/rating schema on venues; canonical correctness; no duplicate/missing tags; escaping verified.
- External validation: Google Rich Results Test, Facebook Sharing Debugger, Twitter Card Validator, LinkedIn Post Inspector against a deployed/preview URL.

**Acceptance:** no Critical/High; external validators pass. **Effort: M**
> **Phase 7 Review** — final sign-off before merge.

---

## Agent Delegation (quick reference)

| Phase | Agent | Skills |
|-------|-------|--------|
| 0 | documentation-architect | — |
| 1 | laravel-backend-developer | laravel-best-practices, pest-testing |
| 2 | laravel-backend-developer (+vue-frontend-developer) | laravel-best-practices, inertia-vue-development |
| 3 | laravel-backend-developer + technical-architect | laravel-best-practices |
| 4 | laravel-backend-developer | laravel-best-practices, pest-testing |
| 5 | documentation-architect / vue-frontend-developer | frontend-design |
| 6 | test-engineer | pest-testing, laravel-best-practices |
| 7 | code-auditor | quality-assurance, bug-investigation |

## Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| **Defamation/legal** on venue pages (spiking domain) | Neutral `Place` only, no review/rating/incident markup; disclaimer copy; product/legal sign-off before shipping venue indexing |
| **XSS** via user-submitted venue name/city/address in meta & JSON-LD | Blade auto-escaping for tags; `json_encode` with `JSON_HEX_TAG\|JSON_UNESCAPED_SLASHES` for JSON-LD; explicit XSS feature test (6.3) |
| Meta stale on client-side SPA nav (Blade re-renders only on full load) | Acceptable — scrapers/crawlers fetch URLs fresh (full load); `<Head title>` updates the visible title for users |
| Wrong `APP_URL` → broken canonical/OG | Fix config; canonical/OG derive from `config('app.url')`; assert in tests |
| Thin/empty venue pages (no reports) diluting index | Canonical set; optional `noindex` for venues with 0 reports (decision in Phase 2) |
| Param/duplicate URLs (map filters) indexed | Canonical to clean path (4.3); robots disallow noisy params if needed |
| No production SSR → JS-only crawlers | Not a problem — all critical meta is server-rendered in Blade |

## Success Metrics

- Every public page: unique server-rendered `<title>` (≤60), `description` (~150–160), `canonical`, full OG + Twitter `summary_large_image`.
- JSON-LD validates (Rich Results): Organization + WebSite sitewide; BreadcrumbList + neutral Place on venues; **zero** review/rating markup.
- `sitemap.xml` valid and lists all public + venue URLs; `robots.txt` references it and blocks private/api.
- Private pages `noindex`.
- Default OG image renders in FB/Twitter/LinkedIn debuggers.
- No XSS via venue names (test-proven).
- `APP_URL`/`APP_NAME` correct for production.

## Verification (end-to-end)

1. `curl -s https://drinksafe.app/ | grep -iE 'og:|twitter:|canonical|description|ld\+json'` (and per page) shows complete meta.
2. Feature tests: `php artisan test --filter=Seo` (after user-approved DB run) — assert on response bodies.
3. Google Rich Results Test (venue + home), Facebook Sharing Debugger, Twitter Card Validator, LinkedIn Post Inspector.
4. Fetch `/sitemap.xml` (valid XML) and `/robots.txt` (disallows + Sitemap line).
5. `vendor/bin/pint --dirty` and eslint clean.

## Dependencies & Notes

- **No new packages required** (dependency-free `Seo` object + Blade). Optional: `spatie/schema-org` for JSON-LD ergonomics and `spatie/laravel-sitemap` — only with explicit approval; the plan assumes custom/no-dep.
- Live `.env` `APP_URL`/`APP_NAME` must be set correctly on the server (user action) for canonical/OG to be right in production.
- Phases 1→4 are largely sequential (1 is foundational); 5 is independent; 6 after 1–4; 7 last.
