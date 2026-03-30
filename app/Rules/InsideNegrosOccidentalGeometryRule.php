<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class InsideNegrosOccidentalGeometryRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Geometry payload must be valid GeoJSON.');

            return;
        }

        $inside = DB::table('geofences')
            ->where('name', 'Negros Occidental')
            ->whereRaw(
                "ST_Within(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326), geom)",
                [$value]
            )
            ->exists();

        if (! $inside) {
            $fail('Geometry must be inside Negros Occidental geofence.');
        }
    }
}
