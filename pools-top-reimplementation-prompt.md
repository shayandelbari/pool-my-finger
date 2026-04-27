# Pools Top Reimplementation Prompt

Reimplement the `pools-top` endpoint in a clean, separated architecture without changing the behavior of the existing pools endpoints.

## Goal

Create a new `GET /api/pools-top` endpoint that returns the best matching pools for a user query defined by postal code, time, radius, and optional pool type.

This endpoint should live in separate files from the current pools CRUD flow.

## File Split

Use separate files for this feature instead of extending the existing pools controller/service/repository directly.

Suggested split:

- `src/backend/api/routes/pools-top.php`
- `src/backend/controllers/TopPoolsController.php`
- `src/backend/services/TopPoolsService.php`
- `src/backend/repositories/TopPoolsRepository.php`
- `src/backend/models/TopPoolResult.php` or a similar DTO/model file

Keep the existing `pools` endpoint untouched.

## Request Flow

1. The route file handles only routing and method dispatch.
2. The controller handles HTTP concerns and basic query parameter checks.
3. The service handles business logic:
   - validate Canadian postal code
   - geocode postal code to latitude/longitude
   - convert radius in km to a simple latitude/longitude bounding box
   - call the repository with derived bounds and normalized parameters
   - compute exact distance using a simple Pythagorean approximation
   - filter out pools outside the requested radius
4. The repository handles database querying:
   - find active pools inside the bounding box
   - match pools to schedules/time blocks for the requested date and day
   - rank the candidate rows by closeness of the schedule time slot to the requested datetime
   - return hydrated rows or DTO-like arrays
5. The controller formats the final JSON response.

## Query Parameters

Define the input contract clearly.

Required parameters:

- `postalCode: string`
- `radius: float`

Optional parameters:

- `dateTime: DateTimeInterface` or ISO-8601 string that gets parsed into a `DateTimeImmutable`
- `type: string`

Suggested rules:

- `postalCode` must be a non-empty Canadian postal code.
- `radius` must be a positive number and should be capped at 50 km.
- `dateTime` should default to `now` if omitted.
- `dateTime` should be rejected if it is in the past or more than six months in the future.
- `type` should be optional and normalized to lowercase.
- Allowed type values should stay aligned with the current pool type set: `pisi`, `piex`, `pata`, `jeud`.

## Type Contract

Use explicit input and output types for this feature.

Request shape:

```text
TopPoolsRequest = {
  postalCode: string,
  radius: float,
  dateTime?: DateTimeInterface,
  type?: string
}
```

Response shape:

```text
TopPoolsResponse = {
  pools: TopPoolResult[]
}
```

Each returned pool entry should contain:

```text
TopPoolResult = {
  pool: PoolSummary,
  relevantSchedule: RelevantSchedule,
  distance: float
}
```

Suggested nested shapes:

```text
PoolSummary = {
  id: int,
  name: string,
  address: ?string,
  imageUrl: ?string,
  website: ?string,
  map: ?string,
  latitude: ?float,
  longitude: ?float,
  phone: ?string,
  active: bool,
  createdAt: string,
  types: PoolTypeSummary[]
}
```

```text
RelevantSchedule = {
  id: int,
  scheduleType: ScheduleTypeSummary,
  effectiveDate: string,
  endDate: string,
  dayOfWeek: string,
  startTime: string,
  endTime: string,
  label: ?string,
  timeGapSeconds: int
}
```

## Behavioral Decisions Captured From the Diff

- Keep ranking by schedule-slot closeness in the repository if that is the minimal place to do it.
- Keep exact distance filtering in the service after the database returns candidate rows.
- Keep the controller thin and focused on request validation and response formatting.
- Keep the endpoint separate from the existing `/api/pools` flow so the old endpoint does not change behavior.
- Return structured JSON that includes both pool data and the schedule that made the row relevant.
- Normalize output distance to two decimal places.

## Repository Query Strategy

The repository should:

- select only active pools
- restrict by latitude/longitude bounding box
- restrict by matching date and day-of-week
- join schedules and time blocks
- optionally filter by pool type
- rank the best schedule per pool by time-slot closeness

The database query can use a window function or equivalent ranking approach if available.

## Service Strategy

The service should:

- validate and normalize the postal code
- geocode the postal code once
- compute a rough km-to-degree bounding box
- fetch candidate pools from the repository
- compute approximate distance with a simple Pythagorean formula
- drop results beyond the requested radius
- return the final response list

## Non-Goals

- Do not alter the behavior of existing `pools`, `pools/{id}`, or auth endpoints.
- Do not mix the new endpoint into the current pools controller/service/repository if separate files are available.
- Do not over-engineer the distance math; keep it intentionally simple and consistent with the current design.

## Implementation Preference

If you have to choose where to keep ranking, prefer the repository layer if it reduces extra passes in PHP.

If you have to choose where to keep the final radius filtering, prefer the service layer.

## Notes For Rebuild

- Preserve clean separation between request parsing, business logic, and SQL.
- Keep the endpoint deterministic and easy to test.
- Treat the new endpoint as an independent feature, not as an extension of the existing pools CRUD path.
