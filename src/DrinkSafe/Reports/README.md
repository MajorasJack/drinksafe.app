# Reports Module

The Reports module handles all report-related functionality including report submission, filtering, moderation, and retrieval.

## Purpose

This module manages:
- Anonymous report submission
- The review queue: every report is checked by an admin before it is published
- Report filtering by date, time, and venue
- PII detection and content moderation
- Report retrieval with pagination
- Soft deletion for moderation purposes

## Key Components

### Models
- **Report** - Eloquent model representing an incident report with soft deletes

### Controllers
- **ReportController** - RESTful report operations (index, show, store)
- **SubmitReportController** - The public submission form
- **ReportModerationController** - The admin review queue at `/admin/reports`

### Services
- **ReportService** - Core report CRUD and filtering operations
- **ReportModerationService** - PII detection and content sanitisation

### Enums
- **TimeOfDay** - Enum for incident timing (Morning, Afternoon, Evening, Night, Unknown)
- **ReportStatus** - Where a report sits in the review queue (Pending, Published, Rejected)

### Scopes
- **PublishedScope** - Global scope on Report that limits every query to published reports

### Policies
- **ReportPolicy** - `moderate` lets users flagged `is_admin` work the review queue

### Notifications
- **ReportAwaitingReviewNotification** - Email telling the moderators a report is waiting

### Commands
- **drinksafe:make-admin {email} {--revoke} {--password}** - Flags a user as an admin, or removes the flag with `--revoke`. If no user has the email, it asks for a name and password and creates a verified admin. `--password` asks for a new password for an existing user, which is how a lost password is replaced as the site has no password reset. Both refuse to run with `--no-interaction`. The email is stored in lowercase

### Resources
- **ReportResource** - JSON response formatting for single report
- **ReportCollection** - Paginated report collection responses

### Exceptions
- **ReportNotFoundException** - Thrown when report UUID not found
- **ReportValidationException** - Thrown when report data validation fails

## Data Model

```php
Report {
    uuid: string (primary key)
    venue_uuid: string (foreign key)
    incident_date: date
    time_of_day: TimeOfDay enum
    description: text
    status: ReportStatus enum (default pending)
    reviewed_at: timestamp (nullable)
    created_at: timestamp
    updated_at: timestamp
    deleted_at: timestamp (soft delete)

    // Relationships
    venue: Venue (belongsTo)
}
```

## Key Features

### Anonymous Submission
Reports are submitted without user accounts or personal information.

### Review Queue
New reports are saved as `pending` and stay off the site until an admin publishes them.

- `PublishedScope` is a global scope on `Report`, so every Eloquent query, relationship, `withCount` and `has('reports')` only sees published reports. Anything that needs the others (the review queue) has to opt out with `Report::withoutGlobalScope(PublishedScope::class)`. Note that `$report->fresh()` ignores global scopes.
- `HeatmapService` queries `DB::table('reports')` directly, which skips the scope, so it filters on `status` itself. Any new raw query on `reports` must do the same.
- `status` is not mass assignable. `ReportService::reviewReport()` sets it with `forceFill`.
- When a report is created, `ReportService` sends `ReportAwaitingReviewNotification` to `services.reports.alert_email` (`REPORT_ALERT_EMAIL`, default `drinksafe@protonmail.com`). The email has no report text and no venue name. It sends during the request rather than through the queue. If it can't be sent, the report is still saved.
- Admins work the queue at `/admin/reports`. It needs a logged-in user with `is_admin` set, which only `php artisan drinksafe:make-admin {email}` can do. The site has no public registration, so the same command creates the account if it doesn't exist yet. Publishing or rejecting sets `reviewed_at`. A published report can be taken down by rejecting it.
- A venue named for the first time in a submission is unpublished (`published_at` is null) and hidden by `PublishedVenueScope` until a report about it is published. Venues created through `POST /api/venues` start unpublished too.
- The migration that added `status` made every existing report pending, on purpose, so they all get reviewed.
- Heatmap and analytics results are cached (15 minutes and 6 hours), so a newly published report can take that long to show there. Venue safety scores are cleared straight away by `ReportObserver`.

### PII Detection
The `ReportModerationService` automatically detects and rejects reports containing:
- Email addresses
- Phone numbers
- Potential personal identifiers

### Filtering
Reports can be filtered by:
- Venue UUID
- Date range (start_date, end_date)
- Time of day (Morning, Afternoon, Evening, Night, Unknown)
- Recency (most recent reports)

### Soft Deletes
Reports use soft deletion for compliance purposes. Day-to-day moderation uses the `rejected` status instead.

## Usage Examples

### Submit a Report
```php
$reportService = app(ReportService::class);
$report = $reportService->createReport([
    'venue_uuid' => 'abc-123-def',
    'incident_date' => '2024-01-15',
    'time_of_day' => 'Night',
    'description' => 'Witnessed suspicious behaviour near the bar area.',
]);
```

### Get Recent Reports
```php
$recent = $reportService->getRecentReports(10); // Last 10 published reports
```

### Publish a Report
```php
$reportService->reviewReport(uuid: $uuid, status: ReportStatus::Published);
```

### Filter by Date Range
```php
$filtered = $reportService->filterByDateRange(
    Carbon::parse('2024-01-01'),
    Carbon::parse('2024-01-31')
);
```
