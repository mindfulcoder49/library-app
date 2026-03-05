# Check It Out Test Plan

## Objective

Provide regression-safe automated coverage for the core business workflows:
- Catalog discovery and request entry points
- Loan lifecycle transitions
- Waitlist lifecycle and queue behavior
- Admin visibility and historical filtering
- Permissions and role-driven actions

## Test Layers

1. Unit tests
- Pure business helpers (if extracted in future: queue ordering, status transitions).

2. Feature tests (primary)
- HTTP-level behavior for controllers, authorization, DB effects, and Inertia payloads.

3. End-to-end/UI smoke (future)
- Browser-level checks for faceting, pagination, and action visibility.

## Critical Workflow Matrix

### A. Catalog and discovery

1. Browse default availability
- Given no `availability` filter
- Then catalog defaults to `all`
- And unavailable items are visible

2. Browse available-only filter
- Given `availability=available`
- Then only `available` items are returned

3. Request entry point from browse
- `available` item -> request creates loan
- `loan_pending`/`checked_out` item -> waitlist flow starts

### B. Loan lifecycle

1. Request -> approve -> share -> borrowed -> return
- Verify all status transitions on `loans` and `book_items`

2. Reject flow
- Request rejected -> item available
- If waitlist exists, next borrower is promoted

3. Cancel flow
- Borrower/lender cancel pending -> item available
- If waitlist exists, next borrower is promoted

4. Permissions
- Non-lender cannot approve/reject/share another lender's loan
- Unauthorized users receive `403`

### C. Waitlist lifecycle

1. Add to waitlist when unavailable
- Entry created with queue position

2. Duplicate prevention
- Existing `waiting`/`notified` entry blocks second join

3. Cancel and rejoin
- Cancel transitions to `cancelled`
- Rejoin reactivates same entry with new queue position

4. Queue rebalancing
- Cancel/removal reindexes `waiting` entries

5. Auto-promotion
- When item becomes available and waitlist exists:
  - next queued borrower gets auto-created `requested` loan
  - promoted waitlist entry becomes `fulfilled`
  - item transitions to `loan_pending`

6. No duplicate open loans on promotion
- Auto-promotion should not create duplicate open request for same borrower/item

### D. Admin observability and filtering

1. Global history visibility
- Admin can see historical records across all loan statuses

2. Filter combinations
- Status filter
- Lender filter
- Borrower filter
- Date range filter
- Free-text search (title/ISBN/participant)

3. Non-admin scope
- Non-admin only sees own request/borrow contexts

### E. CSV and verification (existing + future expansion)

1. CSV dry-run feedback visible in UI payload
2. Non-admin lender assignment enforcement
3. Admin lender override support
4. Verify queue bulk actions (select all/deselect all/verify selected)
5. Admin remove and move-to-pending actions

## Automation Plan

## Phase 1 (implemented now)
- High-risk feature tests:
  - Catalog default + available filter behavior
  - Waitlist add/cancel/rejoin
  - Auto-promotion on return/reject into `requested`
  - Admin historical filter behavior

## Phase 2
- Additional permissions/negative-path tests
- CSV import edge cases (malformed rows, quoted values, encoding, large files)
- Verification queue bulk mixed-status assertions

## Phase 3
- Browser E2E smoke tests for user-critical journeys

## Data and Environment

- Use `RefreshDatabase` for isolation.
- Build test fixtures with explicit statuses and timestamps.
- Prefer deterministic timestamps for date-range filters.

## Success Criteria

- CI fails on any workflow regression affecting statuses, queue order, or admin visibility.
- Manual QA is reduced to exploratory and UX validation.
- Core production paths are covered by deterministic repeatable tests.
