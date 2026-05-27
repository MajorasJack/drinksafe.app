# DrinkSafe API Documentation

Welcome to the DrinkSafe API documentation. This API provides endpoints for managing venues and incident reports for the DrinkSafe application.

---

## Table of Contents

1. [Base URL](#base-url)
2. [Authentication](#authentication)
3. [Response Format](#response-format)
4. [Error Handling](#error-handling)
5. [Rate Limiting](#rate-limiting)
6. [Venues Endpoints](#venues-endpoints)
7. [Reports Endpoints](#reports-endpoints)
8. [Examples](#examples)

---

## Base URL

```
Production: https://drinksafe.example.com/api
Development: http://localhost:8000/api
```

All API endpoints are prefixed with `/api`.

---

## Authentication

Currently, the DrinkSafe API is **publicly accessible** and does not require authentication for read operations. Write operations (creating venues and reports) are also public to allow anonymous reporting.

**Future Authentication:** Bearer token authentication may be added for administrative operations.

---

## Response Format

All API responses are returned in JSON format with the following structure:

### Success Response

```json
{
    "data": {
        // Response data here
    }
}
```

### Collection Response

```json
{
    "data": [
        // Array of resources
    ],
    "meta": {
        "total": 100
    }
}
```

### Error Response

```json
{
    "message": "Error description",
    "errors": {
        "field_name": [
            "Validation error message"
        ]
    }
}
```

---

## Error Handling

### HTTP Status Codes

| Status Code | Description |
|-------------|-------------|
| 200 OK | Request successful |
| 201 Created | Resource created successfully |
| 400 Bad Request | Invalid request parameters |
| 404 Not Found | Resource not found |
| 422 Unprocessable Entity | Validation errors |
| 429 Too Many Requests | Rate limit exceeded |
| 500 Internal Server Error | Server error |

### Error Response Examples

**404 Not Found:**

```json
{
    "message": "Venue not found with UUID: 123e4567-e89b-12d3-a456-426614174000"
}
```

**422 Validation Error:**

```json
{
    "message": "The name field is required. (and 2 more errors)",
    "errors": {
        "name": ["The name field is required."],
        "city": ["The city field is required."],
        "latitude": ["The latitude must be between -90 and 90."]
    }
}
```

---

## Rate Limiting

**Current Limits:** No rate limiting is currently enforced.

**Future Implementation:** Rate limiting may be added as follows:
- Public endpoints: 60 requests per minute per IP
- Authenticated endpoints: 120 requests per minute per user

**Rate Limit Headers (Future):**

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1620000000
```

---

## Venues Endpoints

### List All Venues

Retrieve a list of all venues with report counts.

```http
GET /api/venues
```

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| city | string | No | Filter venues by city name (case-sensitive) |

#### Response

**Status:** 200 OK

```json
{
    "data": [
        {
            "uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
            "name": "The Crown & Anchor",
            "city": "London",
            "address": "123 High Street, London, UK",
            "latitude": 51.5074,
            "longitude": -0.1278,
            "reports_count": 5,
            "created_at": "2026-05-01T10:00:00.000Z"
        },
        {
            "uuid": "8c2d3e4f-5a6b-7c8d-9e0f-1a2b3c4d5e6f",
            "name": "Blue Moon Bar",
            "city": "Manchester",
            "address": "456 Market Street, Manchester, UK",
            "latitude": 53.4808,
            "longitude": -2.2426,
            "reports_count": 3,
            "created_at": "2026-05-02T14:30:00.000Z"
        }
    ],
    "meta": {
        "total": 2
    }
}
```

#### Example Request

```bash
# Get all venues
curl https://drinksafe.example.com/api/venues

# Filter by city
curl "https://drinksafe.example.com/api/venues?city=London"
```

---

### Search Venues

Search for venues using various criteria including text search, city filter, and geographical proximity.

```http
GET /api/venues/search
```

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| q | string | No | Search term for name/city (partial match) |
| city | string | No | Filter by exact city name |
| lat | float | No | Latitude for proximity search (requires lng) |
| lng | float | No | Longitude for proximity search (requires lat) |
| radius | integer | No | Search radius in kilometres (default: 10, max: 1000) |

**Note:** You must provide one of: `q`, `city`, or both `lat` and `lng`.

#### Validation Rules

- `q`: Minimum 1 character
- `city`: Minimum 1 character
- `lat`: Between -90 and 90, required if `lng` is provided
- `lng`: Between -180 and 180, required if `lat` is provided
- `radius`: Between 1 and 1000 kilometres

#### Response

**Status:** 200 OK

```json
{
    "data": [
        {
            "uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
            "name": "The Crown & Anchor",
            "city": "London",
            "address": "123 High Street, London, UK",
            "latitude": 51.5074,
            "longitude": -0.1278,
            "reports_count": 5,
            "created_at": "2026-05-01T10:00:00.000Z"
        }
    ],
    "meta": {
        "total": 1
    }
}
```

#### Example Requests

```bash
# Text search
curl "https://drinksafe.example.com/api/venues/search?q=crown"

# City filter
curl "https://drinksafe.example.com/api/venues/search?city=London"

# Proximity search (within 5km of coordinates)
curl "https://drinksafe.example.com/api/venues/search?lat=51.5074&lng=-0.1278&radius=5"
```

---

### Get Single Venue

Retrieve detailed information about a specific venue, including all associated reports.

```http
GET /api/venues/{uuid}
```

#### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| uuid | string | Yes | UUID of the venue |

#### Response

**Status:** 200 OK

```json
{
    "data": {
        "uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
        "name": "The Crown & Anchor",
        "city": "London",
        "address": "123 High Street, London, UK",
        "latitude": 51.5074,
        "longitude": -0.1278,
        "reports": [
            {
                "uuid": "7b1c2d3e-4f5a-6b7c-8d9e-0f1a2b3c4d5e",
                "venue_uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
                "incident_date": "2026-05-10",
                "time_of_day": "Evening",
                "description": "Felt unsafe due to suspicious behavior at the bar.",
                "formatted_date": "5 days ago",
                "created_at": "2026-05-10T22:30:00.000Z"
            }
        ],
        "created_at": "2026-05-01T10:00:00.000Z"
    }
}
```

#### Error Responses

**404 Not Found:**

```json
{
    "message": "Venue not found with UUID: 9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f"
}
```

#### Example Request

```bash
curl https://drinksafe.example.com/api/venues/9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f
```

---

### Create Venue

Create a new venue in the system.

```http
POST /api/venues
```

#### Request Headers

```http
Content-Type: application/json
```

#### Request Body

```json
{
    "name": "The Crown & Anchor",
    "city": "London",
    "address": "123 High Street, London, UK",
    "latitude": 51.5074,
    "longitude": -0.1278
}
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| name | string | Yes | Venue name (max: 255 characters, unique per city) |
| city | string | Yes | City name (max: 100 characters) |
| address | string | No | Full street address (max: 500 characters) |
| latitude | float | Yes | Latitude (-90 to 90) |
| longitude | float | Yes | Longitude (-180 to 180) |

#### Validation Rules

- `name`: Required, string, max 255 characters, unique combination with city
- `city`: Required, string, max 100 characters
- `address`: Optional, string, max 500 characters
- `latitude`: Required, numeric, between -90 and 90
- `longitude`: Required, numeric, between -180 and 180

#### Response

**Status:** 201 Created

```json
{
    "message": "Venue The Crown & Anchor created successfully",
    "data": {
        "uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
        "name": "The Crown & Anchor",
        "city": "London",
        "address": "123 High Street, London, UK",
        "latitude": 51.5074,
        "longitude": -0.1278,
        "reports_count": 0,
        "created_at": "2026-05-15T10:00:00.000Z"
    }
}
```

#### Error Responses

**422 Unprocessable Entity (Duplicate Venue):**

```json
{
    "message": "A venue with name 'The Crown & Anchor' already exists in London"
}
```

**422 Validation Error:**

```json
{
    "message": "The name field is required. (and 1 more error)",
    "errors": {
        "name": ["The name field is required."],
        "city": ["The city field is required."]
    }
}
```

#### Example Request

```bash
curl -X POST https://drinksafe.example.com/api/venues \
  -H "Content-Type: application/json" \
  -d '{
    "name": "The Crown & Anchor",
    "city": "London",
    "address": "123 High Street, London, UK",
    "latitude": 51.5074,
    "longitude": -0.1278
  }'
```

---

## Reports Endpoints

### List Reports

Retrieve a list of reports with optional filtering.

```http
GET /api/reports
```

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| venue_uuid | string | No | Filter by venue UUID |
| start_date | string | No | Start date (Y-m-d format, requires end_date) |
| end_date | string | No | End date (Y-m-d format, requires start_date) |
| time_of_day | string | No | Filter by time: Morning, Afternoon, Evening, Night, Unknown |
| limit | integer | No | Max results (default: 50, max: 100) |

#### Validation Rules

- `venue_uuid`: Must be valid UUID that exists in venues table
- `start_date`: Date format (Y-m-d), required with end_date
- `end_date`: Date format (Y-m-d), must be after or equal to start_date
- `time_of_day`: One of: Morning, Afternoon, Evening, Night, Unknown
- `limit`: Integer between 1 and 100

#### Response

**Status:** 200 OK

```json
{
    "data": [
        {
            "uuid": "7b1c2d3e-4f5a-6b7c-8d9e-0f1a2b3c4d5e",
            "venue_uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
            "incident_date": "2026-05-10",
            "time_of_day": "Evening",
            "description": "Felt unsafe due to suspicious behavior at the bar.",
            "formatted_date": "5 days ago",
            "venue": {
                "uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
                "name": "The Crown & Anchor",
                "city": "London",
                "address": "123 High Street, London, UK",
                "latitude": 51.5074,
                "longitude": -0.1278,
                "created_at": "2026-05-01T10:00:00.000Z"
            },
            "created_at": "2026-05-10T22:30:00.000Z"
        }
    ],
    "meta": {
        "total": 1
    }
}
```

#### Example Requests

```bash
# Get recent reports (default limit: 50)
curl https://drinksafe.example.com/api/reports

# Filter by venue
curl "https://drinksafe.example.com/api/reports?venue_uuid=9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f"

# Filter by date range
curl "https://drinksafe.example.com/api/reports?start_date=2026-05-01&end_date=2026-05-15"

# Filter by time of day
curl "https://drinksafe.example.com/api/reports?time_of_day=Evening"

# Limit results
curl "https://drinksafe.example.com/api/reports?limit=10"

# Combine filters
curl "https://drinksafe.example.com/api/reports?venue_uuid=9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f&time_of_day=Evening&limit=20"
```

---

### Get Single Report

Retrieve detailed information about a specific report.

```http
GET /api/reports/{uuid}
```

#### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| uuid | string | Yes | UUID of the report |

#### Response

**Status:** 200 OK

```json
{
    "data": {
        "uuid": "7b1c2d3e-4f5a-6b7c-8d9e-0f1a2b3c4d5e",
        "venue_uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
        "incident_date": "2026-05-10",
        "time_of_day": "Evening",
        "description": "Felt unsafe due to suspicious behavior at the bar.",
        "formatted_date": "5 days ago",
        "venue": {
            "uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
            "name": "The Crown & Anchor",
            "city": "London",
            "address": "123 High Street, London, UK",
            "latitude": 51.5074,
            "longitude": -0.1278,
            "created_at": "2026-05-01T10:00:00.000Z"
        },
        "created_at": "2026-05-10T22:30:00.000Z"
    }
}
```

#### Error Responses

**404 Not Found:**

```json
{
    "message": "Report not found with UUID: 7b1c2d3e-4f5a-6b7c-8d9e-0f1a2b3c4d5e"
}
```

#### Example Request

```bash
curl https://drinksafe.example.com/api/reports/7b1c2d3e-4f5a-6b7c-8d9e-0f1a2b3c4d5e
```

---

### Create Report

Submit a new incident report for an existing venue.

```http
POST /api/reports
```

#### Request Headers

```http
Content-Type: application/json
```

#### Request Body

**Option 1: Report for Existing Venue (Recommended)**

```json
{
    "venue_uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
    "incident_date": "2026-05-10",
    "time_of_day": "Evening",
    "description": "Felt unsafe due to suspicious behavior at the bar."
}
```

**Option 2: Report with New Venue Creation**

If the venue doesn't exist, you can create it along with the report:

```json
{
    "venue_name": "The Crown & Anchor",
    "venue_city": "London",
    "venue_address": "123 High Street, London, UK",
    "latitude": 51.5074,
    "longitude": -0.1278,
    "incident_date": "2026-05-10",
    "time_of_day": "Evening",
    "description": "Felt unsafe due to suspicious behavior at the bar."
}
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| venue_uuid | string | Conditional | UUID of existing venue (required if not providing venue details) |
| venue_name | string | Conditional | Venue name (required if not providing venue_uuid) |
| venue_city | string | Conditional | City name (required if providing venue_name) |
| venue_address | string | No | Venue address (optional when creating venue) |
| latitude | float | Conditional | Latitude (optional, defaults to London if creating venue) |
| longitude | float | Conditional | Longitude (optional, defaults to London if creating venue) |
| incident_date | string | Yes | Date of incident (Y-m-d format) |
| time_of_day | string | Yes | Time period: Morning, Afternoon, Evening, Night, Unknown |
| description | string | Yes | Incident description (min: 20 chars, max: 5000 chars) |

#### Validation Rules

- `venue_uuid`: Required without venue_name, must exist in venues table
- `venue_name`: Required without venue_uuid, max 255 characters
- `venue_city`: Required with venue_name, max 100 characters
- `venue_address`: Optional, max 500 characters
- `latitude`: Numeric, between -90 and 90
- `longitude`: Numeric, between -180 and 180
- `incident_date`: Required, date format (Y-m-d), before or equal to today
- `time_of_day`: Required, one of: Morning, Afternoon, Evening, Night, Unknown
- `description`: Required, string, min 20 characters, max 5000 characters

#### PII Detection

The description is automatically scanned for personally identifiable information (PII). The following patterns are detected and may result in rejection:

- Email addresses
- Phone numbers (UK format)
- Names (first and last name together)

If PII is detected and cannot be automatically removed, the report will be rejected with a 422 error.

#### Response

**Status:** 201 Created

```json
{
    "message": "Report created successfully",
    "data": {
        "uuid": "7b1c2d3e-4f5a-6b7c-8d9e-0f1a2b3c4d5e",
        "venue_uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
        "incident_date": "2026-05-10",
        "time_of_day": "Evening",
        "description": "Felt unsafe due to suspicious behavior at the bar.",
        "formatted_date": "5 days ago",
        "venue": {
            "uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
            "name": "The Crown & Anchor",
            "city": "London",
            "address": "123 High Street, London, UK",
            "latitude": 51.5074,
            "longitude": -0.1278,
            "created_at": "2026-05-01T10:00:00.000Z"
        },
        "created_at": "2026-05-15T10:00:00.000Z"
    }
}
```

#### Error Responses

**422 PII Detected:**

```json
{
    "message": "Description contains personal information that cannot be automatically removed. Please remove names, emails, and phone numbers."
}
```

**404 Venue Not Found:**

```json
{
    "message": "Venue not found with UUID: 9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f"
}
```

**422 Validation Error:**

```json
{
    "message": "The venue uuid field is required when venue name is not present. (and 2 more errors)",
    "errors": {
        "venue_uuid": ["The venue uuid field is required when venue name is not present."],
        "incident_date": ["The incident date field is required."],
        "description": ["The description must be at least 20 characters."]
    }
}
```

#### Example Requests

```bash
# Create report for existing venue
curl -X POST https://drinksafe.example.com/api/reports \
  -H "Content-Type: application/json" \
  -d '{
    "venue_uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
    "incident_date": "2026-05-10",
    "time_of_day": "Evening",
    "description": "Felt unsafe due to suspicious behavior at the bar."
  }'

# Create report with new venue
curl -X POST https://drinksafe.example.com/api/reports \
  -H "Content-Type: application/json" \
  -d '{
    "venue_name": "The Crown & Anchor",
    "venue_city": "London",
    "venue_address": "123 High Street, London, UK",
    "latitude": 51.5074,
    "longitude": -0.1278,
    "incident_date": "2026-05-10",
    "time_of_day": "Evening",
    "description": "Felt unsafe due to suspicious behavior at the bar."
  }'
```

---

## Examples

### Complete Workflow Example

This example demonstrates a complete workflow: creating a venue, submitting a report, and retrieving data.

#### 1. Create a Venue

```bash
curl -X POST https://drinksafe.example.com/api/venues \
  -H "Content-Type: application/json" \
  -d '{
    "name": "The Crown & Anchor",
    "city": "London",
    "address": "123 High Street, London, UK",
    "latitude": 51.5074,
    "longitude": -0.1278
  }'
```

**Response:**
```json
{
    "message": "Venue The Crown & Anchor created successfully",
    "data": {
        "uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
        ...
    }
}
```

#### 2. Submit a Report

Use the venue UUID from the previous response:

```bash
curl -X POST https://drinksafe.example.com/api/reports \
  -H "Content-Type: application/json" \
  -d '{
    "venue_uuid": "9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f",
    "incident_date": "2026-05-10",
    "time_of_day": "Evening",
    "description": "Felt unsafe due to suspicious behavior at the bar."
  }'
```

#### 3. Get Venue Details

Retrieve the venue with all reports:

```bash
curl https://drinksafe.example.com/api/venues/9d3c4e5f-6a7b-8c9d-0e1f-2a3b4c5d6e7f
```

#### 4. Search Nearby Venues

Find venues within 5km of your location:

```bash
curl "https://drinksafe.example.com/api/venues/search?lat=51.5074&lng=-0.1278&radius=5"
```

### JavaScript/TypeScript Example

```typescript
// Fetch all venues
const fetchVenues = async () => {
    const response = await fetch('https://drinksafe.example.com/api/venues');
    const data = await response.json();
    return data.data; // Array of venues
};

// Create a report
const submitReport = async (venueUuid: string, report: {
    incident_date: string;
    time_of_day: string;
    description: string;
}) => {
    const response = await fetch('https://drinksafe.example.com/api/reports', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            venue_uuid: venueUuid,
            ...report,
        }),
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message);
    }

    const data = await response.json();
    return data.data;
};

// Search venues by city
const searchVenues = async (city: string) => {
    const response = await fetch(
        `https://drinksafe.example.com/api/venues/search?city=${encodeURIComponent(city)}`
    );
    const data = await response.json();
    return data.data;
};
```

### Python Example

```python
import requests

BASE_URL = "https://drinksafe.example.com/api"

# Fetch all venues
def get_venues(city=None):
    params = {"city": city} if city else {}
    response = requests.get(f"{BASE_URL}/venues", params=params)
    response.raise_for_status()
    return response.json()["data"]

# Create a venue
def create_venue(name, city, address, latitude, longitude):
    payload = {
        "name": name,
        "city": city,
        "address": address,
        "latitude": latitude,
        "longitude": longitude,
    }
    response = requests.post(f"{BASE_URL}/venues", json=payload)
    response.raise_for_status()
    return response.json()["data"]

# Submit a report
def submit_report(venue_uuid, incident_date, time_of_day, description):
    payload = {
        "venue_uuid": venue_uuid,
        "incident_date": incident_date,
        "time_of_day": time_of_day,
        "description": description,
    }
    response = requests.post(f"{BASE_URL}/reports", json=payload)
    response.raise_for_status()
    return response.json()["data"]

# Search nearby venues
def search_nearby_venues(lat, lng, radius=10):
    params = {"lat": lat, "lng": lng, "radius": radius}
    response = requests.get(f"{BASE_URL}/venues/search", params=params)
    response.raise_for_status()
    return response.json()["data"]
```

---

## Testing

### Testing with cURL

All examples in this documentation use cURL and can be run directly from the command line.

### Testing with Postman

Import this collection URL to Postman:
```
https://drinksafe.example.com/api/postman-collection.json
```

*(Note: Collection file would need to be created)*

### Testing Endpoints

Use the following test data for development:

**Test Venue:**
```json
{
    "name": "Test Venue",
    "city": "London",
    "address": "123 Test Street",
    "latitude": 51.5074,
    "longitude": -0.1278
}
```

**Test Report:**
```json
{
    "incident_date": "2026-05-10",
    "time_of_day": "Evening",
    "description": "This is a test report with at least twenty characters for validation purposes."
}
```

---

## Support

For API support, bug reports, or feature requests:

- **Email:** support@drinksafe.example.com
- **GitHub Issues:** https://github.com/your-org/drinksafe/issues
- **Documentation:** https://drinksafe.example.com/docs

---

## Changelog

### Version 1.0.0 (2026-05-15)

- Initial API release
- Venues endpoints (list, show, create, search)
- Reports endpoints (list, show, create)
- PII detection and moderation
- Geospatial search capabilities

---

**End of API Documentation**
