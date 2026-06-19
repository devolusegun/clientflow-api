# ClientFlow API Reference

Base URL: 'https://localhost:8000/api'

All protected endpoints require:
```
Authorization: Bearer {token}
Content-Type: application/json
```

---

## Authentication 

### POST /auth/register
Creates a new user account and returns ap API token.

**Body:**
```json
{
  "name": "Solomon Olusegun",
  "email": "solomon@example.com",
  "password": "password",
  "password_confirmation": "password",
  "company_name": "DevOlusegun Studio",
  "currency": "USD"
}
```
**Response `201`:**
```json
{
  "message": "Registration successful.",
  "user": { "id": 1, "name": "Solomon Olusegun", "email": "...", "currency": "USD" },
  "token": "1|abc123..."
}
```

---

### POST /auth/login
Authenticates a user and returns a fresh token.

**Body:**
```json
{ "email": "solomon@example.com", "password": "password" }
```

**Response `200`:**
```json
{ "message": "Login successful.", "user": {}, "token": "2|xyz456..." }
```

---

### POST /auth/logout 🔒
Revokes the current token.

**Response `200`:**
```json
{ "message": "Logged out successfully." }
```

---

### GET /auth/me 🔒
Returns the authenticated user's profile and revenue totals.

**Response `200`:**
```json
{
  "user": { "id": 1, "name": "...", "currency": "USD" },
  "total_revenue": 15000.00,
  "total_outstanding": 2500.00
}
```

---

### PATCH /auth/me 🔒
Updates the authenticated user's profile.

**Body (all fields optional):**
```json
{ "name": "New Name", "currency": "EUR" }
```

---

## Dashboard

### GET /dashboard 🔒
Returns revenue statistics and recent invoice activity.

**Response `200`:**
```json
{
  "stats": {
    "total_invoices": 12,
    "paid_count": 8,
    "overdue_count": 1,
    "total_paid": 45000.00,
    "total_outstanding": 3200.00,
    "total_overdue": 1800.00
  },
  "recent_invoices": [],
  "monthly_revenue": {}
}
```

---

## Clients

### GET /clients 🔒
Returns paginated list of clients.

**Query params:**
| Param | Type | Description |
|---|---|---|
| search | string | Search by name, email, or company |
| sort_by | string | `name`, `email`, `company`, `created_at` |
| sort_dir | string | `asc` or `desc` |
| per_page | integer | Max 100, default 15 |

---

### POST /clients 🔒
Creates a new client.

**Body:**
```json
{
  "name": "DonateWater Foundation",
  "email": "projects@donatewater.org",
  "company": "DonateWater",
  "country": "Switzerland"
}
```

**Response `201`:**
```json
{ "message": "Client created successfully.", "client": {} }
```

---

### GET /clients/{id} 🔒
Returns a single client with invoice summary.

### PATCH /clients/{id} 🔒
Updates a client's details.

### DELETE /clients/{id} 🔒
Archives a client (soft delete). Preserves invoice history.

### POST /clients/{id}/restore 🔒
Restores an archived client.

---

## Invoices

### GET /invoices 🔒
Returns paginated list of invoices.

**Query params:**
| Param | Type | Description |
|---|---|---|
| status | string | `draft`, `sent`, `paid`, `overdue`, `cancelled` |
| client_id | integer | Filter by client |
| date_from | date | Filter by issue_date from |
| date_to | date | Filter by issue_date to |
| search | string | Search by invoice number or client name |
| sort_by | string | `created_at`, `issue_date`, `due_date`, `total_amount` |
| sort_dir | string | `asc` or `desc` |
| per_page | integer | Max 100, default 15 |

---

### POST /invoices 🔒
Creates an invoice with line items in a single request.

**Body:**
```json
{
  "client_id": 1,
  "issue_date": "2025-01-15",
  "due_date": "2025-02-15",
  "tax_rate": 0,
  "currency": "USD",
  "payment_terms": "Net 30",
  "items": [
    {
      "description": "Campaign management — 3 months",
      "quantity": 1,
      "unit_price": 12000.00
    }
  ]
}
```

**Response `201`:**
```json
{ "message": "Invoice created.", "invoice": {} }
```

---

### GET /invoices/{id} 🔒
Returns a single invoice with all line items and client.

### PATCH /invoices/{id} 🔒
Updates invoice details and optionally replaces all line items.

### DELETE /invoices/{id} 🔒
Deletes a draft or cancelled invoice.

### GET /invoices/overdue 🔒
Returns all overdue invoices. Auto-promotes sent+past-due invoices to overdue status.

### PATCH /invoices/{id}/status 🔒
Transitions invoice status.

**Body:**
```json
{ "status": "paid" }
```

**Valid transitions:**
```
draft    → sent, cancelled
sent     → paid, overdue, cancelled
overdue  → paid, cancelled
```

---

## Health

### GET /health
Returns API status. No authentication required.

**Response `200`:**
```json
{ "status": "ok", "timestamp": "2025-05-25T10:00:00.000000Z" }
```