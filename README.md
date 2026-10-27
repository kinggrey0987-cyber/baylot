# BayLot — Real Estate & Property Management Platform

[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-PostGIS-336791.svg)](https://postgis.net/)
[![Vite](https://img.shields.io/badge/Vite-Bundler-646CFF.svg)](https://vitejs.dev/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

BayLot is a modern Laravel web application for real estate property listing and site-visit appointment management featuring a hybrid inventory model and geospatial geofencing:

- **Shared Project Listings**: Brokerage/project-managed developments with a First-Come, First-Served (FCFS) atomic lead claim queue for active agents.
- **Exclusive Listings**: Individual agent-managed postings with automated lead routing and appointment scheduling.
- **Geofenced Property Validation**: Boundary checking at both application and database trigger levels to enforce geographical boundaries (Negros Occidental).
- **Two-Factor Authentication**: Email-based OTP verification with resend cooldowns and brute-force mitigation.

---

## 🚀 Tech Stack

- **Backend**: PHP 8.2+, Laravel 12 (UUID primary keys, Eloquent ORM)
- **Database**: PostgreSQL 15+ with PostGIS spatial extension (SRID 4326 geometries)
- **Frontend**: Blade templates, Bootstrap 5, Leaflet.js + OpenStreetMap, Vite
- **Security**: Route-level throttling, IP brute-force protection, OWASP security headers, hashed OTP verification

---

## 📋 Prerequisites

Before setting up locally, ensure you have:
1. **PHP 8.2 or higher** with `pdo_pgsql`, `pgsql`, and `zip` extensions enabled.
2. **PostgreSQL 15+** with the **PostGIS** extension installed.
3. **Composer 2.x**
4. **Node.js (v18+)** & **npm**

---

## 🛠️ Local Installation & Setup

### 1. Clone the repository
```bash
git clone https://github.com/kinggrey0987-cyber/itedev_baylot.git
cd itedev_baylot
```

### 2. Install Dependencies
```bash
# Install PHP backend dependencies
composer install

# Install Frontend dependencies
npm install
```

### 3. Configure Environment
Copy the example environment file:
```bash
cp .env.example .env
# Windows PowerShell:
# Copy-Item .env.example .env
```

Generate your application encryption key:
```bash
php artisan key:generate
```

Configure your PostgreSQL database connection in `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=baylot
DB_USERNAME=postgres
DB_PASSWORD=your_postgres_password
```

### 4. Run Migrations & Seeders
Ensure the database exists, then run migrations and seeders:
```bash
php artisan migrate --seed
```

### 5. Start Local Servers
Run the backend and frontend dev servers in two separate terminals:

**Terminal 1 (Backend)**:
```bash
php artisan serve
```
> App will be accessible at: `http://127.0.0.1:8000`

**Terminal 2 (Frontend Assets)**:
```bash
npm run dev
```

---

## 👥 Demo Accounts (Seeded for Local Testing)

When database seeding is performed (`--seed`), the following test accounts are populated:

| Role | Email | Default Password |
|---|---|---|
| **Admin** | `admin@baylot.local` | `password` |
| **Broker** | `broker1@baylot.local` | `password` |
| **Agent** | `agent1@baylot.local` | `password` |
| **Buyer** | `buyer@baylot.local` | `password` |

> ⚠️ **Note**: Default seeded accounts and passwords are for local development and demonstration purposes only. Always use unique, high-entropy passwords in production.

---

## 🗺️ Geospatial & Business Logic

### Geofence Enforcement
- **Application-level validation**:
  - `app/Rules/InsideNegrosOccidentalPointRule.php`
  - `app/Rules/InsideNegrosOccidentalGeometryRule.php`
- **Database-level trigger enforcement**:
  - `database/migrations/2026_03_02_000600_create_geofence_enforcement_triggers.php`

### FCFS Lead Claim Flow
- Atomic lead claim transactions: `app/Http/Controllers/Agent/LeadQueueController.php`
- Unique claim index constraint: `lead_claims.lead_id`
- Exclusive listing auto-routing: `app/Http/Controllers/Buyer/LeadController.php`

---

## 🔒 Security Features
- **Brute Force Protection**: IP-wide and account-level rate limiting on login, registration, password resets, and OTP resends.
- **OWASP HTTP Headers**: Automatic `X-Frame-Options`, `X-Content-Type-Options`, and `Referrer-Policy` headers applied across all routes.
- **Role-Based Access Control**: Strict middleware verification for Admin, Broker, Agent, and Buyer roles.

---

## 📄 License
This project is open-sourced software licensed under the [MIT license](LICENSE).
