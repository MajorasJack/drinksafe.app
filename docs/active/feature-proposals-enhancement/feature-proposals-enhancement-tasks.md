# Feature Proposals Enhancement - Tasks

**Last Updated:** 2026-05-27

---

## Phase 1: Template Standardisation ✅ COMPLETE

### Agent Delegation

**Primary Agent:** `documentation-architect`
**Skills to Load:** None required

**Before Starting This Phase:**
1. Read AGENT_DELEGATION.md for detailed patterns
2. Review existing documentation structure in docs/architecture/

---

### Tasks

- [x] Create feature proposal template section at top of document
- [x] Add "Implementation Estimate" field to each feature (S/M/L/XL)
- [x] Add "Acceptance Criteria" section to each feature
- [x] Standardise heading levels across all features
- [x] Add feature status badges (Proposed/In Progress/Implemented)
- [x] Create inter-feature dependency matrix

---

### Phase 1 Review Checkpoint

**Before proceeding to Phase 2, complete these reviews:**

- [x] All features follow consistent structure
- [x] Template is reusable for future features
- [x] Heading hierarchy is correct (no skipped levels)

**Phase 1 Sign-off:**
- Reviewer: Claude
- Date: 2026-05-27
- Status: Approved

---

## Phase 2: Backend Specifications ✅ COMPLETE

### Agent Delegation

**Primary Agent:** `laravel-backend-developer`
**Skills to Load:**
- `laravel-backend-guidelines`

**Before Starting This Phase:**
1. Load required skills using the Skill tool
2. Review AGENT_DELEGATION.md for Laravel patterns
3. Reference `docs/architecture/modular-structure.md`

---

### Tasks

#### Feature 1: Venue Safety Scores
- [x] Define SafetyScoreService class signature
- [x] Document score computation algorithm with PHP example
- [x] Specify caching strategy (Redis keys, TTL)
- [x] Document API endpoint shape (`GET /api/venues/{uuid}/safety-score`)
- [x] Define VenueResource additions for score fields

#### Feature 2: Incident Heatmap Layer
- [x] Define HeatmapService class signature
- [x] Document spatial aggregation query (raw SQL or Eloquent)
- [x] Specify bounding box query parameters
- [x] Document API endpoint shape (`GET /api/heatmap`)
- [x] Define HeatmapResource for response formatting

#### Feature 3: Time-based Analytics
- [x] Propose Analytics module directory structure
- [x] Define TemporalAnalyticsService class signature
- [x] Document aggregation queries
- [x] Specify API endpoint shapes
- [x] Define AnalyticsResource for response formatting

#### Feature 4: Venue Response/Claim System
- [x] Propose VenueOwners module directory structure
- [x] Define model schemas (VenueOwner, VenueClaim, VenueResponse)
- [x] Document relationships and foreign keys
- [x] Specify migration considerations
- [x] Define API endpoints for claim workflow
- [x] Document moderation service interface

#### Feature 5: Nearby Alerts
- [x] Propose Notifications module directory structure
- [x] Define model schemas (UserAlert, SavedVenue, AlertHistory)
- [x] Document event/listener architecture
- [x] Specify queue job patterns
- [x] Define user preference API endpoints

---

### Phase 2 Review Checkpoint

**Before proceeding to Phase 3, complete these reviews:**

- [x] All PHP examples follow project conventions
- [x] Module structures align with modular-structure.md
- [x] API shapes are consistent with existing API docs
- [x] Database schemas are properly normalised

**Phase 2 Sign-off:**
- Reviewer: laravel-backend-developer agent
- Date: 2026-05-27
- Status: Approved

---

## Phase 3: Frontend Specifications ✅ COMPLETE

### Agent Delegation

**Primary Agent:** `vue-frontend-developer`
**Skills to Load:**
- `vue-inertia-frontend-guidelines`

**Before Starting This Phase:**
1. Load required skills using the Skill tool
2. Review AGENT_DELEGATION.md for Vue patterns
3. Reference `docs/architecture/naming-conventions.md`

---

### Tasks

#### Feature 1: Venue Safety Scores
- [x] Define SafetyBadge component props and emits
- [x] Document component usage in VenueCard
- [x] Specify TypeScript interface for SafetyScore

#### Feature 2: Incident Heatmap Layer
- [x] Define useHeatmap composable signature
- [x] Document Leaflet.heat integration approach
- [x] Specify heatmap layer toggle component
- [x] Define time period selector component

#### Feature 3: Time-based Analytics
- [x] Propose analytics page structure
- [x] Define chart component specifications
- [x] Document composable for fetching analytics data
- [x] Specify filter component patterns

#### Feature 4: Venue Response/Claim System
- [x] Define claim flow page sequence
- [x] Specify response display component
- [x] Document venue owner dashboard structure
- [x] Define form components for responses

#### Feature 5: Nearby Alerts
- [x] Define notification preferences page
- [x] Specify saved venues management component
- [x] Document notification display patterns
- [x] Define alert badge/bell component

---

### Phase 3 Review Checkpoint

**Before proceeding to Phase 4, complete these reviews:**

- [x] All Vue examples follow naming conventions
- [x] Component patterns match existing codebase
- [x] TypeScript interfaces are properly typed
- [x] Composable patterns are consistent

**Phase 3 Sign-off:**
- Reviewer: vue-frontend-developer agent
- Date: 2026-05-27
- Status: Approved

---

## Phase 4: Testing Strategy ✅ COMPLETE

### Agent Delegation

**Primary Agent:** `test-engineer`
**Skills to Load:**
- `pest-testing`

**Before Starting This Phase:**
1. Load required skills using the Skill tool
2. Review AGENT_DELEGATION.md for testing patterns
3. Reference existing tests in `src/DrinkSafe/*/Tests/`

---

### Tasks

#### All Features - General
- [x] Define test file naming conventions for new modules
- [x] Document factory requirements for new models

#### Feature 1: Venue Safety Scores
- [x] Define unit tests for score computation service
- [x] Document feature tests for safety score API endpoint
- [x] Specify edge case scenarios (no reports, old reports, etc.)

#### Feature 2: Incident Heatmap Layer
- [x] Define unit tests for spatial aggregation
- [x] Document feature tests for heatmap API
- [x] Specify performance test considerations

#### Feature 3: Time-based Analytics
- [x] Define unit tests for aggregation service
- [x] Document feature tests for analytics API
- [x] Specify test data seeding requirements

#### Feature 4: Venue Response/Claim System
- [x] Define unit tests for claim verification service
- [x] Document feature tests for claim workflow
- [x] Specify moderation flow test scenarios
- [x] Define factory states for claim statuses

#### Feature 5: Nearby Alerts
- [x] Define unit tests for alert matching logic
- [x] Document feature tests for notification dispatch
- [x] Specify queue job testing approach

---

### Phase 4 Review Checkpoint

**Before proceeding to Phase 5, complete these reviews:**

- [x] All test scenarios cover happy path and edge cases
- [x] Factory requirements are documented
- [x] Test patterns match existing codebase

**Phase 4 Sign-off:**
- Reviewer: test-engineer agent
- Date: 2026-05-27
- Status: Approved

---

## Phase 5: Final Review ✅ COMPLETE

### Agent Delegation

**Primary Agent:** `documentation-architect`
**Skills to Load:** None required

**Before Starting This Phase:**
1. Read all previous phase outputs
2. Verify cross-references are accurate

---

### Tasks

- [x] Verify all acceptance criteria are measurable
- [x] Ensure all code examples compile/parse correctly
- [x] Update Related Documentation links
- [x] Create/update dependency matrix diagram
- [x] Add revision history entry for this enhancement
- [x] Verify document renders correctly in markdown preview
- [x] Cross-reference with existing architecture docs

---

### Phase 5 Review Checkpoint

**Final review before marking complete:**

- [x] Document is self-contained and understandable
- [x] All features have consistent structure
- [x] No broken links or references
- [x] Peer review completed

**Phase 5 Sign-off:**
- Reviewer: documentation-architect agent
- Date: 2026-05-27
- Status: Approved

---

## Completion Summary

| Phase | Status | Completed Date | Notes |
|-------|--------|----------------|-------|
| Phase 1: Template Standardisation | ✅ Complete | 2026-05-27 | Added template, acceptance criteria, effort estimates, status badges, dependency matrix |
| Phase 2: Backend Specifications | ✅ Complete | 2026-05-27 | Added PHP services, controllers, migrations, enums, events for all 5 features |
| Phase 3: Frontend Specifications | ✅ Complete | 2026-05-27 | Added Vue components, composables, TypeScript interfaces for all 5 features |
| Phase 4: Testing Strategy | ✅ Complete | 2026-05-27 | Added PestPHP tests, factories, edge cases for all 5 features |
| Phase 5: Final Review | ✅ Complete | 2026-05-27 | Fixed code inconsistencies, verified links, updated revision history |

**Overall Status:** ✅ COMPLETE

**Estimated Total Effort:** Medium (sum of S + M + M + S + S)
