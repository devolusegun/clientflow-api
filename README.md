# ClientFlow API

![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Sanctum](https://img.shields.io/badge/Auth-Laravel%20Sanctum-FF2D20?style=flat-square)
![React](https://img.shields.io/badge/Frontend-React%20(in%20progress)-61DAFB?style=flat-square&logo=react&logoColor=black)
![Status](https://img.shields.io/badge/Status-Active-brightgreen?style=flat-square)

> A Laravel REST API for freelancers and agencies to manage clients, create invoices, track payments, and monitor revenue — built with a React frontend (in progress).

---

## Why I Built This

As a freelance developer working with international clients, I needed a clean way to manage invoices, track which clients had paid, and see my outstanding revenue at a glance. Existing tools were either too complex or didn't support multi-currency workflows. ClientFlow solves the problem I actually have.

---

## Features

### Client Management
- Create, update, archive, and restore clients
- Per-client invoice summary (total billed, total paid, invoice counts by status)
- Search by name, email, or company
- Soft deletion — archived clients preserve their invoice history

### Invoice Lifecycle
- Full status workflow: **Draft → Sent → Paid / Overdue / Cancelled**
- Automatic invoice number generation (`INV-2025-0042`)
- Line items with auto-calculated `line_total` and invoice totals
- Tax rate and discount support
- Automatic overdue detection on retrieval
- Multi-currency support (USD, EUR, GBP, NGN, CAD, AUD)

### Dashboard & Reporting
- Revenue summary (total paid, outstanding, overdue)
- Monthly revenue breakdown for the current year (chart-ready)
- Recent invoice activity feed
- Per-status invoice counts

### API Design
- RESTful endpoints with consistent JSON responses
- Laravel Sanctum token-based authentication (stateless)
- Form Request validation on all inputs
- Scoped data access — users only see their own clients and invoices
- Pagination, search, and sorting on list endpoints

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 |
| Language | PHP 8.2+ |
| Database | MySQL 8.0 |
| ORM | Eloquent (with relationships, scopes, observers) |
| Authentication | Laravel Sanctum (API token) |
| Validation | Laravel Form Requests |
| Frontend | React (in progress — see `/frontend`) |

---

## API Endpoints

### Authentication
| Method | Endpoint | Description | Auth |
|---|---|---|---|
| `POST` | `/api/auth/register` | Register new user | Public |
| `POST` | `/api/auth/login` | Login, returns token | Public |
| `POST` | `/api/auth/logout` | Revoke current token | Required |
| `GET` | `/api/auth/me` | Get profile + revenue totals | Required |
| `PATCH` | `/api/auth/me` | Update profile | Required |

### Dashboard
| Method | Endpoint | Description | Auth |
|---|---|---|---|
| `GET` | `/api/dashboard` | Revenue stats + recent invoices | Required |

### Clients
| Method | Endpoint | Description | Auth |
|---|---|---|---|
| `GET` | `/api/clients` | List clients (search, sort, paginate) | Required |
| `POST` | `/api/clients` | Create client | Required |
| `GET` | `/api/clients/{id}` | Client detail + invoice summary | Required |
| `PUT/PATCH` | `/api/clients/{id}` | Update client | Required |
| `DELETE` | `/api/clients/{id}` | Archive client (soft delete) | Required |
| `POST` | `/api/clients/{id}/restore` | Restore archived client | Required |

### Invoices
| Method | Endpoint | Description | Auth |
|---|---|---|---|
| `GET` | `/api/invoices` | List invoices (filter, search, paginate) | Required |
| `POST` | `/api/invoices` | Create invoice with line items | Required |
| `GET` | `/api/invoices/{id}` | Invoice detail with items | Required |
| `PUT/PATCH` | `/api/invoices/{id}` | Update invoice + replace items | Required |
| `DELETE` | `/api/invoices/{id}` | Delete draft/cancelled invoice | Required |
| `PATCH` | `/api/invoices/{id}/status` | Transition invoice status | Required |

### Status Transitions
```
draft    → sent, cancelled
sent     → paid, overdue, cancelled
overdue  → paid, cancelled
```
Invalid transitions return a `422` with a clear error message.

---

## Local Setup

### Requirements
- PHP 8.2+
- Composer
- MySQL 8.0+
- Laravel 11

### Installation

```bash
# Clone the repo
git clone https://github.com/devolusegun/clientflow-api.git
cd clientflow-api

# Install PHP dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=clientflow
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

```bash
# Run migrations
php artisan migrate

# Seed demo data (optional)
php artisan db:seed

# Start development server
php artisan serve
```

API available at `http://localhost:8000/api`

Demo credentials (after seeding):
```
Email:    demo@clientflow.test
Password: password
```

---

## Example Requests

### Register
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Your Name",
    "email": "you@example.com",
    "password": "password",
    "password_confirmation": "password",
    "company_name": "Your Studio",
    "currency": "USD"
  }'
```

### Create an Invoice
```bash
curl -X POST http://localhost:8000/api/invoices \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": 1,
    "issue_date": "2025-01-15",
    "due_date": "2025-02-15",
    "tax_rate": 0,
    "currency": "USD",
    "payment_terms": "Net 30",
    "notes": "Thank you for your business.",
    "items": [
      {
        "description": "3-month ad campaign management",
        "quantity": 1,
        "unit_price": 12000.00
      }
    ]
  }'
```

### Mark Invoice as Paid
```bash
curl -X PATCH http://localhost:8000/api/invoices/1/status \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ "status": "paid" }'
```

---

## Database Schema

```
users
  id, name, email, password, company_name, phone, address, currency

clients
  id, user_id (FK), name, email, phone, company, address, city, country,
  notes, deleted_at

invoices
  id, user_id (FK), client_id (FK), invoice_number, status, issue_date,
  due_date, paid_at, subtotal, tax_rate, tax_amount, discount_amount,
  total_amount, currency, notes, payment_terms, deleted_at

invoice_items
  id, invoice_id (FK), description, quantity, unit_price, line_total,
  sort_order
```

**Key design decisions:**
- `invoice_items` uses a model observer to auto-calculate `line_total` on save and trigger `invoice.recalculateTotals()` — no manual total management needed
- `clients` uses soft deletes to preserve invoice history when a client is archived
- `invoices` uses soft deletes so deleted drafts can be recovered if needed
- All invoice financial columns use `DECIMAL(12,2)` to avoid floating-point rounding errors
- Composite indexes on `(user_id, status)` and `(user_id, due_date)` for efficient dashboard queries

---

## Roadmap

- [x] Authentication (Sanctum)
- [x] Client CRUD with soft delete + restore
- [x] Invoice CRUD with line items
- [x] Status workflow with transition validation
- [x] Dashboard summary + monthly revenue
- [x] Database seeder with demo data
- [ ] React frontend (in progress)
- [ ] PDF invoice generation (Laravel Snappy / DomPDF)
- [ ] Email notifications (invoice sent, payment received)
- [ ] Recurring invoice support
- [ ] Client portal (read-only invoice view)

---

## Author

**Abioye Solomon Olusegun**
Full-Stack Developer · Laravel · PHP · FastAPI · PostgreSQL · MySQL
[github.com/devolusegun](https://github.com/devolusegun) · [linkedin.com/in/kidolu](https://linkedin.com/in/kidolu) · [Portfolio](https://devolusegun.github.io/portfolio/)
