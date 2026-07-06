# Agent Delegation — Mobile UX Overhaul

**Last Updated: 2026-06-30**

**Purpose:** Map each phase to the appropriate agent and required skills for execution.

**CRITICAL:** AI agents MUST load the specified skills BEFORE executing any phase.

## Quick Reference

| Phase | Primary Agent | Skills to Load |
|-------|---------------|----------------|
| 0 | documentation-architect | — |
| 1 | laravel-backend-developer | laravel-best-practices, pest-testing |
| 2 | vue-frontend-developer | inertia-vue-development, vue-inertia-frontend-guidelines, tailwindcss-development |
| 3 | test-engineer | pest-testing, laravel-best-practices |
| 4 | vue-frontend-developer | inertia-vue-development, tailwindcss-development |
| 5 | vue-frontend-developer | inertia-vue-development, tailwindcss-development |
| 6 | vue-frontend-developer | inertia-vue-development, tailwindcss-development |
| 7 | vue-frontend-developer | tailwindcss-development, frontend-mastery |
| 8 | code-auditor | quality-assurance, bug-investigation |

## Phase Details

### Phase 1 — Report flow BACKEND
**Primary Agent:** `laravel-backend-developer` · **Skills:** laravel-best-practices, pest-testing
Patterns: modular `src/DrinkSafe/Reports/...`, FormRequest validation, return types/typehints/docblocks, `sprintf`, module-specific exceptions, `$fillable`, factory updated with migration. **Do not run the migration without user approval.**

### Phase 2 — Report flow FRONTEND
**Primary Agent:** `vue-frontend-developer` · **Skills:** inertia-vue-development, vue-inertia-frontend-guidelines, tailwindcss-development
Patterns: Composition API + TS, reuse Reka UI + existing `VenueSearchInput`/`useSearch`, single root element, descriptive names, no axios (built-in XHR / existing store).

### Phase 3 — Report flow TESTS
**Primary Agent:** `test-engineer` · **Skills:** pest-testing, laravel-best-practices
Patterns: `it()`/`test…` camelCase, AAA, `fake()`, JSON methods + response helpers, single-assertion focus, no comments. **Confirm test DB before running.**

### Phases 4–7 — Mobile polish
**Primary Agent:** `vue-frontend-developer` · **Skills:** inertia-vue-development, tailwindcss-development (+ frontend-mastery for Phase 7)
Patterns: mobile-first, ≥44px tap targets, safe-area insets, Reka `Sheet`/`Collapsible`, existing teal/navy tokens.

### Phase 8 — QA & standards audit
**Primary Agent:** `code-auditor` · **Skills:** quality-assurance, bug-investigation

## Execution Checklist (every phase)
- [ ] Read this file for the phase's agent + skills
- [ ] Load ALL required skills via the Skill tool
- [ ] Review files from the previous phase
- [ ] Implement following the skill patterns
- [ ] Complete the phase Review Checkpoint before handoff

## Handoff Protocol
1. Outgoing agent: mark phase done in `mobile-ux-overhaul-tasks.md`; note deviations in context file.
2. Incoming agent: load required skills, review prior phase files, continue.
