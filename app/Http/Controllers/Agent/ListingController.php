<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListingRequest;
use App\Http\Requests\UpdateListingRequest;
use App\Models\Listing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListingController extends Controller
{
    public function index(Request $request): View
    {
        return view('agent.listings.index', [
            'listings' => Listing::query()
                ->where('owner_agent_id', auth()->id())
                ->when($request->filled('property_type'), fn ($query) => $query->where('property_type', $request->string('property_type')))
                ->with('images')
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $geofenceGeoJson = DB::table('geofences')
            ->where('name', 'Negros Occidental')
            ->selectRaw('ST_AsGeoJSON(geom) as geojson')
            ->value('geojson');

        return view('agent.listings.create', [
            'geofenceGeoJson' => $geofenceGeoJson,
        ]);
    }

    public function store(StoreListingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $propertyType = $data['property_type'];
        $price = (float) $data['price'];
        $priceMax = isset($data['price_max']) ? (float) $data['price_max'] : null;
        $lotAreaSqm = isset($data['lot_area_sqm']) ? (float) $data['lot_area_sqm'] : null;
        $lotAreaMaxSqm = isset($data['lot_area_max_sqm']) ? (float) $data['lot_area_max_sqm'] : null;
        $floorAreaSqm = isset($data['floor_area_sqm']) ? (float) $data['floor_area_sqm'] : null;
        $pricePerSqm = isset($data['price_per_sqm']) ? (float) $data['price_per_sqm'] : null;
        $bedrooms = $data['bedrooms'] ?? null;
        $bedroomsMax = $data['bedrooms_max'] ?? null;
        $bathrooms = $data['bathrooms'] ?? null;
        $bathroomsMax = $data['bathrooms_max'] ?? null;
        $floors = $data['floors'] ?? null;
        $floorsMax = $data['floors_max'] ?? null;
        $availableUnits = $data['available_units'] ?? null;
        $requestedStatus = $data['status'] ?? 'available';

        if ($propertyType === 'lot') {
            $bedrooms = null;
            $bedroomsMax = null;
            $bathrooms = null;
            $bathroomsMax = null;
            $floors = null;
            $floorsMax = null;
            $floorAreaSqm = null;
            $price = round(($lotAreaSqm ?? 0.0) * ($pricePerSqm ?? 0.0), 2);
        } elseif ($propertyType === 'house_and_lot') {
            $pricePerSqm = null;
        } else {
            $floors = null;
            $floorsMax = null;
            $lotAreaSqm = null;
            $lotAreaMaxSqm = null;
            $floorAreaSqm = null;
            $pricePerSqm = null;
        }

        // Exclusive postings are single-property records, not project range inventory.
        $priceMax = null;
        $lotAreaMaxSqm = null;
        $bedroomsMax = null;
        $bathroomsMax = null;
        $floorsMax = null;
        $availableUnits = null;

        $status = $this->resolveStatusFromAvailability($requestedStatus, is_null($availableUnits) ? null : (int) $availableUnits);

        DB::transaction(function () use ($request, $data, $propertyType, $price, $priceMax, $lotAreaSqm, $lotAreaMaxSqm, $floorAreaSqm, $pricePerSqm, $bedrooms, $bedroomsMax, $bathrooms, $bathroomsMax, $floors, $floorsMax, $availableUnits, $status): void {
            $listingId = (string) Str::uuid();
            $now = now();

            DB::insert(
                'INSERT INTO listings (id, listing_type, property_type, project_id, owner_agent_id, title, description, price, price_max, lot_area_sqm, lot_area_max_sqm, floor_area_sqm, price_per_sqm, bedrooms, bedrooms_max, bathrooms, bathrooms_max, floors, floors_max, available_units, status, location, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?, ?)',
                [
                    $listingId,
                    'exclusive',
                    $propertyType,
                    null,
                    $request->user()->id,
                    $data['title'],
                    $data['description'] ?? null,
                    $price,
                    $priceMax,
                    $lotAreaSqm,
                    $lotAreaMaxSqm,
                    $floorAreaSqm,
                    $pricePerSqm,
                    $bedrooms,
                    $bedroomsMax,
                    $bathrooms,
                    $bathroomsMax,
                    $floors,
                    $floorsMax,
                    $availableUnits,
                    $status,
                    $request->float('lng'),
                    $request->float('lat'),
                    $now,
                    $now,
                ]
            );

            $listing = Listing::query()->findOrFail($listingId);

            foreach ($request->file('images', []) as $index => $imageFile) {
                if ($imageFile instanceof UploadedFile) {
                    $path = $imageFile->store('listings', 'public');
                    $listing->images()->create([
                        'image_url' => Storage::url($path),
                        'sort_order' => $index + 1,
                    ]);
                }
            }
        });

        return redirect()->route('agent.listings.index')->with('success', 'Exclusive listing created.');
    }

    public function edit(Listing $listing): View
    {
        abort_if($listing->owner_agent_id !== auth()->id(), 403);

        $spatial = DB::table('listings')
            ->where('id', $listing->id)
            ->selectRaw('ST_Y(location) as lat')
            ->selectRaw('ST_X(location) as lng')
            ->first();

        $geofenceGeoJson = DB::table('geofences')
            ->where('name', 'Negros Occidental')
            ->selectRaw('ST_AsGeoJSON(geom) as geojson')
            ->value('geojson');

        return view('agent.listings.edit', [
            'listing' => $listing->load('images'),
            'lat' => $spatial?->lat,
            'lng' => $spatial?->lng,
            'geofenceGeoJson' => $geofenceGeoJson,
        ]);
    }

    public function update(UpdateListingRequest $request, Listing $listing): RedirectResponse
    {
        abort_if($listing->owner_agent_id !== auth()->id(), 403);

        $data = $request->validated();
        $propertyType = $data['property_type'];
        $price = (float) $data['price'];
        $priceMax = isset($data['price_max']) ? (float) $data['price_max'] : null;
        $lotAreaSqm = isset($data['lot_area_sqm']) ? (float) $data['lot_area_sqm'] : null;
        $lotAreaMaxSqm = isset($data['lot_area_max_sqm']) ? (float) $data['lot_area_max_sqm'] : null;
        $floorAreaSqm = isset($data['floor_area_sqm']) ? (float) $data['floor_area_sqm'] : null;
        $pricePerSqm = isset($data['price_per_sqm']) ? (float) $data['price_per_sqm'] : null;
        $bedrooms = $data['bedrooms'] ?? null;
        $bedroomsMax = $data['bedrooms_max'] ?? null;
        $bathrooms = $data['bathrooms'] ?? null;
        $bathroomsMax = $data['bathrooms_max'] ?? null;
        $floors = $data['floors'] ?? null;
        $floorsMax = $data['floors_max'] ?? null;
        $availableUnits = $data['available_units'] ?? null;
        $requestedStatus = $data['status'];

        if ($propertyType === 'lot') {
            $bedrooms = null;
            $bedroomsMax = null;
            $bathrooms = null;
            $bathroomsMax = null;
            $floors = null;
            $floorsMax = null;
            $floorAreaSqm = null;
            $price = round(($lotAreaSqm ?? 0.0) * ($pricePerSqm ?? 0.0), 2);
        } elseif ($propertyType === 'house_and_lot') {
            $pricePerSqm = null;
        } else {
            $floors = null;
            $floorsMax = null;
            $lotAreaSqm = null;
            $lotAreaMaxSqm = null;
            $floorAreaSqm = null;
            $pricePerSqm = null;
        }

        // Exclusive postings are single-property records, not project range inventory.
        $priceMax = null;
        $lotAreaMaxSqm = null;
        $bedroomsMax = null;
        $bathroomsMax = null;
        $floorsMax = null;
        $availableUnits = null;

        $status = $this->resolveStatusFromAvailability($requestedStatus, is_null($availableUnits) ? null : (int) $availableUnits);

        DB::transaction(function () use ($request, $listing, $data, $propertyType, $price, $priceMax, $lotAreaSqm, $lotAreaMaxSqm, $floorAreaSqm, $pricePerSqm, $bedrooms, $bedroomsMax, $bathrooms, $bathroomsMax, $floors, $floorsMax, $availableUnits, $status): void {
            $listing->update([
                'title' => $data['title'],
                'property_type' => $propertyType,
                'description' => $data['description'] ?? null,
                'price' => $price,
                'price_max' => $priceMax,
                'lot_area_sqm' => $lotAreaSqm,
                'lot_area_max_sqm' => $lotAreaMaxSqm,
                'floor_area_sqm' => $floorAreaSqm,
                'price_per_sqm' => $pricePerSqm,
                'bedrooms' => $bedrooms,
                'bedrooms_max' => $bedroomsMax,
                'bathrooms' => $bathrooms,
                'bathrooms_max' => $bathroomsMax,
                'floors' => $floors,
                'floors_max' => $floorsMax,
                'available_units' => $availableUnits,
                'status' => $status,
            ]);

            DB::statement(
                'UPDATE listings SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
                [$request->float('lng'), $request->float('lat'), $listing->id]
            );

            // Delete images marked for removal
            $deleteIds = array_filter((array) $request->input('delete_images', []), 'is_numeric');
            if (! empty($deleteIds)) {
                $toDelete = $listing->images()->whereIn('id', $deleteIds)->get();
                foreach ($toDelete as $img) {
                    if (str_starts_with($img->image_url, '/storage/')) {
                        Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $img->image_url), '/'));
                    }
                }
                $listing->images()->whereIn('id', $deleteIds)->delete();
            }

            // Replace individual images keyed by image ID
            foreach ($request->file('replace_images', []) as $imageId => $imageFile) {
                if (! is_numeric($imageId) || ! $imageFile instanceof UploadedFile) {
                    continue;
                }
                $imageRecord = $listing->images()->find((int) $imageId);
                if ($imageRecord === null) {
                    continue;
                }
                if (str_starts_with($imageRecord->image_url, '/storage/')) {
                    Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $imageRecord->image_url), '/'));
                }
                $path = $imageFile->store('listings', 'public');
                $imageRecord->update(['image_url' => Storage::url($path)]);
            }

            // Add new images
            if ($request->hasFile('images')) {
                $nextSortOrder = ($listing->images()->max('sort_order') ?? 0) + 1;
                foreach ($request->file('images', []) as $index => $imageFile) {
                    if ($imageFile instanceof UploadedFile) {
                        $path = $imageFile->store('listings', 'public');
                        $listing->images()->create([
                            'image_url' => Storage::url($path),
                            'sort_order' => $nextSortOrder + $index,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('agent.listings.index')->with('success', 'Listing updated.');
    }

    public function destroy(Listing $listing): RedirectResponse
    {
        abort_if($listing->owner_agent_id !== auth()->id(), 403);

        $listing->delete();

        return back()->with('success', 'Listing removed.');
    }

    private function resolveStatusFromAvailability(string $requestedStatus, ?int $availableUnits): string
    {
        if (! is_null($availableUnits) && $availableUnits <= 0) {
            return 'unavailable';
        }

        return $requestedStatus;
    }
}
