<?php

namespace App\Http\Requests;

use App\Rules\InsideNegrosOccidentalGeometryRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLotRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $geomGeoJson = $this->input('geom_geojson');
        $lat = $this->input('lot_lat');
        $lng = $this->input('lot_lng');

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return;
        }

        $hasGeoJson = is_string($geomGeoJson) && trim($geomGeoJson) !== '';
        if ($hasGeoJson) {
            return;
        }

        $pointGeoJson = json_encode([
            'type' => 'Point',
            'coordinates' => [round((float) $lng, 6), round((float) $lat, 6)],
        ]);

        if ($pointGeoJson !== false) {
            $this->merge([
                'geom_geojson' => $pointGeoJson,
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->isAgent() ?? false;
    }

    public function rules(): array
    {
        return [
            'lot_code' => ['required', 'string', 'max:100'],
            'area_sqm' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,reserved,sold'],
            'lot_lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lot_lng'],
            'lot_lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lot_lat'],
            'geom_geojson' => ['required', 'string', new InsideNegrosOccidentalGeometryRule()],
        ];
    }
}
