<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class InsideNegrosOccidentalPointRule implements ValidationRule
{
    public function __construct(
        private readonly ?float $lat,
        private readonly ?float $lng,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->lat === null || $this->lng === null) {
            $fail('Latitude and longitude are required.');

            return;
        }

        $inside = DB::table('geofences')
            ->where('name', 'Negros Occidental')
            ->whereRaw(
                "ST_Within(ST_SetSRID(ST_MakePoint(?, ?), 4326), geom)",
                [$this->lng, $this->lat]
            )
            ->exists();

        if (! $inside) {
            $fail('Location must be inside Negros Occidental geofence.');
        }
    }
}
