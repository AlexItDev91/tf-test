# TF-Test

TF-Test is a Laravel application built as a technical test project.

The project demonstrates:
- clean separation of layers (Controllers / Services / Repositories)
- Livewire (Class API) with Blade
- queues (Jobs)
- events & listeners
- scheduler (cron)
- Pest tests

---

## Requirements

- **PHP 8.5**
- Composer
- Node.js 18+
- SQLite / MySQL / PostgreSQL
- Redis (optional, for queues)

---

## Fast Deployment

To quickly set up the project, run:

```bash
composer setup
```

This command will:
- Install composer dependencies
- Create `.env` from `.env.example` if it doesn't exist
- Generate application key
- Run migrations
- Install npm dependencies
- Build assets

## Running the Application

To start the local development server, run:

```bash
php artisan serve
```

The application will be available at:
http://127.0.0.1:8000

## Authentication

Cart and checkout functionality require an authenticated user.
You can use the standard Laravel registration and login flow.

## Queues

- low stock notifications
- daily sales report emails

Run the queue worker locally:
```bash
php artisan queue:work
```

## Scheduler (Daily Sales Report)

A daily sales report is generated and sent via a scheduled job.

To test it locally:

```bash
php artisan schedule:run
```
For production, add the following cron entry:

```bash
* * * * * php /path-to-project/artisan schedule:run >> /dev/null 2>&1
```

## Running Tests

To run the test suite, you can use the following command:

```bash
composer test
```

Alternatively, you can run:

```bash
php artisan test
```

## Seed Products

To seed the database with products, run:

```bash
php artisan db:seed --class=ProductSeeder
```

## Notes

- All monetary values are stored in cents (int)
- Carts and orders are always linked to the authenticated user
- Controllers contain no business logic — all logic lives in Services and Repositories

## Architecture Overview

The application follows a layered architecture with a clear separation of responsibilities.

### Controllers
- Handle HTTP requests and responses
- Contain **no business logic**
- Delegate all operations to Services

### Services
- Contain all business logic
- Orchestrate multiple repositories
- Enforce domain rules (cart behavior, checkout, stock handling)
- Are framework-agnostic where possible

### Repositories
- Responsible for data access only
- All database interaction happens here
- Implemented using the **Repository pattern**
- Support multiple implementations (Eloquent, Cached)

Structure:
- `Repositories/Contracts` — interfaces
- `Repositories/Implementations/Eloquent` — database access
- `Repositories/Implementations/Cached` — cache decorators

### Events & Listeners
- Used to decouple side effects from business logic
- Example:
    - `ProductStockChanged` → `SendLowStockNotification`
- Makes the system extensible and testable

### Jobs & Queues
- Email notifications and reports are sent asynchronously
- Prevent slow operations during user requests
- Jobs are triggered from Services or Event listeners

### Scheduler
- Laravel Scheduler is used for recurring tasks
- Daily sales report is dispatched via a queued Job

### Frontend
- Built with **Livewire (Class API)** and Blade
- Alpine.js for small UI interactions
- UI communicates directly with Services through Livewire components
- No session-based cart — everything is persisted per authenticated user

---

## Design Principles

- Single Responsibility Principle
- Explicit dependencies (constructor injection)
- No hidden magic in controllers
- Database writes are transactional where required
- All money values stored as integers (cents)
