<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Listing;
use App\Models\Project;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PublicListingController extends Controller
{
    public function index(Request $request): View
    {
        $featuredListings = $this->featuredListingsQuery()
            ->latest('listings.created_at')
            ->limit(6)
            ->get();

        $savedListingIds = $this->resolveSavedListingIds($request);

        return view('public.home', [
            'featuredListings' => $featuredListings,
            'savedListingIds' => $savedListingIds,
        ]);
    }

    public function properties(Request $request): View
    {
        $filters = $this->validatedSearchFilters($request);

        $query = $this->availableListingsQuery($filters);
        $this->applySort($query, (string) ($filters['sort'] ?? 'latest'));

        $listings = $query->paginate(12)->withQueryString();
        $savedListingIds = $this->resolveSavedListingIds($request);
        $featuredListings = $this->featuredListingsQuery()->limit(3)->get();
        [$brokers, $agents, $projects] = $this->filterSelectOptions();

        return view('public.properties', [
            'listings'        => $listings,
            'savedListingIds' => $savedListingIds,
            'featuredListings' => $featuredListings,
            'brokers'         => $brokers,
            'agents'          => $agents,
            'projects'        => $projects,
            'locationOptions' => $this->locationFilterOptions(),
        ]);
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function mapView(Request $request): View
    {
        $filters = $this->validatedSearchFilters($request, includeBounds: true);
        $geofenceGeoJson = $this->negrosOccidentalGeofenceGeoJson();

        $query = $this->availableListingsQuery($filters);
        if (($filters['bounds_mode'] ?? false) && $this->hasBounds($filters)) {
            $this->applyBounds($query, $filters);
        }
        $this->applySort($query, (string) ($filters['sort'] ?? 'latest'));

        $listings = $query->paginate(15)->withQueryString();
        $savedListingIds = $this->resolveSavedListingIds($request);
        $featuredListings = $this->featuredListingsQuery()->limit(2)->get();
        [$brokers, $agents, $projects] = $this->filterSelectOptions();

        $markerQuery = $this->availableListingsQuery($filters);
        if (($filters['bounds_mode'] ?? false) && $this->hasBounds($filters)) {
            $this->applyBounds($markerQuery, $filters);
        }

        $markers = $markerQuery->get()
            ->map(fn ($l) => [
                'id'            => $l->id,
                'title'         => $l->title,
                'price'         => (float) $l->price,
                'lat'           => is_null($l->lat) ? null : (float) $l->lat,
                'lng'           => is_null($l->lng) ? null : (float) $l->lng,
                'url'           => route('listings.show', $l),
                'thumb'         => $l->images->first()?->image_url ?? asset('images/hero.jpg'),
                'property_type' => $l->property_type,
                'listing_type'  => $l->listing_type,
            ])
            ->filter(fn ($m) => ! is_null($m['lat']) && ! is_null($m['lng']))
            ->values();

        return view('public.map', [
            'listings'        => $listings,
            'markers'         => $markers,
            'savedListingIds' => $savedListingIds,
            'featuredListings' => $featuredListings,
            'geofenceGeoJson' => $geofenceGeoJson,
            'brokers'         => $brokers,
            'agents'          => $agents,
            'projects'        => $projects,
            'locationOptions' => $this->locationFilterOptions(),
        ]);
    }

    public function mapMarkers(Request $request): JsonResponse
    {
        $filters = $this->validatedSearchFilters($request, includeBounds: true);

        $query = $this->availableListingsQuery($filters);

        if ($this->hasBounds($filters)) {
            $this->applyBounds($query, $filters);
        }

        $markers = $query->limit(500)->get()
            ->map(fn ($l) => [
                'id'            => $l->id,
                'title'         => $l->title,
                'price'         => (float) $l->price,
                'lat'           => is_null($l->lat) ? null : (float) $l->lat,
                'lng'           => is_null($l->lng) ? null : (float) $l->lng,
                'url'           => route('listings.show', $l),
                'thumb'         => $l->images->first()?->image_url ?? asset('images/hero.jpg'),
                'property_type' => $l->property_type,
                'listing_type'  => $l->listing_type,
            ])
            ->filter(fn ($m) => ! is_null($m['lat']) && ! is_null($m['lng']))
            ->values();

        return response()->json($markers);
    }

    public function show(Request $request, Listing $listing): View
    {
        abort_if(in_array($listing->status, ['sold', 'unavailable'], true), 404);
        abort_unless(
            Listing::query()->whereKey($listing->id)->insideNegrosOccidental()->exists(),
            404
        );

        if ($listing->status === 'reserved') {
            $user = auth()->user();
            $isStaff = in_array($user?->role, ['admin', 'agent', 'broker'], true);
            $isReservingBuyer = $user?->role === 'buyer'
                && Appointment::query()
                    ->whereHas('lead', function ($query) use ($listing, $user): void {
                        $query->where('listing_id', $listing->id)
                            ->where('buyer_id', $user->id);
                    })
                    ->whereIn('status', ['approved', 'completed'])
                    ->whereNotNull('down_payment_received_at')
                    ->exists();

            abort_unless($isStaff || $isReservingBuyer, 404);
        }

        $listing->load(['images', 'project.broker', 'owner.broker']);

        $spatial = DB::table('listings')
            ->where('id', $listing->id)
            ->selectRaw('ST_Y(location) as lat')
            ->selectRaw('ST_X(location) as lng')
            ->first();

        $lat = ! is_null($spatial?->lat) ? (float) $spatial->lat : null;
        $lng = ! is_null($spatial?->lng) ? (float) $spatial->lng : null;

        $geofence = $this->negrosOccidentalGeofenceGeoJson();

        $savedListingIds = $this->resolveSavedListingIds($request);

        $availableUnits = Listing::query()
            ->availableUnitsForListing($listing)
            ->with(['images', 'project.broker', 'owner.broker'])
            ->latest()
            ->limit(6)
            ->get();

        $structuredDescription = $this->buildStructuredDescription($listing, $lat, $lng);
        $faqItems = $this->buildFaqItems($listing, $availableUnits);

        return view('public.listing-show', [
            'listing' => $listing,
            'lat' => $lat,
            'lng' => $lng,
            'geofenceGeoJson' => $geofence,
            'isSaved' => in_array($listing->id, $savedListingIds, true),
            'savedListingIds' => $savedListingIds,
            'availableUnits' => $availableUnits,
            'structuredDescription' => $structuredDescription,
            'faqItems' => $faqItems,
        ]);
    }

    public function startSiteVisitRequest(Listing $listing): RedirectResponse
    {
        abort_if($listing->status !== 'available', 404);

        return redirect()->to(route('listings.show', $listing).'#request-site-visit');
    }

    private function baseListingsQuery()
    {
        return Listing::query()
            ->publiclyVisible()
            ->with(['images', 'project.broker', 'owner.broker'])
            ->select('listings.*')
            ->selectRaw('ST_Y(location) as lat')
            ->selectRaw('ST_X(location) as lng');
    }

    private function featuredListingsQuery()
    {
        return Listing::query()
            ->featuredPubliclyVisible()
            ->with(['images', 'project.broker', 'owner.broker'])
            ->select('listings.*')
            ->selectRaw('ST_Y(location) as lat')
            ->selectRaw('ST_X(location) as lng');
    }

    private function availableListingsQuery(array $filters): Builder
    {
        $query = $this->baseListingsQuery();

        return $query
            ->when(! empty($filters['keyword']), function (Builder $q) use ($filters): void {
                $kw = '%'.trim((string) $filters['keyword']).'%';
                $q->where(function (Builder $sub) use ($kw): void {
                    $sub->where('title', 'ilike', $kw)->orWhere('description', 'ilike', $kw);
                });
            })
            ->when(! empty($filters['location']), function (Builder $q) use ($filters): void {
                $location = '%'.trim((string) $filters['location']).'%';

                $q->where(function (Builder $sub) use ($location): void {
                    $sub->where('title', 'ilike', $location)
                        ->orWhere('description', 'ilike', $location)
                        ->orWhereHas('project', function (Builder $projectQuery) use ($location): void {
                            $projectQuery
                                ->where('name', 'ilike', $location)
                                ->orWhere('description', 'ilike', $location);
                        })
                        ->orWhereHas('owner', function (Builder $ownerQuery) use ($location): void {
                            $ownerQuery
                                ->where('name', 'ilike', $location)
                                ->orWhereHas('broker', fn (Builder $brokerQuery) => $brokerQuery->where('name', 'ilike', $location));
                        });
                });
            })
            ->when(! empty($filters['property_type']),
                fn (Builder $q) => $q->where('property_type', (string) $filters['property_type']))
            ->when(! empty($filters['listing_type']),
                fn (Builder $q) => $q->where('listing_type', (string) $filters['listing_type']))
            ->when(! empty($filters['status']),
                fn (Builder $q) => $q->where('status', (string) $filters['status']))
            ->when(isset($filters['min_price']),
                fn (Builder $q) => $q->where('price', '>=', (float) $filters['min_price']))
            ->when(isset($filters['max_price']),
                fn (Builder $q) => $q->where('price', '<=', (float) $filters['max_price']))
            ->when(isset($filters['bedrooms']), function (Builder $q) use ($filters): void {
                $bedrooms = (int) $filters['bedrooms'];
                $q->where(function (Builder $sub) use ($bedrooms): void {
                    $sub->where('bedrooms', '>=', $bedrooms)
                        ->orWhere('bedrooms_max', '>=', $bedrooms);
                });
            })
            ->when(isset($filters['bathrooms']), function (Builder $q) use ($filters): void {
                $bathrooms = (int) $filters['bathrooms'];
                $q->where(function (Builder $sub) use ($bathrooms): void {
                    $sub->where('bathrooms', '>=', $bathrooms)
                        ->orWhere('bathrooms_max', '>=', $bathrooms);
                });
            })
            ->when(isset($filters['lot_area_min']), function (Builder $q) use ($filters): void {
                $lotAreaMin = (float) $filters['lot_area_min'];
                $q->whereRaw('COALESCE(lot_area_max_sqm, lot_area_sqm) >= ?', [$lotAreaMin]);
            })
            ->when(isset($filters['lot_area_max']),
                fn (Builder $q) => $q->where('lot_area_sqm', '<=', (float) $filters['lot_area_max']))
            ->when(isset($filters['floor_area_min']),
                fn (Builder $q) => $q->where('floor_area_sqm', '>=', (float) $filters['floor_area_min']))
            ->when(isset($filters['floor_area_max']),
                fn (Builder $q) => $q->where('floor_area_sqm', '<=', (float) $filters['floor_area_max']))
            ->when(! empty($filters['broker_id']), function (Builder $q) use ($filters): void {
                $brokerId = (string) $filters['broker_id'];
                $q->where(function (Builder $sub) use ($brokerId): void {
                    $sub->where(function ($s) use ($brokerId): void {
                        $s->where('listing_type', 'shared')
                          ->whereHas('project', fn ($p) => $p->where('broker_id', $brokerId));
                    })->orWhere(function ($s) use ($brokerId): void {
                        $s->where('listing_type', 'exclusive')
                          ->whereHas('owner', fn ($p) => $p->where('broker_id', $brokerId));
                    });
                });
            })
            ->when(! empty($filters['agent_id']),
                fn (Builder $q) => $q->where('listing_type', 'exclusive')
                    ->where('owner_agent_id', (string) $filters['agent_id']))
            ->when(! empty($filters['project_id']),
                fn (Builder $q) => $q->where('listing_type', 'shared')
                    ->where('project_id', (string) $filters['project_id']));
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_low_high', 'price_low' => $query->orderBy('price'),
            'price_high_low', 'price_high' => $query->orderByDesc('price'),
            default      => $query->latest('listings.created_at'),
        };
    }

    private function filterSelectOptions(): array
    {
        $brokers  = User::where('role', 'broker')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $agents   = User::where('role', 'agent')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $projects = Project::query()
            ->whereHas('broker', fn (Builder $query) => $query->where('role', 'broker')->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        return [$brokers, $agents, $projects];
    }

    private function locationFilterOptions(): array
    {
        return [
            'Bacolod' => 'Bacolod City',
            'Bago' => 'Bago City',
            'Cadiz' => 'Cadiz City',
            'Escalante' => 'Escalante City',
            'Himamaylan' => 'Himamaylan City',
            'Kabankalan' => 'Kabankalan City',
            'La Carlota' => 'La Carlota City',
            'Sagay' => 'Sagay City',
            'San Carlos' => 'San Carlos City',
            'Silay' => 'Silay City',
            'Sipalay' => 'Sipalay City',
            'Talisay' => 'Talisay City',
            'Victorias' => 'Victorias City',
        ];
    }

    private function validatedSearchFilters(Request $request, bool $includeBounds = false): array
    {
        $rules = [
            'keyword' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'property_type' => ['nullable', Rule::in(['lot', 'house_and_lot', 'condo'])],
            'listing_type' => ['nullable', Rule::in(['shared', 'exclusive'])],
            'status' => ['nullable', Rule::in(['available'])],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
            'bedrooms' => ['nullable', 'integer', 'min:1', 'max:20'],
            'bathrooms' => ['nullable', 'integer', 'min:1', 'max:20'],
            'lot_area_min' => ['nullable', 'numeric', 'min:0'],
            'lot_area_max' => ['nullable', 'numeric', 'min:0'],
            'floor_area_min' => ['nullable', 'numeric', 'min:0'],
            'floor_area_max' => ['nullable', 'numeric', 'min:0'],
            'broker_id' => [
                'nullable',
                'uuid',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'broker')->where('is_active', true)),
            ],
            'agent_id' => [
                'nullable',
                'uuid',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'agent')->where('is_active', true)),
            ],
            'project_id' => ['nullable', 'uuid', Rule::exists('projects', 'id')],
            'sort' => ['nullable', Rule::in(['latest', 'price_low_high', 'price_high_low', 'price_low', 'price_high'])],
            'bounds_mode' => ['nullable', 'boolean'],
        ];

        if ($includeBounds) {
            $rules['sw_lat'] = ['nullable', 'numeric', 'between:-90,90'];
            $rules['sw_lng'] = ['nullable', 'numeric', 'between:-180,180'];
            $rules['ne_lat'] = ['nullable', 'numeric', 'between:-90,90'];
            $rules['ne_lng'] = ['nullable', 'numeric', 'between:-180,180'];
        }

        $validated = $request->validate($rules);

        $validated['min_price'] = $validated['min_price'] ?? ($validated['price_min'] ?? null);
        $validated['max_price'] = $validated['max_price'] ?? ($validated['price_max'] ?? null);
        $validated['bounds_mode'] = filter_var($validated['bounds_mode'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $validated;
    }

    private function hasBounds(array $filters): bool
    {
        return isset($filters['sw_lat'], $filters['sw_lng'], $filters['ne_lat'], $filters['ne_lng']);
    }

    private function applyBounds(Builder $query, array $filters): void
    {
        $south = min((float) $filters['sw_lat'], (float) $filters['ne_lat']);
        $north = max((float) $filters['sw_lat'], (float) $filters['ne_lat']);
        $west = min((float) $filters['sw_lng'], (float) $filters['ne_lng']);
        $east = max((float) $filters['sw_lng'], (float) $filters['ne_lng']);

        $query->withBounds($south, $west, $north, $east);
    }

    private function resolveSavedListingIds(Request $request): array
    {
        $user = $request->user();

        if (! $user || ! $user->isBuyer()) {
            return [];
        }

        return Wishlist::query()
            ->where('user_id', $user->id)
            ->pluck('listing_id')
            ->all();
    }

    private function negrosOccidentalGeofenceGeoJson(): ?string
    {
        return DB::table('geofences')
            ->where('name', 'Negros Occidental')
            ->selectRaw('ST_AsGeoJSON(geom) as geojson')
            ->value('geojson');
    }

    private function buildStructuredDescription(Listing $listing, ?float $lat, ?float $lng): array
    {
        $rawDescription = trim((string) $listing->description);
        $propertyTypeLabel = ucwords(str_replace('_', ' ', (string) ($listing->property_type ?? 'Property')));
        $statusLabel = $listing->status === 'unavailable' ? 'Not Available' : ucfirst((string) $listing->status);

        $overview = $rawDescription !== ''
            ? $rawDescription
            : "This {$propertyTypeLabel} listing is currently {$statusLabel}. Contact the poster for full details and viewing options.";

        $highlights = [
            'Listing type: '.ucfirst((string) $listing->listing_type),
            'Status: '.$statusLabel,
            'Price starts at: ₱'.number_format((float) $listing->price, 2),
        ];

        if (! is_null($listing->price_max) && (float) $listing->price_max > (float) $listing->price) {
            $highlights[] = 'Price range up to: ₱'.number_format((float) $listing->price_max, 2);
        }

        if (! is_null($listing->lot_area_sqm)) {
            $highlights[] = 'Lot area: '.number_format((float) $listing->lot_area_sqm, 2).' sqm';
        }

        if (! is_null($listing->floor_area_sqm)) {
            $highlights[] = 'Floor area: '.number_format((float) $listing->floor_area_sqm, 2).' sqm';
        }

        if (! is_null($listing->bedrooms)) {
            $highlights[] = 'Bedrooms: '.(string) $listing->bedrooms;
        }

        if (! is_null($listing->bathrooms)) {
            $highlights[] = 'Bathrooms: '.(string) $listing->bathrooms;
        }

        if ($listing->listing_type === 'shared' && ! is_null($listing->available_units)) {
            $highlights[] = 'Available units: '.(string) $listing->available_units;
        }

        $amenities = $this->extractAmenityHints($rawDescription);

        if ($amenities === []) {
            $amenities = match ($listing->property_type) {
                'condo' => ['Condo amenities may include shared building facilities depending on tower and association rules.'],
                'house_and_lot' => ['Community and subdivision amenities may apply depending on the project or village.'],
                default => ['Utility and access amenities vary by lot/project location.'],
            };
        }

        $locationSummary = ['Coverage area: Negros Occidental, Philippines'];

        if ($listing->listing_type === 'shared') {
            $locationSummary[] = 'Project: '.($listing->project?->name ?? 'Shared project');
            $locationSummary[] = 'Broker: '.($listing->project?->broker?->name ?? 'Unassigned broker');
        } else {
            $locationSummary[] = 'Agent: '.($listing->owner?->name ?? 'Unknown agent');

            if ($listing->owner?->broker) {
                $locationSummary[] = 'Brokerage: '.$listing->owner->broker->name;
            }
        }

        if (! is_null($lat) && ! is_null($lng)) {
            $locationSummary[] = 'Coordinates: '.number_format($lat, 5).', '.number_format($lng, 5);
        }

        return [
            'overview' => $overview,
            'overview_preview' => Str::limit($overview, 320),
            'has_long_overview' => Str::length($overview) > 320,
            'highlights' => array_values(array_unique(array_filter($highlights))),
            'amenities' => array_values(array_unique(array_filter($amenities))),
            'location_summary' => array_values(array_unique(array_filter($locationSummary))),
        ];
    }

    private function buildFaqItems(Listing $listing, Collection $availableUnits): array
    {
        $customFaqItems = $this->extractCustomFaqItems($listing);

        if ($customFaqItems !== []) {
            return $customFaqItems;
        }

        $propertyTypeLabel = ucwords(str_replace('_', ' ', (string) ($listing->property_type ?? 'property')));
        $statusLabel = $listing->status === 'unavailable' ? 'not available' : (string) $listing->status;

        $bedBathAnswer = 'Bedrooms and bathrooms were not specified for this listing.';
        if (! is_null($listing->bedrooms) || ! is_null($listing->bathrooms)) {
            $bedroomText = is_null($listing->bedrooms) ? 'not specified' : (string) $listing->bedrooms;
            $bathroomText = is_null($listing->bathrooms) ? 'not specified' : (string) $listing->bathrooms;
            $bedBathAnswer = "This listing has {$bedroomText} bedroom(s) and {$bathroomText} bathroom(s).";
        }

        $areaParts = [];
        if (! is_null($listing->lot_area_sqm)) {
            $areaParts[] = 'Lot area: '.number_format((float) $listing->lot_area_sqm, 2).' sqm';
        }
        if (! is_null($listing->floor_area_sqm)) {
            $areaParts[] = 'Floor area: '.number_format((float) $listing->floor_area_sqm, 2).' sqm';
        }
        $areaAnswer = $areaParts !== [] ? implode('. ', $areaParts).'.' : 'Lot area and floor area are not specified.';

        if ($listing->listing_type === 'shared') {
            $projectAnswer = 'Yes. This listing belongs to '.($listing->project?->name ?? 'a shared project').'.';
            $posterAnswer = 'Broker: '.($listing->project?->broker?->name ?? 'Unassigned broker').'.';
        } else {
            $projectAnswer = 'No. This is an exclusive listing posted outside project inventory.';
            $posterAnswer = 'Agent: '.($listing->owner?->name ?? 'Unknown agent').'.';

            if ($listing->owner?->broker) {
                $posterAnswer .= ' Brokerage: '.$listing->owner->broker->name.'.';
            }
        }

        $otherUnitsAnswer = $availableUnits->isNotEmpty()
            ? "Yes. There are {$availableUnits->count()} other active unit(s) shown below."
            : 'No other active units are currently available from the same project/poster.';

        return [
            [
                'question' => 'What type of property is this?',
                'answer' => 'This is a '.$propertyTypeLabel.' listed as '.ucfirst((string) $listing->listing_type).'.',
            ],
            [
                'question' => 'Is this listing available?',
                'answer' => 'Current status: '.ucfirst($statusLabel).'.',
            ],
            [
                'question' => 'How many bedrooms and bathrooms does it have?',
                'answer' => $bedBathAnswer,
            ],
            [
                'question' => 'What is the lot area / floor area?',
                'answer' => $areaAnswer,
            ],
            [
                'question' => 'Is this part of a project?',
                'answer' => $projectAnswer,
            ],
            [
                'question' => 'Who is the broker or agent for this property?',
                'answer' => $posterAnswer,
            ],
            [
                'question' => 'Are there other available units from the same project or poster?',
                'answer' => $otherUnitsAnswer,
            ],
        ];
    }

    private function extractCustomFaqItems(Listing $listing): array
    {
        foreach (['faq_data', 'faq_json', 'faq'] as $field) {
            $raw = $listing->getAttribute($field);

            if (empty($raw)) {
                continue;
            }

            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $raw = $decoded;
                }
            }

            if (! is_array($raw)) {
                continue;
            }

            $normalized = [];

            foreach ($raw as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $question = trim((string) ($entry['question'] ?? ''));
                $answer = trim((string) ($entry['answer'] ?? ''));

                if ($question === '' || $answer === '') {
                    continue;
                }

                $normalized[] = [
                    'question' => $question,
                    'answer' => $answer,
                ];
            }

            if ($normalized !== []) {
                return $normalized;
            }
        }

        return [];
    }

    private function extractAmenityHints(string $description): array
    {
        if ($description === '') {
            return [];
        }

        $keywords = ['amenit', 'pool', 'club', 'gym', 'parking', 'security', 'garden', 'playground', 'lobby', 'elevator', 'cctv', 'guard'];

        $segments = preg_split('/[\r\n]+|(?<=[.!?])\s+/', $description) ?: [];

        $amenities = [];
        foreach ($segments as $segment) {
            $line = trim($segment);

            if ($line === '') {
                continue;
            }

            $lower = Str::lower($line);
            foreach ($keywords as $keyword) {
                if (Str::contains($lower, $keyword)) {
                    $amenities[] = $line;
                    break;
                }
            }

            if (count($amenities) >= 4) {
                break;
            }
        }

        return array_values(array_unique($amenities));
    }
}
