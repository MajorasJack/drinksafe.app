# SEO Sweep — Tasks

**Last Updated: 2026-07-06**

Status: `[ ]` pending · `[~]` in progress · `[x]` done

## Phase 0 — Scaffold docs (documentation-architect)
- [x] Create `docs/active/seo-sweep/` with plan, context, tasks, AGENT_DELEGATION

## Phase 1 — SEO foundation: data layer + Blade rendering (laravel-backend-developer) `[x] DONE`
**Skills:** laravel-best-practices, pest-testing
- [x] 1.1 `Seo` value object (immutable readonly, typed, fluent `with*`, `noindex()`, `toArray()`)
- [x] 1.2 `Seo::default()` (app name/url, default description, OG image URL, Organization + WebSite/SearchAction JSON-LD)
- [x] 1.3 Shared lazy `seo` default in `HandleInertiaRequests` (controller overrides)
- [x] 1.4 `partials/seo.blade.php` + `@include`; XSS-safe (`{{ }}` attrs, `JSON_HEX_*` JSON-LD); one `<title>`; kept `<x-inertia::head />`
- [x] 1.5 `.env.production.example` → `APP_URL=https://drinksafe.app`, `APP_NAME="Drink Safe"`
- [x] Verified: pint passed, smoke test 6/6 green

## Phase 2 — Per-page SEO content (laravel-backend-developer + vue) `[x] DONE`
**Skills:** laravel-best-practices, inertia-vue-development
- [x] 2.1 All 8 static controllers set unique `seo` (title ≤60 + ~150-char description)
- [x] 2.2 `VenueDetailController` dynamic `seo` (title/desc/og:type=article); **canonical fixed to config('app.url')** (was route()/request-host)
- [x] 2.3 `SubmitReportController` create() → `noindex`; auth-gated routes covered by auth redirects
- [x] 2.4 `<Head title>` added to Home/Map/Support
- [x] Verified: pint/eslint/build clean; 114/114 tests

## Phase 3 — Structured data JSON-LD (laravel-backend-developer) `[x] DONE`
**Skills:** laravel-best-practices
- [x] 3.1 Sitewide Organization + WebSite (SearchAction → `/map?search=`) — in `Seo::default()`
- [x] 3.2 Venue BreadcrumbList (Home › Map › Venue) — config-host URLs
- [x] 3.3 Venue neutral `Place` (name/PostalAddress/GeoCoordinates) — **NO Review/AggregateRating** ✓ verified
- [x] Reviewed: no defamation-risk markup present

## Phase 4 — Crawlability: sitemap + robots (laravel-backend-developer) `[x] DONE`
**Skills:** laravel-best-practices, pest-testing
- [x] 4.1 `SitemapController` + `GET /sitemap.xml` (8 static + all venues, lastmod, `Cache::remember` 6h, xml content-type, lazy query, `e()`-escaped)
- [x] 4.2 `public/robots.txt` (Disallow submit-report/dashboard/settings/api + `Sitemap:` line)
- [~] 4.3 Canonical guard for map param URLs — folded into Phase 2 agent (owns MapController)
- [x] Verified: pint passed, 7/7 tests green

## Phase 5 — Social preview image `[x] DONE`
**Skills:** frontend-design
- [x] 5.1 Branded 1200×630 `public/og-image.png` (rendered from HTML via Playwright screenshot; teal/navy, shield mark, tagline, drinksafe.app). Already referenced as default og:image/twitter:image in `Seo::default()`.
- [~] 5.2 (Deferred) dynamic per-venue OG images — follow-up only
- [x] Visual check passed; validate in social debuggers post-deploy (Phase 7)

## Phase 6 — Testing (test-engineer) `[x] DONE`
**Skills:** pest-testing, laravel-best-practices
- [x] 6.1 Per-page metadata dataset (8 routes) — title/description/canonical/OG/Twitter + unique titles (`PageMetadataTest`)
- [x] 6.2 noindex on submit-report; venue dynamic + neutral Place; **absence of Review/AggregateRating/ratingValue** even with reports seeded (`VenueMetadataTest`)
- [x] 6.3 XSS: venue name `Rex"><script>` — escaped in attrs + hex-escaped in JSON-LD; dangerous `alert(1)</script>` token absent everywhere (`SeoEscapingTest`)
- [x] 6.4 sitemap/robots covered (Phase 4 `SitemapTest`)
- [x] Verified: SEO suite 103 green; full suite 204 green; pint clean

## Phase 7 — QA & external validation (code-auditor) `[x] DONE (changes-required → resolved)`
**Skills:** quality-assurance, bug-investigation
- [x] Standards audit — escaping ✓, defamation guard ✓ (zero Review/AggregateRating), canonical/OG all from config host ✓, no N+1 in sitemap ✓
- [x] **HIGH fixed:** removed `<Head title>` from Home/Map/Support (they overwrote the server SEO title on JS crawlers / risked duplicate `<title>`) — server `seo` prop now owns titles everywhere
- [x] **MEDIUM fixed:** SitemapController builds URLs from `config('app.url')` (host-stable under 6h cache), consistent with canonicals
- [x] **All LOWs resolved:** geo now omitted when coords null (defensive); `declare(strict_types=1)` added to `HandleInertiaRequests`; robots drops `Disallow: /submit-report` so bots read its `noindex`; venue `og:type` → `website` (conventional)
- [x] **MEDIUM 2 (test-confidence):** single-`<title>` invariant documented in context doc for future devs
- [x] Re-verified after ALL fixes: pint + eslint clean, build green, SEO 103 + full 204/204 tests
- [ ] External validators (Rich Results / FB / Twitter / LinkedIn) — **post-deploy** (need a live URL)
- [x] Final sign-off: APPROVED after fixes

---

## Open decisions to confirm during execution
- [ ] Noindex venues with **0 reports** (thin content)? — decide in Phase 2
- [ ] Product/legal sign-off on indexing venue pages + neutral Place schema — before Phase 7
- [ ] Live-server `.env` `APP_URL`/`APP_NAME` set correctly (user action)
