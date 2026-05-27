# Modular Architecture Structure

**Last Updated:** 2026-05-14

---

## Overview

The DrinkSafe application follows a modular architecture pattern using domain-driven design principles. Instead of placing all code in Laravel's default `app/` directory, the application organises domain logic into self-contained modules within the `src/DrinkSafe/` namespace.

## Architectural Principles

### 1. Domain-Driven Design
Each module represents a distinct domain within the application:
- **Venues** - Venue management and geolocation
- **Reports** - Report submission and moderation
- **Shared** - Cross-cutting concerns

### 2. Clear Boundaries
Modules have explicit boundaries:
- Internal module logic is encapsulated
- Inter-module communication happens through service interfaces
- Each module can be understood independently

### 3. Consistent Structure
All modules follow the same directory structure, making navigation predictable and maintainable.

---

## Directory Structure

```
src/DrinkSafe/
├── README.md
├── Venues/
│   ├── README.md
│   ├── Models/
│   │   └── Venue.php
│   ├── Controllers/
│   │   ├── VenueController.php
│   │   └── VenueSearchController.php
│   ├── Services/
│   │   ├── VenueService.php
│   │   └── VenueSearchService.php
│   ├── Policies/
│   │   └── VenuePolicy.php
│   ├── Requests/
│   │   ├── StoreVenueRequest.php
│   │   └── UpdateVenueRequest.php
│   ├── Resources/
│   │   ├── VenueResource.php
│   │   └── VenueCollection.php
│   ├── Exceptions/
│   │   ├── VenueNotFoundException.php
│   │   └── VenueDuplicateException.php
│   └── Tests/
│       ├── Feature/
│       │   ├── VenueControllerTest.php
│       │   └── VenueSearchControllerTest.php
│       └── Unit/
│           ├── VenueTest.php
│           └── VenueServiceTest.php
├── Reports/
│   ├── README.md
│   ├── Models/
│   │   └── Report.php
│   ├── Controllers/
│   │   └── ReportController.php
│   ├── Services/
│   │   ├── ReportService.php
│   │   └── ReportModerationService.php
│   ├── Policies/
│   │   └── ReportPolicy.php
│   ├── Requests/
│   │   └── StoreReportRequest.php
│   ├── Resources/
│   │   ├── ReportResource.php
│   │   └── ReportCollection.php
│   ├── Enums/
│   │   └── TimeOfDay.php
│   ├── Exceptions/
│   │   ├── ReportNotFoundException.php
│   │   └── ReportValidationException.php
│   └── Tests/
│       ├── Feature/
│       │   └── ReportControllerTest.php
│       └── Unit/
│           ├── ReportTest.php
│           └── ReportServiceTest.php
└── Shared/
    ├── README.md
    ├── Traits/
    │   └── HasUuid.php
    ├── Services/
    │   └── GeolocationService.php
    └── Exceptions/
        └── DrinkSafeException.php
```

---

## Module Components

### Models (Eloquent)
**Purpose:** Database entities and relationships

**Responsibilities:**
- Define database schema attributes
- Define relationships with other models
- Define query scopes for common filters
- Define attribute casting and mutators

**Example:**
```php
namespace DrinkSafe\Venues\Models;

use Illuminate\Database\Eloquent\Model;
use DrinkSafe\Shared\Traits\HasUuid;

class Venue extends Model
{
    use HasUuid;

    protected $fillable = ['name', 'city', 'address', 'latitude', 'longitude'];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'venue_uuid');
    }
}
```

### Controllers (HTTP Layer)
**Purpose:** Handle HTTP requests and responses

**Responsibilities:**
- Route request handling
- Input validation (via FormRequests)
- Delegate to services for business logic
- Return API Resources or Inertia responses

**Pattern:** Thin controllers - no business logic

**Example:**
```php
namespace DrinkSafe\Venues\Controllers;

use DrinkSafe\Venues\Services\VenueService;
use DrinkSafe\Venues\Resources\VenueCollection;

class VenueController extends Controller
{
    public function __construct(
        private readonly VenueService $venueService
    ) {}

    public function index(): JsonResponse
    {
        $venues = $this->venueService->getAllVenues();

        return new VenueCollection($venues);
    }
}
```

### Services (Business Logic Layer)
**Purpose:** Encapsulate business logic

**Responsibilities:**
- Complex business operations
- Data transformation
- Inter-model coordination
- Throw custom exceptions on errors

**Pattern:** Services are injected via constructor

**Example:**
```php
namespace DrinkSafe\Venues\Services;

use DrinkSafe\Venues\Models\Venue;
use DrinkSafe\Venues\Exceptions\VenueNotFoundException;

class VenueService
{
    public function getVenueById(string $uuid): Venue
    {
        $venue = Venue::with('reports')->find($uuid);

        if (!$venue) {
            throw new VenueNotFoundException($uuid);
        }

        return $venue;
    }
}
```

### Policies (Authorization)
**Purpose:** Define authorization rules

**Responsibilities:**
- Check user permissions
- Return boolean allow/deny decisions
- Used by Gate facade and middleware

**Example:**
```php
namespace DrinkSafe\Venues\Policies;

class VenuePolicy
{
    public function update(?User $user, Venue $venue): bool
    {
        // Future: admin-only venue updates
        return $user && $user->isAdmin();
    }
}
```

### Requests (Validation)
**Purpose:** Validate incoming HTTP requests

**Responsibilities:**
- Define validation rules
- Custom error messages
- Authorization checks (via `authorize()` method)

**Pattern:** All validation in FormRequests, never in controllers

**Example:**
```php
namespace DrinkSafe\Venues\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVenueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ];
    }
}
```

### Resources (API Responses)
**Purpose:** Transform models to JSON

**Responsibilities:**
- Consistent JSON structure
- Conditional field inclusion
- Data transformation for API consumers

**Example:**
```php
namespace DrinkSafe\Venues\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VenueResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'city' => $this->city,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'reports_count' => $this->whenLoaded('reports', fn() => $this->reports->count()),
        ];
    }
}
```

### Enums (Type-Safe Constants)
**Purpose:** Define fixed sets of values

**Responsibilities:**
- Type-safe value constraints
- Label generation for UI display
- Integration with model casting

**Example:**
```php
namespace DrinkSafe\Reports\Enums;

enum TimeOfDay: string
{
    case Morning = 'Morning';
    case Afternoon = 'Afternoon';
    case Evening = 'Evening';
    case Night = 'Night';
    case Unknown = 'Unknown';

    public function label(): string
    {
        return $this->value;
    }
}
```

### Exceptions (Error Handling)
**Purpose:** Module-specific error types

**Responsibilities:**
- Explicit error communication
- HTTP status code mapping
- Error logging context

**Pattern:** Extend DrinkSafeException base class

**Example:**
```php
namespace DrinkSafe\Venues\Exceptions;

use DrinkSafe\Shared\Exceptions\DrinkSafeException;

class VenueNotFoundException extends DrinkSafeException
{
    public function __construct(string $uuid)
    {
        parent::__construct(
            sprintf('Venue with UUID %s not found', $uuid),
            404
        );
    }
}
```

### Tests (Quality Assurance)
**Purpose:** Verify functionality and prevent regressions

**Structure:**
- **Feature Tests:** Test HTTP endpoints and full request/response cycles
- **Unit Tests:** Test individual classes in isolation

**Example:**
```php
namespace DrinkSafe\Venues\Tests\Unit;

use DrinkSafe\Venues\Models\Venue;

it('generates UUID on creation', function () {
    $venue = Venue::factory()->create();

    expect($venue->uuid)
        ->toBeString()
        ->toHaveLength(36);
});
```

---

## Benefits of Modular Architecture

### Maintainability
- Easy to locate code (predictable structure)
- Changes are isolated to specific modules
- Clear ownership and responsibility

### Scalability
- New modules can be added without affecting existing ones
- Teams can work on different modules in parallel
- Microservices extraction is straightforward if needed

### Testability
- Modules can be tested independently
- Clear dependencies make mocking easier
- Test organisation mirrors code organisation

### Onboarding
- New developers can understand one module at a time
- Consistent structure reduces learning curve
- README files provide context

---

## Inter-Module Communication

### Service Injection
Modules communicate by injecting services:

```php
namespace DrinkSafe\Reports\Services;

use DrinkSafe\Venues\Services\VenueService;

class ReportService
{
    public function __construct(
        private readonly VenueService $venueService
    ) {}

    public function createReportWithNewVenue(array $data): Report
    {
        $venue = $this->venueService->createVenue($data['venue']);
        // ...
    }
}
```

### Event-Based Communication (Future)
For looser coupling, modules can emit events:

```php
event(new VenueCreated($venue));
```

Other modules listen without direct dependencies.

---

## Module Creation Guidelines

When creating a new module:

1. **Create directory structure** following the standard layout
2. **Add README.md** explaining the module's purpose
3. **Define namespace** as `DrinkSafe\{ModuleName}`
4. **Create base exception** extending `DrinkSafeException`
5. **Start with models** and their relationships
6. **Build services** for business logic
7. **Create controllers** as thin HTTP handlers
8. **Write tests** alongside implementation

---

## Configuration

### PSR-4 Autoloading
Namespace mapping in `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "DrinkSafe\\": "src/DrinkSafe/"
        }
    }
}
```

### Test Discovery
PHPUnit configuration in `phpunit.xml`:

```xml
<testsuites>
    <testsuite name="Unit">
        <directory>src/DrinkSafe/*/Tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory>src/DrinkSafe/*/Tests/Feature</directory>
    </testsuite>
</testsuites>
```

---

## Anti-Patterns to Avoid

### God Modules
Don't create overly large modules. Split when a module grows beyond 10-15 classes.

### Circular Dependencies
Modules should have clear dependency direction. If A depends on B, B should not depend on A.

### Shared State
Avoid static properties or global state within modules. Use dependency injection.

### Direct Model Access Across Modules
Always go through services. Don't directly access another module's models.

---

## Future Considerations

### Pages Module
When Inertia controllers are added, create `src/DrinkSafe/Pages/` module.

### Admin Module
Future admin functionality will live in `src/DrinkSafe/Admin/`.

### API Versioning
If API versioning is needed, create versioned modules: `src/DrinkSafe/Api/V1/`, `src/DrinkSafe/Api/V2/`.

---

## Related Documentation

- [Naming Conventions](./naming-conventions.md)
- [Data Flow Architecture](./data-flow.md)
- [Testing Strategy](../../react-to-vue-migration-context.md#testing-strategy)

---

**Document Status:** Active Reference
**Created:** 2026-05-14
**Last Updated:** 2026-05-14
