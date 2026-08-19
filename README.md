# InventoryFlow SaaS — Complete Learning MVP

InventoryFlow is a bilingual, multi-tenant SaaS inventory and sales management application built as a learning and portfolio project.

## Stack

- PHP 8.4
- Laravel 13
- PostgreSQL 18
- Laravel Sanctum
- Blade
- HTML5 / CSS3 / Vanilla JavaScript
- Docker Compose
- Nginx
- Mailpit for local email testing
- PHPUnit
- GitHub Actions

## Main SaaS features

- Account registration, login, logout and password reset
- Multi-tenant workspaces
- Workspace switcher
- Role-based access control: Owner, Admin, Manager, Member and Viewer
- Team invitations by email
- Local subscription / billing simulator with Free, Pro and Business plans
- Product limits, member limits and warehouse limits enforced by the backend
- Product CRUD
- Categories
- Suppliers
- Customers
- Multiple warehouses
- Stock by warehouse
- Stock in / stock out movements
- Stock cannot become negative
- Low-stock notifications
- Orders with multiple order items
- Automatic stock reduction when an order is processed
- Automatic stock restoration when a processed order is cancelled
- Dashboard
- Sales and inventory reports
- Audit log
- Workspace settings: currency, locale, timezone and business type
- English / Portuguese UI
- PostgreSQL data persistence
- Automated tests
- CI workflow

## Important billing note

The project contains a **local billing simulator**. It implements the difficult SaaS part — plans, subscriptions, limits and authorization — but does not charge real money. A real payment provider requires a provider account, API credentials, webhook configuration and production security review.

## Local URLs

After starting Docker:

- InventoryFlow: `http://localhost:8080`
- Mailpit email inbox: `http://localhost:8025`
- PostgreSQL host port: `5432`

## Demo account

- Email: `demo@inventoryflow.com`
- Password: `inventory123`

## Start

```bash
docker compose up -d --build
```

Check containers:

```bash
docker compose ps
```

Run tests:

```bash
docker compose exec app php artisan test
```

View routes:

```bash
docker compose exec app php artisan route:list
```

Follow Laravel logs:

```bash
docker compose exec app tail -f storage/logs/laravel.log
```

Stop:

```bash
docker compose down
```

## Architecture

```text
Browser
   |
   v
Nginx
   |
   v
public/index.php
   |
   v
Laravel
   |
   +--> Web routes --> Blade authentication pages
   |
   +--> API routes --> Controllers
                         |
                         +--> Services / business rules
                         |
                         +--> Eloquent Models
                                  |
                                  v
                              PostgreSQL
```

## SaaS tenancy

Every business workspace is a tenant:

```text
User
  |
  +---- Workspace A ---- Products / Customers / Orders / Stock
  |
  +---- Workspace B ---- Products / Customers / Orders / Stock
```

API queries are scoped to the currently selected workspace.

## Suggested learning order

1. `routes/web.php`
2. `resources/views/login.blade.php`
3. `AuthController.php`
4. `routes/api.php`
5. `EnsureWorkspaceSelected.php`
6. `Workspace.php` and `User.php`
7. Migrations
8. `ProductController.php`
9. `Product.php`
10. `app.js`
11. `InventoryController.php` + `StockService.php`
12. `OrderController.php` + `OrderItem.php`
13. Team / invitation / roles
14. Reports
15. Docker / Nginx
16. Tests and GitHub Actions

Read `docs/GUIA_COMPLETO_SAAS_PTBR.md` for the detailed explanation in Portuguese.
