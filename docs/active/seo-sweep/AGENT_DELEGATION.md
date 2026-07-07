# Agent Delegation — SEO Sweep

**Last Updated: 2026-07-06**

**Purpose:** Map each phase to the appropriate agent and required skills.

**CRITICAL:** AI agents MUST load the specified skills BEFORE executing any phase.

## Quick Reference

| Phase | Primary Agent | Skills to Load |
|-------|---------------|----------------|
| 0 | documentation-architect | — |
| 1 | laravel-backend-developer | laravel-best-practices, pest-testing |
| 2 | laravel-backend-developer (+ vue-frontend-developer) | laravel-best-practices, inertia-vue-development |
| 3 | laravel-backend-developer + technical-architect | laravel-best-practices |
| 4 | laravel-backend-developer | laravel-best-practices, pest-testing |
| 5 | documentation-architect / vue-frontend-developer | frontend-design |
| 6 | test-engineer | pest-testing, laravel-best-practices |
| 7 | code-auditor | quality-assurance, bug-investigation |

## Phase Details

### Phase 1 — SEO foundation (data layer + Blade)
**Agent:** `laravel-backend-developer` · **Skills:** laravel-best-practices, pest-testing
Patterns: modular `src/DrinkSafe/Shared/Seo/`, immutable typed value object, return types/typehints/docblocks, `sprintf`. **Escape all output** — Blade `{{ }}` for tags; `json_encode(..., JSON_HEX_TAG|JSON_UNESCAPED_SLASHES)` for JSON-LD. Canonical/OG from `config('app.url')`.

### Phase 2 — Per-page SEO content
**Agent:** `laravel-backend-developer` (+ minor `vue-frontend-developer`) · **Skills:** laravel-best-practices, inertia-vue-development
Patterns: controllers build `Seo` and pass `'seo' => $seo->toArray()`; neutral venue copy; noindex private pages; `<Head title>` on Home/Map/Support.

### Phase 3 — Structured data
**Agent:** `laravel-backend-developer` + `technical-architect` · **Skills:** laravel-best-practices
Patterns: Organization + WebSite (SearchAction), BreadcrumbList, neutral Place. **No Review/AggregateRating/incident markup on venues.**

### Phase 4 — Crawlability
**Agent:** `laravel-backend-developer` · **Skills:** laravel-best-practices, pest-testing
Patterns: cached sitemap controller (`Cache::remember`), correct `Content-Type: application/xml`, robots disallows + `Sitemap:` line, canonical for param URLs.

### Phase 5 — Social image
**Agent:** `documentation-architect` / `vue-frontend-developer` · **Skills:** frontend-design
Patterns: branded 1200×630 PNG, teal/navy theme, wordmark + tagline.

### Phase 6 — Testing
**Agent:** `test-engineer` · **Skills:** pest-testing, laravel-best-practices
Patterns: assert on `$response->getContent()` (no browser); `it()`, AAA, `fake()`, response helpers; XSS test; confirm test DB before running.

### Phase 7 — QA & validation
**Agent:** `code-auditor` · **Skills:** quality-assurance, bug-investigation

## Execution Checklist (every phase)
- [ ] Read this file for the phase's agent + skills
- [ ] Load ALL required skills via the Skill tool
- [ ] Review files from the previous phase
- [ ] Implement following the skill patterns
- [ ] Complete the phase Review Checkpoint before handoff

## Handoff Protocol
1. Outgoing: mark phase done in `seo-sweep-tasks.md`; note deviations in context file.
2. Incoming: load required skills, review prior phase files, continue.
