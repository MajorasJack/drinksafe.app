# Agent Delegation - Feature Proposals Enhancement

**Purpose:** Map each phase to the appropriate agent and required skills for execution.

**CRITICAL:** AI agents MUST load the specified skills BEFORE executing any phase.

**Last Updated:** 2026-05-27

---

## Quick Reference

| Phase | Primary Agent | Skills to Load |
|-------|---------------|----------------|
| 1 | `documentation-architect` | None |
| 2 | `laravel-backend-developer` | `laravel-backend-guidelines` |
| 3 | `vue-frontend-developer` | `vue-inertia-frontend-guidelines` |
| 4 | `test-engineer` | `pest-testing` |
| 5 | `documentation-architect` | None |

---

## Phase Details

### Phase 1: Template Standardisation

**Primary Agent:** `documentation-architect`

**Before Starting - Load These Skills:**
- No skills required (documentation task)

**Key References:**
- `docs/architecture/modular-structure.md` - Module structure patterns
- `docs/architecture/naming-conventions.md` - Naming standards
- `docs/api/README.md` - API documentation format

**Acceptance Criteria:**
- All 5 features follow identical structure
- Feature template is documented for future use
- Heading hierarchy is consistent (H2 for features, H3 for sections)

---

### Phase 2: Backend Specifications

**Primary Agent:** `laravel-backend-developer`

**Before Starting - Load These Skills:**
- Skill: `laravel-backend-guidelines`

**Key Patterns from Skill:**
- Service class patterns with constructor injection
- FormRequest validation patterns
- API Resource transformation patterns
- Exception handling patterns
- Migration schema patterns

**Key References:**
- `src/DrinkSafe/Venues/Services/VenueService.php` - Service pattern
- `src/DrinkSafe/Venues/Resources/VenueResource.php` - Resource pattern
- `src/DrinkSafe/Reports/Enums/TimeOfDay.php` - Enum pattern

**Acceptance Criteria:**
- All service classes follow existing patterns
- API endpoints follow REST conventions
- Database schemas are properly normalised
- Migrations consider indexing

---

### Phase 3: Frontend Specifications

**Primary Agent:** `vue-frontend-developer`

**Before Starting - Load These Skills:**
- Skill: `vue-inertia-frontend-guidelines`

**Key Patterns from Skill:**
- Component props/emits with TypeScript
- Composable patterns with ref/computed
- Pinia store patterns
- Inertia page component patterns

**Key References:**
- `resources/js/components/venue/VenueCard.vue` - Component pattern
- `resources/js/composables/useMap.ts` - Composable pattern
- `resources/js/stores/reportStore.ts` - Store pattern
- `resources/js/types/venue.ts` - TypeScript type pattern

**Acceptance Criteria:**
- All components follow naming conventions
- TypeScript interfaces are properly defined
- Composables return consistent shapes
- Component props use proper typing

---

### Phase 4: Testing Strategy

**Primary Agent:** `test-engineer`

**Before Starting - Load These Skills:**
- Skill: `pest-testing`

**Key Patterns from Skill:**
- `it()` syntax for test descriptions
- AAA pattern (Arrange-Act-Assert)
- Factory usage patterns
- Feature test JSON assertions
- Unit test isolation patterns

**Key References:**
- `src/DrinkSafe/Venues/Tests/Feature/VenueControllerTest.php` - Feature test pattern
- `src/DrinkSafe/Venues/Tests/Unit/VenueServiceTest.php` - Unit test pattern
- `database/factories/DrinkSafe/VenueFactory.php` - Factory pattern

**Acceptance Criteria:**
- All test scenarios use `it()` syntax
- Tests cover happy path and edge cases
- Factory requirements are documented
- Test file locations follow module structure

---

### Phase 5: Final Review

**Primary Agent:** `documentation-architect`

**Before Starting - Load These Skills:**
- No skills required (documentation review task)

**Key References:**
- All previous phase outputs
- Existing architecture documentation

**Acceptance Criteria:**
- All code examples are syntactically correct
- All cross-references are valid
- Document renders correctly in markdown
- Revision history is updated

---

## Execution Checklist

Before starting ANY phase, the executing agent MUST:

- [ ] Read this AGENT_DELEGATION.md file
- [ ] Identify the primary agent for the phase
- [ ] Load ALL required skills using the Skill tool
- [ ] Review the "Key References" for that phase
- [ ] Only THEN begin implementation

---

## Handoff Protocol

When one phase completes and hands off to another agent:

### Outgoing Agent:

1. Mark phase complete in `feature-proposals-enhancement-tasks.md`
2. Document any deviations or decisions in context file
3. Note any blockers for next phase

### Incoming Agent:

1. Read AGENT_DELEGATION.md for next phase requirements
2. Load required skills BEFORE starting
3. Review context file for decisions made
4. Review previous phase output in FEATURE_PROPOSALS.md
5. Continue execution

---

## Skill Loading Commands

For reference, here are the skill invocations:

```
# Phase 2 - Backend
Skill: laravel-backend-guidelines

# Phase 3 - Frontend
Skill: vue-inertia-frontend-guidelines

# Phase 4 - Testing
Skill: pest-testing
```

---

## Notes

- **Phase 2 and 3 can run in parallel** if multiple agents are available
- **Phase 4 depends on Phase 2 and 3** (needs to know what to test)
- **Phase 5 must run last** (final review and polish)
- All phases operate on the same target file: `docs/features/FEATURE_PROPOSALS.md`
