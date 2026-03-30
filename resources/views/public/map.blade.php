<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'BayLot') }} - Map Search</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .map-page-layout {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 4.5rem);
            margin-top: 4.5rem;
            overflow: hidden;
        }
        .map-filter-bar {
            flex-shrink: 0;
            background: #fff;
            border-bottom: 1px solid rgba(0,0,0,.1);
            z-index: 100;
        }
        .map-content-area {
            flex: 1;
            display: flex;
            min-height: 0;
            overflow: hidden;
        }
        .map-panel {
            flex: 0 0 60%;
            min-width: 0;
            position: relative;
        }
        #browseMap {
            width: 100%;
            height: 100%;
        }
        .listings-scroll-panel {
            flex: 0 0 40%;
            min-width: 0;
            overflow-y: auto;
            border-left: 1px solid rgba(0,0,0,.1);
            background: #f8f9fa;
        }
        .listings-scroll-panel .listing-map-card img {
            height: 140px;
            object-fit: cover;
        }
        @media (max-width: 991.98px) {
            .map-page-layout {
                height: auto;
                min-height: calc(100vh - 4.5rem);
                overflow: visible;
            }
            .map-content-area {
                flex-direction: column;
                height: auto;
                overflow: visible;
            }
            .map-panel {
                flex: none;
                height: 55vw;
                min-height: 280px;
                max-height: 400px;
            }
            .listings-scroll-panel {
                flex: none;
                height: auto;
                overflow: visible;
                border-left: none;
                border-top: 1px solid rgba(0,0,0,.1);
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg fixed-top baylot-navbar" aria-label="Main navigation">
            <div class="container-fluid px-3">
                <a class="navbar-brand" href="{{ route('home') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="BayLot" class="brand-logo">
                </a>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <a href="{{ route('properties.index', request()->query()) }}" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-list-ul"></i> List View
                    </a>
                    @auth
                        <a class="btn btn-sm btn-light" href="{{ route('dashboard') }}">Dashboard</a>
                    @else
                        <a class="btn btn-sm btn-outline-light" href="{{ route('login') }}">Login</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <div class="map-page-layout">
        {{-- Filter Bar --}}
        <div class="map-filter-bar">
            @php
                $selectedType = request()->string('property_type')->toString();
                $advancedActive = request()->hasAny([
                    'listing_type','bedrooms','bathrooms',
                    'lot_area_min','lot_area_max','floor_area_min','floor_area_max',
                    'broker_id','agent_id','project_id','bounds_mode',
                ]);
                $hasActiveFilters = request()->hasAny([
                    'property_type','keyword','location','status','listing_type','min_price','max_price',
                    'bedrooms','bathrooms','lot_area_min','lot_area_max',
                    'floor_area_min','floor_area_max','broker_id','agent_id','project_id','bounds_mode',
                ]);
                $propertyTypes = ['' => 'All', 'lot' => 'Lot', 'house_and_lot' => 'House & Lot', 'condo' => 'Condo'];
            @endphp

            <div class="p-2 border-bottom">
                {{-- Category Pills --}}
                <div class="d-flex gap-1 flex-wrap align-items-center">
                    @php
                        $baseQ = request()->only([
                            'keyword','location','status','listing_type','min_price','max_price','sort',
                            'bedrooms','bathrooms','lot_area_min','lot_area_max',
                            'floor_area_min','floor_area_max','broker_id','agent_id','project_id','bounds_mode',
                        ]);
                    @endphp
                    @foreach($propertyTypes as $value => $label)
                        @php $q = $baseQ; if ($value !== '') $q['property_type'] = $value; @endphp
                        <a href="{{ route('properties.map', $q) }}"
                           class="btn btn-xs {{ $selectedType === $value ? 'btn-primary' : 'btn-outline-secondary' }} btn-sm py-0 px-2">
                            {{ $label }}
                        </a>
                    @endforeach

                    <div class="vr mx-1 d-none d-md-block"></div>

                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 ms-auto ms-md-0"
                        data-bs-toggle="collapse" data-bs-target="#mapFilterForm"
                        aria-expanded="{{ ($hasActiveFilters) ? 'true' : 'false' }}" aria-controls="mapFilterForm">
                        <i class="bi bi-funnel{{ $hasActiveFilters ? '-fill text-primary' : '' }}"></i>
                        Filters{{ $hasActiveFilters ? ' ●' : '' }}
                    </button>
                    @if($hasActiveFilters)
                        <a href="{{ route('properties.map') }}" class="btn btn-sm btn-outline-danger py-0 px-2">
                            <i class="bi bi-x"></i> Clear
                        </a>
                    @endif
                </div>
            </div>

            <div id="mapFilterForm" class="collapse {{ $hasActiveFilters ? 'show' : '' }} border-bottom">
                <form method="GET" action="{{ route('properties.map') }}" class="row g-2 align-items-end p-2">
                    <input type="hidden" name="property_type" value="{{ $selectedType }}">
                    <input type="hidden" id="bounds_sw_lat" name="sw_lat" value="{{ request('sw_lat') }}">
                    <input type="hidden" id="bounds_sw_lng" name="sw_lng" value="{{ request('sw_lng') }}">
                    <input type="hidden" id="bounds_ne_lat" name="ne_lat" value="{{ request('ne_lat') }}">
                    <input type="hidden" id="bounds_ne_lng" name="ne_lng" value="{{ request('ne_lng') }}">

                    <div class="col-12 col-md-2">
                        <input name="keyword" class="form-control form-control-sm" value="{{ request('keyword') }}" placeholder="Keyword (title / description)">
                    </div>
                    <div class="col-12 col-md-2">
                        <select name="location" class="form-select form-select-sm" title="Location">
                            <option value="">All Cities</option>
                            @foreach(($locationOptions ?? []) as $locationValue => $locationLabel)
                                <option value="{{ $locationValue }}" {{ request('location') === $locationValue ? 'selected' : '' }}>
                                    {{ $locationLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <input name="min_price" class="form-control form-control-sm" type="number" step="1" min="0" value="{{ request('min_price', request('price_min')) }}" placeholder="Min Price (₱)">
                    </div>
                    <div class="col-6 col-md-2">
                        <input name="max_price" class="form-control form-control-sm" type="number" step="1" min="0" value="{{ request('max_price', request('price_max')) }}" placeholder="Max Price (₱)">
                    </div>
                    <div class="col-6 col-md-1">
                        <select name="listing_type" class="form-select form-select-sm" title="Listing type">
                            <option value="">Type</option>
                            <option value="shared" {{ request('listing_type') === 'shared' ? 'selected' : '' }}>Shared</option>
                            <option value="exclusive" {{ request('listing_type') === 'exclusive' ? 'selected' : '' }}>Exclusive</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-1">
                        <select name="sort" class="form-select form-select-sm" title="Sort">
                            <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Latest</option>
                            <option value="price_low_high" {{ in_array(request('sort'), ['price_low_high', 'price_low'], true) ? 'selected' : '' }}>Price ↑</option>
                            <option value="price_high_low" {{ in_array(request('sort'), ['price_high_low', 'price_high'], true) ? 'selected' : '' }}>Price ↓</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-1">
                        <select name="status" class="form-select form-select-sm" title="Status">
                            <option value="">Status</option>
                            <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Available</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-1">
                        <select name="bedrooms" class="form-select form-select-sm" title="Bedrooms">
                            <option value="">Beds</option>
                            @foreach([1,2,3,4,5] as $n)
                                <option value="{{ $n }}" {{ (int)request('bedrooms') === $n ? 'selected' : '' }}>{{ $n }}+BR</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-1">
                        <select name="bathrooms" class="form-select form-select-sm" title="Bathrooms">
                            <option value="">Baths</option>
                            @foreach([1,2,3,4] as $n)
                                <option value="{{ $n }}" {{ (int)request('bathrooms') === $n ? 'selected' : '' }}>{{ $n }}+BA</option>
                            @endforeach
                        </select>
                    </div>
                    @if(isset($brokers) && $brokers->count() > 0)
                        <div class="col-12 col-md-2">
                            <select name="broker_id" class="form-select form-select-sm">
                                <option value="">All Brokers</option>
                                @foreach($brokers as $brokerOption)
                                    <option value="{{ $brokerOption->id }}" {{ request('broker_id') === $brokerOption->id ? 'selected' : '' }}>{{ $brokerOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if(isset($agents) && $agents->count() > 0)
                        <div class="col-12 col-md-2">
                            <select name="agent_id" class="form-select form-select-sm">
                                <option value="">All Agents</option>
                                @foreach($agents as $agentOption)
                                    <option value="{{ $agentOption->id }}" {{ request('agent_id') === $agentOption->id ? 'selected' : '' }}>{{ $agentOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if(isset($projects) && $projects->count() > 0)
                        <div class="col-12 col-md-2">
                            <select name="project_id" class="form-select form-select-sm">
                                <option value="">All Projects</option>
                                @foreach($projects as $projectOption)
                                    <option value="{{ $projectOption->id }}" {{ request('project_id') === $projectOption->id ? 'selected' : '' }}>{{ $projectOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-auto">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="bounds_mode" name="bounds_mode" value="1" {{ request()->boolean('bounds_mode') ? 'checked' : '' }}>
                            <label class="form-check-label small" for="bounds_mode">Use map bounds</label>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Map + Listings split --}}
        <div class="map-content-area">
            {{-- Map --}}
            <div class="map-panel">
                <div id="browseMap"></div>
                <div class="position-absolute bottom-0 start-0 m-2 z-3" style="pointer-events:none">
                    <span class="badge text-bg-dark bg-opacity-75">{{ $listings->total() }} listing(s) match</span>
                </div>
            </div>

            {{-- Listing cards --}}
            <div class="listings-scroll-panel p-2">
                @if(isset($featuredListings) && $featuredListings->isNotEmpty())
                    <div class="card border-0 shadow-sm mb-2">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <span class="small fw-semibold">Featured in Marketplace</span>
                            <span class="badge text-bg-warning text-dark"><i class="bi bi-star-fill"></i> {{ $featuredListings->count() }}</span>
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-2">
                                @foreach($featuredListings as $featured)
                                    @php $featuredSaved = in_array($featured->id, $savedListingIds ?? [], true); @endphp
                                    <div class="col-12">
                                        <div class="d-flex gap-2 border rounded p-2">
                                            <img src="{{ $featured->images->first()?->image_url ?? asset('images/hero.jpg') }}" alt="{{ $featured->title }}" style="width:72px;height:60px;object-fit:cover;border-radius:4px;">
                                            <div class="flex-grow-1 min-width-0">
                                                <p class="small fw-semibold mb-0 text-truncate">{{ $featured->title }}</p>
                                                <p class="small text-primary fw-semibold mb-1">₱{{ number_format((float) $featured->price, 0) }}</p>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('listings.show', $featured) }}" class="btn btn-outline-primary btn-sm py-0 px-2">View</a>
                                                    @auth
                                                        @if(auth()->user()->role === 'buyer')
                                                            @if($featuredSaved)
                                                                <form method="POST" action="{{ route('buyer.wishlist.destroy', $featured) }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button class="btn btn-outline-danger btn-sm py-0 px-2" type="submit" title="Remove from wishlist"><i class="bi bi-heart-fill"></i></button>
                                                                </form>
                                                            @else
                                                                <form method="POST" action="{{ route('buyer.wishlist.store', $featured) }}">
                                                                    @csrf
                                                                    <button class="btn btn-outline-secondary btn-sm py-0 px-2" type="submit" title="Add to wishlist"><i class="bi bi-heart"></i></button>
                                                                </form>
                                                            @endif
                                                        @endif
                                                    @endauth
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @forelse($listings as $listing)
                    @php $isSaved = in_array($listing->id, $savedListingIds ?? [], true); @endphp
                    <div class="card listing-map-card border-0 shadow-sm mb-2">
                        <div class="row g-0">
                            <div class="col-4">
                                <img src="{{ $listing->images->first()?->image_url ?? asset('images/hero.jpg') }}"
                                     class="img-fluid rounded-start h-100" alt="{{ $listing->title }}"
                                     style="height:100%;object-fit:cover;">
                            </div>
                            <div class="col-8">
                                <div class="card-body p-2">
                                    <div class="d-flex gap-1 mb-1">
                                        <span class="badge text-bg-info" style="font-size:.65rem">{{ ucwords(str_replace('_',' ',$listing->property_type ?? 'lot')) }}</span>
                                        <span class="badge text-bg-secondary" style="font-size:.65rem">{{ ucfirst($listing->listing_type) }}</span>
                                    </div>
                                    <h6 class="mb-1 small fw-semibold" style="line-height:1.3">{{ $listing->title }}</h6>
                                    <p class="fw-bold text-primary mb-1 small">₱{{ number_format($listing->price, 0) }}</p>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('listings.show', $listing) }}" class="btn btn-outline-primary btn-sm flex-grow-1" style="font-size:.75rem;padding:2px 6px">Details</a>
                                        @auth
                                            @if(auth()->user()->role === 'buyer')
                                                @if($isSaved)
                                                    <form method="POST" action="{{ route('buyer.wishlist.destroy', $listing) }}">
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-outline-danger btn-sm" type="submit" style="padding:2px 6px" title="Remove from wishlist">
                                                            <i class="bi bi-heart-fill"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('buyer.wishlist.store', $listing) }}">
                                                        @csrf
                                                        <button class="btn btn-outline-secondary btn-sm" type="submit" style="padding:2px 6px" title="Add to wishlist">
                                                            <i class="bi bi-heart"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-light border text-center small my-2">No listings match the current filters.</div>
                @endforelse

                @if($listings->hasPages())
                    <div class="mt-2 d-flex justify-content-center">
                        {{ $listings->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('partials.site-footer')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const markers = @json($markers);
            const mapDataUrl = '{{ route('properties.map-data') }}';
            const map = window.BayLotMap.initBrowseMap('browseMap', markers, mapDataUrl, @json($geofenceGeoJson ?? null));

            const swLatInput = document.getElementById('bounds_sw_lat');
            const swLngInput = document.getElementById('bounds_sw_lng');
            const neLatInput = document.getElementById('bounds_ne_lat');
            const neLngInput = document.getElementById('bounds_ne_lng');

            const syncBoundsInputs = () => {
                if (!map || !swLatInput || !swLngInput || !neLatInput || !neLngInput) {
                    return;
                }

                const bounds = map.getBounds();
                swLatInput.value = bounds.getSouth().toFixed(6);
                swLngInput.value = bounds.getWest().toFixed(6);
                neLatInput.value = bounds.getNorth().toFixed(6);
                neLngInput.value = bounds.getEast().toFixed(6);
            };

            if (map) {
                syncBoundsInputs();
                map.on('moveend', syncBoundsInputs);
                map.on('zoomend', syncBoundsInputs);
            }
        });
    </script>
</body>
</html>
