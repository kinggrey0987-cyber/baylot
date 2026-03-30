<?php

namespace App\Http\Requests;

use App\Rules\InsideNegrosOccidentalPointRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBrokerOrAgent() ?? false;
    }

    public function rules(): array
    {
        return [
            'listing_type' => ['required', 'in:shared,exclusive'],
            'property_type' => ['required', 'in:lot,house_and_lot,condo'],
            'project_id' => ['nullable', 'uuid', 'exists:projects,id'],
            'owner_agent_id' => ['nullable', 'uuid', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'price_max' => ['nullable', 'numeric', 'gte:price', 'max:999999999999.99'],
            'lot_area_sqm' => ['nullable', 'numeric', 'gt:0', 'max:9999999999.99'],
            'lot_area_max_sqm' => ['nullable', 'numeric', 'gte:lot_area_sqm', 'max:9999999999.99'],
            'floor_area_sqm' => ['nullable', 'numeric', 'gt:0', 'max:9999999999.99'],
            'price_per_sqm' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:999'],
            'bedrooms_max' => ['nullable', 'integer', 'gte:bedrooms', 'max:999'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:999'],
            'bathrooms_max' => ['nullable', 'integer', 'gte:bathrooms', 'max:999'],
            'floors' => ['nullable', 'integer', 'min:0', 'max:999'],
            'floors_max' => ['nullable', 'integer', 'gte:floors', 'max:999'],
            'available_units' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'status' => ['sometimes', 'in:available,reserved,sold,unavailable'],
            'lat' => ['required', 'numeric', 'between:-90,90', new InsideNegrosOccidentalPointRule($this->float('lat'), $this->float('lng'))],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.max' => 'Price must not exceed 999,999,999,999.99.',
            'price_max.max' => 'Maximum price must not exceed 999,999,999,999.99.',
            'lot_area_sqm.max' => 'Lot area must not exceed 9,999,999,999.99 sqm.',
            'lot_area_max_sqm.max' => 'Maximum lot area must not exceed 9,999,999,999.99 sqm.',
            'floor_area_sqm.max' => 'Floor area must not exceed 9,999,999,999.99 sqm.',
            'price_per_sqm.max' => 'Price per sqm must not exceed 999,999,999,999.99.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = $this->input('listing_type');
            $propertyType = $this->input('property_type');
            $lotArea = $this->input('lot_area_sqm');
            $lotAreaMax = $this->input('lot_area_max_sqm');
            $floorArea = $this->input('floor_area_sqm');
            $pricePerSqm = $this->input('price_per_sqm');
            $priceMax = $this->input('price_max');
            $bedrooms = $this->input('bedrooms');
            $bedroomsMax = $this->input('bedrooms_max');
            $bathrooms = $this->input('bathrooms');
            $bathroomsMax = $this->input('bathrooms_max');
            $floors = $this->input('floors');
            $floorsMax = $this->input('floors_max');
            $projectOnlyFields = [
                'price_max' => ['label' => 'Maximum price', 'value' => $priceMax],
                'lot_area_max_sqm' => ['label' => 'Maximum lot area', 'value' => $lotAreaMax],
                'bedrooms_max' => ['label' => 'Maximum bedrooms', 'value' => $bedroomsMax],
                'bathrooms_max' => ['label' => 'Maximum bathrooms', 'value' => $bathroomsMax],
                'floors_max' => ['label' => 'Maximum floors', 'value' => $floorsMax],
                'available_units' => ['label' => 'Available units', 'value' => $this->input('available_units')],
            ];

            if ($type === 'shared' && empty($this->input('project_id'))) {
                $validator->errors()->add('project_id', 'Project is required for shared listings.');
            }

            if ($type === 'exclusive' && empty($this->input('owner_agent_id'))) {
                $validator->errors()->add('owner_agent_id', 'Owner agent is required for exclusive listings.');
            }

            if ($type === 'exclusive') {
                foreach ($projectOnlyFields as $field => $meta) {
                    if ($meta['value'] !== null && $meta['value'] !== '') {
                        $validator->errors()->add($field, $meta['label'].' is only applicable to project/shared postings.');
                    }
                }
            }

            if ($propertyType === 'house_and_lot') {
                if ($lotArea === null || $lotArea === '') {
                    $validator->errors()->add('lot_area_sqm', 'Lot area is required for house and lot postings.');
                }

                if ($floorArea === null || $floorArea === '') {
                    $validator->errors()->add('floor_area_sqm', 'Floor area is required for house and lot postings.');
                }
            }

            if ($propertyType === 'lot') {
                if ($lotArea === null || $lotArea === '') {
                    $validator->errors()->add('lot_area_sqm', 'Lot area is required for lot postings.');
                }

                if ($pricePerSqm === null || $pricePerSqm === '') {
                    $validator->errors()->add('price_per_sqm', 'Price per sqm is required for lot postings.');
                }

                if (is_numeric($lotArea) && is_numeric($pricePerSqm)) {
                    $computedPrice = round((float) $lotArea * (float) $pricePerSqm, 2);

                    if ($computedPrice > 999999999999.99) {
                        $validator->errors()->add('price_per_sqm', 'Computed total price must not exceed 999,999,999,999.99.');
                    }

                    if (is_numeric($lotAreaMax)) {
                        $computedPriceMax = round((float) $lotAreaMax * (float) $pricePerSqm, 2);

                        if ($computedPriceMax > 999999999999.99) {
                            $validator->errors()->add('lot_area_max_sqm', 'Computed maximum total price must not exceed 999,999,999,999.99.');
                        }
                    }
                }
            }

            if ($type === 'shared') {
                if (($priceMax !== null && $priceMax !== '') && ($this->input('price') === null || $this->input('price') === '')) {
                    $validator->errors()->add('price', 'Starting price is required when maximum price is provided.');
                }

                if (($lotAreaMax !== null && $lotAreaMax !== '') && ($lotArea === null || $lotArea === '')) {
                    $validator->errors()->add('lot_area_sqm', 'Minimum lot area is required when maximum lot area is provided.');
                }

                if (($bedroomsMax !== null && $bedroomsMax !== '') && ($bedrooms === null || $bedrooms === '')) {
                    $validator->errors()->add('bedrooms', 'Minimum bedrooms is required when maximum bedrooms is provided.');
                }

                if (($bathroomsMax !== null && $bathroomsMax !== '') && ($bathrooms === null || $bathrooms === '')) {
                    $validator->errors()->add('bathrooms', 'Minimum bathrooms is required when maximum bathrooms is provided.');
                }

                if (($floorsMax !== null && $floorsMax !== '') && ($floors === null || $floors === '')) {
                    $validator->errors()->add('floors', 'Minimum floors is required when maximum floors is provided.');
                }
            }
        });
    }
}
