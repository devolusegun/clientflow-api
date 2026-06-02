# Changelog

All notable changes to ClientFlow API are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased] 

## [0.4.0] - 2025-05-xx
## Added 
- API request logging middleware with response time tracking
- Rate limiting on auth endpoints (10 req/min) and protected routes (60 req/min)
- Global JSON exception handler for consistent API error responses
- Health check endpoint at GET /api/health

## [0.3.0] - 2025-05-xx
## Added
- DashboardResource for shaped summary endpoint responses
- InvoiceResource, InvoiceItemResource, ClientResource API Resources
- Search, date range, and sort model scopes on Invoice model
- Search and sort model scopes on Client model

## [0.2.0] - 2025-05-xx
### Added
- Input validation on invoice and client index endpoints
- per_page cap (max 100) across all paginated endpoints
- Overdue invoices endpoint with auto-status promotion

## [0.1.0] - 2025-05-xx
### Added
- Initial Laravel 11 project setup with Sanctum authentication
- User, Client, Invoice, InvoiceItem models with Eloquent relationships
- Database migrations for all core tables with indexes
- Form Request validation classes for auth, clients, and invoices
- AuthController, ClientController, InvoiceController
- Invoice status workflow with transition validation
- Dashboard summary endpoint with monthly revenue breakdown
- Database seeder with realistic demo data