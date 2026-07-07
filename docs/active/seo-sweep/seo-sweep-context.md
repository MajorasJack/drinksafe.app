# SEO Sweep — Context

**Last Updated: 2026-07-06**

## Key Files

| Area | File(s) |
|------|---------|
| Root Blade head (add meta here) | `resources/views/app.blade.php` |
| New SEO partial | `resources/views/partials/seo.blade.php` (to create) |
| New SEO value object | `src/DrinkSafe/Shared/Seo/Seo.php` (to create) |
| Global shared props | `app/Http/Middleware/HandleInertiaRequests.php` |
| Inertia title callback (keep) | `resources/js/app.ts` |
| Static public controllers | `src/DrinkSafe/Shared/Controllers/{Home,Map,About,Support,Analytics,Privacy,Terms,Contact}Controller.php` |
| Dynamic venue controller | `src/DrinkSafe/Venues/Controllers/VenueDetailController.php` |
| Venue model / resource | `src/DrinkSafe/Venues/Models/Venue.php`, `.../Resources/VenueResource.php` |
| Pages missing `<Head>` | `resources/js/pages/{Home,Map,Support}.vue` |
| Submit form (noindex) | `resources/js/pages/SubmitReport.vue`, `src/DrinkSafe/Reports/Controllers/SubmitReportController.php` |
| Routes | `routes/web.php` (add `/sitemap.xml`), `routes/drinksafe.php` (api) |
| Sitemap controller | `src/DrinkSafe/Shared/Controllers/SitemapController.php` (to create) |
| robots | `public/robots.txt` |
| OG image | `public/og-image.png` (to create) |
| Config | `config/app.php`, `.env.example`, `.env.production.example` |
| Slug/route binding | `src/DrinkSafe/Shared/Traits/HasSlug.php` (slug-based binding) |

## Key Decisions

- **Server-side Blade rendering** of SEO meta. Controllers pass a `seo` prop; the root Blade view reads `$page['props']['seo']` and renders `<head>` tags on every initial load. No production SSR Node server. Works for all scrapers/crawlers without JS.
- **Venue pages: index + neutral `Place` JSON-LD** (name/address/geo). **No** Review/AggregateRating/incident markup (defamation/legal). Copy kept neutral.
- **Domain `https://drinksafe.app`**; generate a branded 1200×630 default OG image. Per-venue dynamic OG images deferred.
- **No new dependencies** — dependency-free `Seo` object + Blade partial. Packages (spatie/schema-org, spatie/laravel-sitemap) only with explicit approval.
- Global default `seo` shared via middleware; per-controller `seo` overrides it (Inertia per-request prop wins over shared prop of the same key).

## Critical Constraints

- **XSS:** venue `name`/`city`/`address` are user-submitted (new-venue creation in the report flow). All meta content must be HTML-escaped (Blade `{{ }}`); JSON-LD via `json_encode(..., JSON_HEX_TAG|JSON_UNESCAPED_SLASHES)`. Explicit XSS test required.
- **Legal:** neutral venue schema only; product/legal sign-off before shipping venue indexing.
- **Config:** live `.env` `APP_URL`/`APP_NAME` must be correct on the server (currently `.env.production.example` has placeholder `safespot.example.com`).
- Global rules: modular architecture (`src/DrinkSafe/<Module>/…`), return types, typehints, docblocks, `sprintf`, module-specific exceptions; PestPHP (`it()`, AAA, response helpers, JSON methods); DB-safety (confirm test DB before running).

## Indexation Policy

- **Index:** `/`, `/map`, `/venues/{slug}`, `/about`, `/support`, `/analytics`, `/privacy`, `/terms`, `/contact`.
- **noindex,follow:** `/submit-report`, `/dashboard`, `/settings/*`, `/auth/*`. **robots Disallow:** `/api`.

## Testability Note

All SEO is server-rendered into the initial HTML response, so **feature tests assert on `$response->getContent()`** (assertSee/regex) — no browser/Playwright needed.

## Single-`<title>` invariant (important for future devs)

The Blade `seo` partial is the **single source of truth** for `<title>` and all meta. Pages MUST NOT set a client-side `<Head title>` — doing so makes Inertia's title callback overwrite the crafted server title on JS-rendering crawlers, and would emit a second `<title>` if an Inertia SSR bundle is ever built (`<x-inertia::head />` currently renders nothing server-side because SSR has no bundle). The `SeoFoundationTest` "exactly one `<title>`" assertion guards this. If Inertia SSR is ever enabled with a bundle, reconcile the `<x-inertia::head />` title emission with the partial before shipping.
