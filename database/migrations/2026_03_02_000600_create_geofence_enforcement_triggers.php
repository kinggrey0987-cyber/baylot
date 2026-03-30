<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION ensure_listing_within_negros_occidental()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    geofence_geom geometry;
BEGIN
    SELECT geom INTO geofence_geom
    FROM geofences
    WHERE name = 'Negros Occidental'
    LIMIT 1;

    IF geofence_geom IS NULL THEN
        RAISE EXCEPTION 'Negros Occidental geofence is not configured.';
    END IF;

    IF NOT ST_Within(ST_SetSRID(NEW.location, 4326), geofence_geom) THEN
        RAISE EXCEPTION 'Listing location must be inside Negros Occidental geofence.';
    END IF;

    RETURN NEW;
END;
$$;

CREATE OR REPLACE FUNCTION ensure_lot_within_negros_occidental()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    geofence_geom geometry;
BEGIN
    SELECT geom INTO geofence_geom
    FROM geofences
    WHERE name = 'Negros Occidental'
    LIMIT 1;

    IF geofence_geom IS NULL THEN
        RAISE EXCEPTION 'Negros Occidental geofence is not configured.';
    END IF;

    IF NOT ST_Within(ST_SetSRID(NEW.geom, 4326), geofence_geom) THEN
        RAISE EXCEPTION 'Lot geometry must be inside Negros Occidental geofence.';
    END IF;

    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_listings_within_geofence ON listings;
CREATE TRIGGER trg_listings_within_geofence
BEFORE INSERT OR UPDATE OF location
ON listings
FOR EACH ROW
EXECUTE FUNCTION ensure_listing_within_negros_occidental();

DROP TRIGGER IF EXISTS trg_lots_within_geofence ON lots;
CREATE TRIGGER trg_lots_within_geofence
BEFORE INSERT OR UPDATE OF geom
ON lots
FOR EACH ROW
EXECUTE FUNCTION ensure_lot_within_negros_occidental();
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS trg_lots_within_geofence ON lots;
DROP TRIGGER IF EXISTS trg_listings_within_geofence ON listings;
DROP FUNCTION IF EXISTS ensure_lot_within_negros_occidental();
DROP FUNCTION IF EXISTS ensure_listing_within_negros_occidental();
SQL);
    }
};
