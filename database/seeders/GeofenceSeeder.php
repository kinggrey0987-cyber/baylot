<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeofenceSeeder extends Seeder
{
    public function run(): void
    {
        $geoJson = [
            'type' => 'Polygon',
            'coordinates' => [[
                [123.04063495905046, 11.038654928235943],
                [122.25668053765122, 9.824912971490676],
                [122.92774330256958, 8.939654935818183],
                [123.41961285687302, 9.110773553128908],
                [123.37814182899189, 9.352977813291261],
                [123.2790857931372, 9.422287398075738],
                [123.40660263671327, 10.381106626290588],
                [123.686505743042, 10.88577118829096],
                [123.3532125477978, 11.073604717248642],
                [123.04063495905046, 11.038654928235943],
            ]],
        ];

        DB::statement(
            "INSERT INTO geofences (id, name, geom, created_at, updated_at)
             VALUES (?, 'Negros Occidental', ST_SetSRID(ST_Multi(ST_GeomFromGeoJSON(?)), 4326), NOW(), NOW())
             ON CONFLICT (name)
             DO UPDATE SET geom = EXCLUDED.geom, updated_at = NOW()",
            [(string) Str::uuid(), json_encode($geoJson, JSON_THROW_ON_ERROR)]
        );
    }
}
