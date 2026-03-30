# BayLot

BayLot is a Laravel web app for property listing + site visit appointment management with a hybrid model:
- Shared project listings (admin-managed, FCFS claim queue for all active agents)
- Exclusive listings (agent-owned, auto-routed lead + appointment assignment)

## Stack
- PHP 8.2+
- Laravel (project currently uses Laravel 12 skeleton; code is Laravel 11-compatible)
- PostgreSQL 15+ with PostGIS
- Bootstrap 5 + Blade + Breeze auth
- Leaflet + OpenStreetMap
- UUID primary keys
- SRID 4326 geometries

## Windows Setup (PowerShell)

1. Install dependencies
```powershell
composer install
npm.cmd install
```

2. Environment setup
```powershell
Copy-Item .env.example .env
php artisan key:generate
```

3. Configure `.env` for PostgreSQL
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=baylot
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

4. Ensure PostGIS is enabled (if needed run manually in PostgreSQL)
```sql
CREATE EXTENSION IF NOT EXISTS postgis;
```

5. Run migrations + seeders
```powershell
php artisan migrate --seed
```

6. Build frontend
```powershell
npm.cmd run dev
```

7. Run app
```powershell
php artisan serve
```

## Demo Accounts
- Admin: `admin@baylot.local` / `password`
- Agent 1: `agent1@baylot.local` / `password`
- Agent 2: `agent2@baylot.local` / `password`
- Buyer: `buyer@baylot.local` / `password`

## Geofence Enforcement
- Application-level validation:
	- `app/Rules/InsideNegrosOccidentalPointRule.php`
	- `app/Rules/InsideNegrosOccidentalGeometryRule.php`
- Database-level trigger enforcement:
	- `database/migrations/2026_03_02_000600_create_geofence_enforcement_triggers.php`

## FCFS Claim Logic
- Atomic lead claim flow:
	- `app/Http/Controllers/Agent/LeadQueueController.php`
- Unique claim constraint:
	- `lead_claims.lead_id` unique index
- Exclusive listing auto-routing on lead creation:
	- `app/Http/Controllers/Buyer/LeadController.php`

## Notes
- Shared listing claims are open to all active agents.
- Listing and lot geometries outside Negros Occidental are rejected by validation and DB triggers.
