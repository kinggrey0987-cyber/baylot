<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListingRequest;
use App\Http\Requests\UpdateListingRequest;
use App\Models\Listing;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SharedListingController extends Controller
{
    public function index(Request $request): View
    {
        $broker = $request->user();

        $selectedProjectId = $request->filled('project_id')
            ? $request->string('project_id')->toString()
            : null;

        $geofenceGeoJson = DB::table('geofences')
            ->where('name', 'Negros Occidental')
            ->selectRaw('ST_AsGeoJSON(geom) AS geojson')
            ->value('geojson');

        return view('admin.shared-listings.index', [
            'listings' => Listing::query()
                ->sharedOwnedByBroker($broker->id)
                ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $selectedProjectId))
                ->when($request->filled('property_type'), fn ($query) => $query->where('property_type', $request->string('property_type')))
                ->with('project')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'projects' => Project::query()
                ->ownedByBroker($broker->id)
                ->orderBy('name')
                ->get(),
            'selectedProjectId' => $selectedProjectId,
            'geofenceGeoJson' => $geofenceGeoJson,
        ]);
    }

    public function store(StoreListingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Project::query()
            ->ownedByBroker($request->user()->id)
            ->findOrFail($data['project_id']);

        $data['listing_type'] = 'shared';
        $data['owner_agent_id'] = null;
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
            $priceMax = is_null($lotAreaMaxSqm)
                ? null
                : round($lotAreaMaxSqm * ($pricePerSqm ?? 0.0), 2);
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

        $status = $this->resolveStatusFromAvailability($requestedStatus, is_null($availableUnits) ? null : (int) $availableUnits);

        DB::transaction(function () use ($data, $request, $propertyType, $price, $priceMax, $lotAreaSqm, $lotAreaMaxSqm, $floorAreaSqm, $pricePerSqm, $bedrooms, $bedroomsMax, $bathrooms, $bathroomsMax, $floors, $floorsMax, $availableUnits, $status): void {
            $listingId = (string) Str::uuid();
            $now = now();

            DB::insert(
                'INSERT INTO listings (id, listing_type, property_type, project_id, owner_agent_id, title, description, price, price_max, lot_area_sqm, lot_area_max_sqm, floor_area_sqm, price_per_sqm, bedrooms, bedrooms_max, bathrooms, bathrooms_max, floors, floors_max, available_units, status, location, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?, ?)',
                [
                    $listingId,
                    'shared',
                    $propertyType,
                    $data['project_id'],
                    null,
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

        return redirect()
            ->route('broker.shared-listings.index', ['project_id' => $data['project_id']])
            ->with('success', 'Shared listing created.');
    }

    public function edit(Listing $listing): View
    {
        abort_if($listing->listing_type !== 'shared', 404);
        abort_if($listing->project?->broker_id !== auth()->id(), 403);

        $spatial = DB::table('listings')
            ->where('id', $listing->id)
            ->selectRaw('ST_Y(location) AS lat')
            ->selectRaw('ST_X(location) AS lng')
            ->first();

        $geofenceGeoJson = DB::table('geofences')
            ->where('name', 'Negros Occidental')
            ->selectRaw('ST_AsGeoJSON(geom) AS geojson')
            ->value('geojson');

        return view('admin.shared-listings.edit', [
            'listing' => $listing->load('images', 'project'),
            'projects' => Project::query()->ownedByBroker(auth()->id())->orderBy('name')->get(),
            'lat' => $spatial?->lat,
            'lng' => $spatial?->lng,
            'geofenceGeoJson' => $geofenceGeoJson,
        ]);
    }

    public function update(UpdateListingRequest $request, Listing $listing): RedirectResponse
    {
        abort_if($listing->listing_type !== 'shared', 404);
        abort_if($listing->project?->broker_id !== auth()->id(), 403);

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
            $priceMax = is_null($lotAreaMaxSqm)
                ? null
                : round($lotAreaMaxSqm * ($pricePerSqm ?? 0.0), 2);
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

            $deleteIds = $data['delete_images'] ?? [];
            if (! empty($deleteIds)) {
                $toDelete = $listing->images()->whereIn('id', $deleteIds)->get();
                foreach ($toDelete as $img) {
                    if (str_starts_with($img->image_url, '/storage/')) {
                        Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $img->image_url), '/'));
                    }
                }
                $listing->images()->whereIn('id', $deleteIds)->delete();
            }

            foreach ($request->file('replace_images', []) as $imageId => $imageFile) {
                if (! is_string($imageId) || ! Str::isUuid($imageId) || ! $imageFile instanceof UploadedFile) {
                    continue;
                }
                $imageRecord = $listing->images()->find($imageId);
                if ($imageRecord === null) {
                    continue;
                }
                if (str_starts_with($imageRecord->image_url, '/storage/')) {
                    Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $imageRecord->image_url), '/'));
                }
                $path = $imageFile->store('listings', 'public');
                $imageRecord->update(['image_url' => Storage::url($path)]);
            }

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

        return back()->with('success', 'Shared listing updated.');
    }

    public function destroy(Listing $listing): RedirectResponse
    {
        abort_if($listing->listing_type !== 'shared', 404);
        abort_if($listing->project?->broker_id !== auth()->id(), 403);

        $listing->delete();

        return back()->with('success', 'Shared listing deleted.');
    }

    private function resolveStatusFromAvailability(string $requestedStatus, ?int $availableUnits): string
    {
        if (! is_null($availableUnits) && $availableUnits <= 0) {
            return 'unavailable';
        }

        return $requestedStatus;
    }
}
