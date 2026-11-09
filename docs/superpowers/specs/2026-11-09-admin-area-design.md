# Admin area design

## Why

Drink Safe has one admin, the owner. Every new report emails them, and they review it at `/admin/reports`. That page can publish or reject pending reports and take down one of the last 20 decisions, and that's all it can do. Anything else needs a shell on the server or a database query:

- finding an older report that a venue complains about
- fixing a venue's name, address or map position
- seeing which partner QR codes are being scanned
- checking how many venues and reports each city has before outreach

The site is about to be promoted to students' unions and venues, so complaints, new partners and new venues will come in. The admin area should let the owner handle all of that from the browser.

## Goals

1. Land on a useful dashboard after login, not the starter-kit placeholder.
2. Review pending reports safely. The owner must see where a new venue's pin will go before publishing, because a submission without coordinates is placed in central London.
3. Deal with complaints: find any report by venue, city, status or date, and take it down or restore it.
4. Fix venues: edit name, city, address and coordinates, and see venues that aren't published.
5. Clear a backlog quickly with bulk publish and reject.
6. See partner QR scan totals over a date range, and switch a partner link on or off.
7. See coverage per city, which today needs the `drinksafe:coverage` command.
8. Make sure a takedown disappears from the public heatmap and analytics straight away, not after the caches expire.
9. Require two-factor login before any admin page can be used.
10. Point out pending reports that look risky, using only data the site already holds.

## Non-goals

- Several admins, roles or permissions. There is one admin, and `is_admin` plus the existing `moderate` policy is enough.
- Creating admins or resetting passwords from the browser. That stays in `drinksafe:make-admin`.
- Editing a report's description. The owner publishes or rejects; there's no redaction tool in this work.
- Hard deleting venues or reports from the browser. Deleting a venue cascades to its reports, so that stays a deliberate command-line job.
- Creating partner links from the browser. `drinksafe:partners:add` also generates the QR file, which belongs on a machine with the print tooling.
- Venue-owner accounts or published replies.

## Anti-goals (these would make it a failure even if it works)

- **Showing any per-visitor data.** The site promises no tracking. No admin page may show IP addresses, user agents, session data, log lines, or partner scans finer than daily totals. Abuse signals use only report and venue data.
- **Exposing server details in the public repository.** No hostnames, addresses, paths or hosting details in code, docs, commits or PRs.
- **Wiping the whole cache.** The existing `invalidateCache()` methods on the heatmap and analytics services call `Cache::flush()`, which also clears rate limiters. Invalidation must be targeted.
- **Weakening the admin boundary.** Every admin route keeps `auth`, `auth.session` and the `moderate` policy, and adds the two-factor requirement.

## Constraints

- Laravel 13, Inertia v3, Vue 3, Tailwind 4, Pest 4, and the existing UI components in `resources/js/components/ui`. Wayfinder generates the route helpers. No new dependencies.
- Code lives in the owning module under `src/DrinkSafe/*`, following the existing module layout, FormRequests, Policies and Resources. House rules are in the repo's `CLAUDE.md` and the owner's global standards (return types, `$fillable` only, `sprintf`, British English, Pest feature and unit tests, JSON test helpers, no comments in tests).
- Public pages must not change behaviour. The global `PublishedScope` (reports) and `PublishedVenueScope` (venues) stay. Admin queries opt out with `withoutGlobalScope`, as the review queue already does.
- The site runs behind Cloudflare. The app never reads visitor IP addresses.
- Every PR is reviewed before merging and deploys on merge to `main`.

## What's decided

### Navigation and landing

- Share `is_admin` in the Inertia `auth.user` props. Admin pages use the existing app shell (`AppSidebar`/`AppLayout`), with sidebar items for Dashboard, Review queue, Reports, Venues, Partners and Coverage.
- After login, admins go to the admin dashboard. Point Fortify's `home` at it, and replace the placeholder `Dashboard.vue`.

### Dashboard

Shows:
- pending count, how old the oldest pending report is, and a link to the queue
- reports received per day over the last 30 days, pending and decided included, from `created_at`
- counts published and rejected in the last 30 days
- top partner codes by scans over the last 30 days
- the number of unpublished venues with no pending report

Every number is an aggregate.

### Review queue (the existing `/admin/reports`, improved)

- Each pending card shows the venue's coordinates and a small map. When the coordinates equal the default used for missing coordinates, it shows a clear warning and a link to edit the venue before publishing. The default is `51.5074, -0.1278`, set in `ReportService`. Move it to a named constant so the check and the default can't drift. The check is an exact match on the stored values (the decimal columns round-trip exactly). A real venue at exactly that point is very unlikely, and the warning is only a prompt, so a false match costs nothing. *(My call.)*
- The map tiles come from CARTO, which the Privacy page already names. No other third party.
- Select several pending reports and publish or reject them together. It uses the existing `reviewReport()` for each, so venues are published and caches cleared exactly as they are one at a time.
- The pending list paginates instead of stopping at 50.
- Flags (see "Abuse signals") show as badges on the card.

### Reports search (new)

- A page listing every report, any status, newest first, paginated.
- Filters: venue (search by name), city, status, and incident date range. An optional text search over the description, using the existing full-text index where the database supports it and a `LIKE` fallback otherwise.
- Each row links to the venue's admin page. Actions are Take down for a published report and Publish for a rejected one, using `reviewReport()`. A report can't be moved back to pending.
- "Recent decisions" on the queue page links here instead of being capped at 20.

### Takedowns and restores (decided by the owner)

- **A takedown sets the report to `rejected`,** as it does today. There's no separate status, so takedowns count as rejections in the filters and the dashboard. *(My call: it's the existing behaviour, and nothing needs to tell them apart.)*
- **Taking down a venue's last published report asks the owner.** When a takedown, bulk reject included, would leave a venue with no published reports, the confirmation asks whether to unpublish the venue as well, with "Hide the venue too" and "Keep the venue listed" as the two answers. Unpublishing goes through the same venue action as the venue page. Bulk reject asks once, listing the venues affected.
- **Restoring a report whose venue is unpublished is blocked.** On a rejected report whose venue is unpublished, Publish is disabled with the note "This venue is unpublished. Publish the venue first." `reviewReport()` must not publish a venue the owner unpublished on purpose. Today it publishes any venue whose `published_at` is null, which includes those. It needs to tell "never published (new submission)" apart from "unpublished by the owner", for example with an `unpublished_at` timestamp or a flag set by the venue action. That choice is the builder's.

### Venues (new)

- A list of all venues, published and unpublished, with search by name and a city filter. Filters for "unpublished" and "unpublished with no pending report" (the venues that `POST /api/venues` or rejected submissions leave behind).
- A venue page showing details, a map, and its reports in every status.
- An edit form for name, city, address, latitude and longitude. Use a new admin-only FormRequest, so the unused public `UpdateVenueRequest` isn't changed. `VenueService::updateVenue()` has no public caller today, so the admin can use it. Name plus city must stay unique across all venues, published or not, and a clash gives a validation error naming the existing venue. Coordinates must be valid latitude and longitude. The slug stays as it is, so existing links keep working. *(My call.)*
- Unpublish and publish actions. Unpublishing hides the venue and all its reports from the public site through the existing scopes. Publishing a venue doesn't publish its reports.
- No delete (see non-goals).
- Editing coordinates or publish state clears that venue's cached safety score and the heatmap cache (see "Caches").

### Partners (new)

- A table of partner links with code, label, destination, active, and scan totals over a chosen date range (default: the last 30 days), using the query `drinksafe:partners:report` already runs.
- Switch active on or off, and edit label and destination with the existing `PartnerLink` validation, which allows internal paths only.
- Totals are sums of the daily rows, with the date range inclusive and in UK dates, the same as the scans are recorded. The dashboard's "top codes" panel includes inactive links, marked as inactive, because scans still happen after a link is switched off. *(My call.)*
- No create or delete (see non-goals).

### Coverage (new)

A page showing the same table as `drinksafe:coverage`, with a text field to change the cities. Move the counting into a small service that both the command and the page call.

### Caches

- Give the heatmap and temporal analytics caches targeted invalidation with a version number in their cache keys, which an invalidation bumps. The default cache store is `database`, which has no tags, so a version key works on every store and tags don't. Never `Cache::flush()`. *(My call.)*
- Clear them whenever a report's status changes and whenever a venue's coordinates or publish state change, after the database transaction commits.
- Add a "Refresh public caches" button on the dashboard that does the same.

### Two-factor requirement

- Middleware on every admin route. If the admin hasn't confirmed two-factor (`two_factor_confirmed_at` is null), redirect to the existing two-factor setup in settings, with a message explaining why.
- The settings pages themselves don't require it, so setup stays reachable.
- The check reads `two_factor_confirmed_at` on every request. Fortify already challenges for the code at login once two-factor is confirmed, and turning it off in settings clears the column, so the next admin request is redirected. That's enough for one admin. *(My call.)*
- If the admin loses their two-factor device, they recover from the command line: add `--reset-two-factor` to `drinksafe:make-admin`. It clears the two-factor secret, recovery codes and confirmation, and ends existing sessions the same way `--password` does. *(My call.)*

### Abuse signals (badges on pending reports)

Shown only to the admin, and computed only from report and venue data:
- The description had personal details removed automatically (it contains the "removed" markers the moderation service inserts).
- The venue is new (unpublished) and has more than one pending report.
- The venue got an unusual number of reports in the last 24 hours. The threshold is the builder's call: a fixed number such as 5, set in config.
- The venue's coordinates are the default.

These are prompts to look closer, not automatic actions.

## Left to the builder

- Page layouts, table and filter components, and pagination size, using the existing UI components and the review queue's style.
- How the dashboard's per-day chart is drawn. There's no chart library, so a simple bar strip in Tailwind or SVG is fine.
- Splitting this into PRs. A sensible order is: two-factor and navigation, queue improvements, reports search, venues, caches, partners and coverage, dashboard, abuse signals. Each PR is reviewed separately.
- Test structure, following the existing feature, unit and browser test patterns. Every admin route needs tests for guest (redirect), non-admin (403), admin without two-factor (redirect), and admin.
