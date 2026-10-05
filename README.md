# Pool My Finger

## Introduction

### Scope

Pool My Finger (PMF) is a web application designed to make it easier for the public to find free swim times at Montreal's public pools. Users can search for a specific pool or a desired swim time, then apply filters based on the activities they want to participate in. With all these pools at their fingertips, users no longer need to search each location individually.

### Inspiration

Our group members are frequent swimmers who use these free swim times. We noticed that navigating from one pool's website to another was cumbersome: the simplest approach was to search for one location, then repeat the process for the next. Even when using Google Maps, users still had to visit each pool's website to find its swim schedule.

PMF brings this information together in one place to streamline searching, comparing, and filtering public pool schedules.

Pool My Finger is a full-stack web application for finding public pools in Montreal. Users can search by pool name or postal code, filter by distance, pool type, and date/time, then open a detailed pool view with contact information, maps, and schedule previews.

This project demonstrates layered PHP application design, REST-style API development, relational data modeling, session-based authentication, and a responsive Tailwind CSS frontend.

## Highlights

- Search pools by name or Canadian postal code.
- Rank nearby pools by distance and relevance for a requested date and time.
- Filter by indoor, outdoor, wading pool, and splash pad types.
- View pool images, addresses, phone numbers, websites, map embeds, and directions.
- Preview pool schedules and time blocks.
- Authenticate admin users with hashed passwords and revocable session cookies.
- Create, update, and delete pool records through authenticated API operations.
- Support light and dark themes with persisted user preferences.
- Import pool data from the scraper pipeline into MySQL.

## Technology

- PHP 8+ with Composer and PSR-4 autoloading
- MySQL with PDO and a normalized relational schema
- Tailwind CSS 4
- Vanilla JavaScript for search, filtering, pagination, and dynamic views
- Python scraper integration for refreshing pool data

## Architecture

The backend is organized into clear application layers:

```text
HTTP request
	-> public/index.php
	-> API router
	-> controller
	-> service
	-> repository
	-> MySQL
```

The project uses models, repositories, services, and controllers to keep HTTP concerns, business rules, and database queries separate. Composer maps the backend namespaces under `src/backend/` using PSR-4 autoloading.

## Getting Started

### Prerequisites

- PHP 8.0 or newer with PDO MySQL enabled
- Composer
- MySQL 8 or a compatible MySQL server
- Node.js and npm
- Python, if using the scraper import workflow

### Install dependencies

```bash
composer install
npm install
```

### Configure the database

The application reads these environment variables and falls back to local MySQL defaults:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=pool_my_finger
export DB_USER=root
export DB_PASS=
```

Create the database and tables with:

```bash
php db/schema.php
```

To recreate the application tables, use `--force`. To run the scraper and import a fresh snapshot, use `--scrape`:

```bash
php db/schema.php --force --scrape
```

### Build frontend assets

For a production-style build:

```bash
npm run build
```

For live Tailwind CSS compilation during development:

```bash
npm run dev
```

### Run the application

From the project root, start PHP's development server with `public/` as the document root:

```bash
php -S localhost:8000 -t public
```

Open [http://localhost:8000](http://localhost:8000) in a browser.

Create an initial user with:

```bash
php scripts/create-user.php
```

## Main Routes

### Pages

| Route | Purpose |
| --- | --- |
| `/` | Search and browse pools |
| `/pool/{id}` | View pool details and schedule previews |
| `/login` | Admin login |
| `/admin` | Authenticated administration view |

### API

| Endpoint | Purpose |
| --- | --- |
| `GET /api/pools` | List and search pools |
| `GET /api/pools/{id}` | Fetch one pool |
| `GET /api/pools/{id}/schedules` | Fetch schedules for a pool |
| `GET /api/pools-top` | Find relevant pools by postal code, distance, time, and type |
| `GET /api/pool-types` | List available pool types |
| `POST /api/auth/login` | Create an authenticated session |
| `POST /api/auth/logout` | Revoke the current session |
| `POST /api/pools` | Create a pool; authentication required |
| `PUT/PATCH /api/pools/{id}` | Update a pool; authentication required |
| `DELETE /api/pools/{id}` | Delete a pool; authentication required |

## Project Structure

```text
public/                 Web entry point and compiled assets
src/backend/            API, controllers, services, repositories, and models
src/frontend/           PHP pages, reusable components, and Tailwind source
db/                     MySQL connection and schema/import bootstrap
scraper/                Pool data scraper integration
scripts/                Command-line utilities such as user creation
```

## Current Scope

The core pool discovery, detail, schedule, authentication, and pool management flows are implemented. Planned follow-up work includes adding an admin-panel diff between the current database data and newly scraped data, plus completing the broader standalone schedules endpoint.

## Planning

Early project planning and architecture notes are available in the [project planning document](https://docs.google.com/document/d/1-8W4G4xn1iulS8VQ8h-acFAy4ne7YoXQg-JgpVC1h9A/edit?usp=sharing).

## License

This project is available under the MIT License.
