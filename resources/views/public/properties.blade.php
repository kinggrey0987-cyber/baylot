<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'BayLot') }} - Properties</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg sticky-top baylot-navbar" aria-label="Main navigation">
            <div class="container">
                <a class="navbar-brand" href="{{ route('home') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="BayLot" class="brand-logo">
                </a>
                <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#propertiesNav" aria-controls="propertiesNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="propertiesNav">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                        <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                        <li class="nav-item"><a class="nav-link active" href="{{ route('properties.index') }}">Properties</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('about') }}">About</a></li>
                        @auth
                            <li class="nav-item ms-lg-2">
                                <a class="btn btn-sm btn-light" href="{{ route('dashboard') }}">Dashboard</a>
                            </li>
                        @else
                            <li class="nav-item ms-lg-2">
                                <a class="btn btn-sm btn-outline-light" href="{{ route('login') }}">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="btn btn-sm btn-warning" href="{{ route('register') }}">Register</a>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="py-5 bg-light">
        <div class="container">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">Available Properties</h1>
                    <p class="text-muted mb-0">Browse posted listings and refine by category and budget.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('properties.map', request()->query()) }}" class="btn btn-outline-primary">
                        <i class="bi bi-map"></i> Map View
                    </a>
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary">Back to Home</a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success shadow-sm border-0" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @php
                $selectedType = request()->string('property_type')->toString();
                $baseFilter = request()->only([
                    'keyword','location','status','listing_type','min_price','max_price','sort',
                    'bedrooms','bathrooms','lot_area_min','lot_area_max',
                    'floor_area_min','floor_area_max','broker_id','agent_id','project_id',
                ]);
                $hasActiveFilters = request()->hasAny([
                    'property_type','keyword','location','status','listing_type','min_price','max_price',
                    'bedrooms','bathrooms','lot_area_min','lot_area_max',
                    'floor_area_min','floor_area_max','broker_id','agent_id','project_id',
                ]);
                $propertyTypes = [
                    '' => 'All',
                    'lot' => 'Lot',
                    'house_and_lot' => 'House and Lot',
                    'condo' => 'Condo',
                ];
            @endphp

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center d-lg-none">
                    <span class="fw-semibold">Filters</span>
                    <button
                        class="btn btn-sm btn-outline-secondary"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#propertiesFilterCollapse"
                        aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}"
                        aria-controls="propertiesFilterCollapse"
                    >
                        {{ $hasActiveFilters ? 'Hide Filters' : 'Show Filters' }}
                    </button>
                </div>

                <div id="propertiesFilterCollapse" class="collapse d-lg-block {{ $hasActiveFilters ? 'show' : '' }}">
                    <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label mb-2">Category</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($propertyTypes as $value => $label)
                                @php
                                    $query = $baseFilter;
                                    if ($value !== '') {
                                        $query['property_type'] = $value;
                                    }
                                @endphp
                                <a
                                    href="{{ route('properties.index', $query) }}"
                                    class="btn {{ $selectedType === $value ? 'btn-primary' : 'btn-outline-secondary' }} btn-sm"
                                >
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <form method="GET" action="{{ route('properties.index') }}" class="row g-2 align-items-end">
                        <input type="hidden" name="property_type" value="{{ $selectedType }}">

                        <div class="col-12 col-md-3">
                            <label class="form-label" for="keyword">Keyword</label>
                            <input id="keyword" name="keyword" class="form-control" value="{{ request('keyword') }}" placeholder="Title or description">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="location">Location</label>
                            <select id="location" name="location" class="form-select">
                                <option value="">All Cities</option>
                                @foreach(($locationOptions ?? []) as $locationValue => $locationLabel)
                                    <option value="{{ $locationValue }}" {{ request('location') === $locationValue ? 'selected' : '' }}>
                                        {{ $locationLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label" for="min_price">Min Price (₱)</label>
                            <input id="min_price" name="min_price" class="form-control" type="number" step="1" min="0" value="{{ request('min_price', request('price_min')) }}" placeholder="0">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label" for="max_price">Max Price (₱)</label>
                            <input id="max_price" name="max_price" class="form-control" type="number" step="1" min="0" value="{{ request('max_price', request('price_max')) }}" placeholder="5000000">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label" for="sort">Sort By</label>
                            <select id="sort" name="sort" class="form-select">
                                <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Latest</option>
                                <option value="price_low_high" {{ in_array(request('sort'), ['price_low_high', 'price_low'], true) ? 'selected' : '' }}>Price: Low → High</option>
                                <option value="price_high_low" {{ in_array(request('sort'), ['price_high_low', 'price_high'], true) ? 'selected' : '' }}>Price: High → Low</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">All Public</option>
                                <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Available</option>
                            </select>
                        </div>

                        <div class="col-12 mt-1">
                            @php
                                $advancedActive = request()->hasAny([
                                    'listing_type','bedrooms','bathrooms',
                                    'lot_area_min','lot_area_max','floor_area_min','floor_area_max',
                                    'broker_id','agent_id','project_id',
                                ]);
                            @endphp
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none"
                                data-bs-toggle="collapse" data-bs-target="#advancedFilters"
                                aria-expanded="{{ $advancedActive ? 'true' : 'false' }}"
                                aria-controls="advancedFilters">
                                <i class="bi bi-sliders"></i> Advanced Filters
                                @if($advancedActive)<span class="badge text-bg-primary ms-1">active</span>@endif
                            </button>
                        </div>

                        <div id="advancedFilters" class="col-12 collapse {{ $advancedActive ? 'show' : '' }}">
                            <div class="row g-2">
                                <div class="col-6 col-md-2">
                                    <label class="form-label" for="listing_type">Listing Type</label>
                                    <select id="listing_type" name="listing_type" class="form-select">
                                        <option value="">All Types</option>
                                        <option value="shared" {{ request('listing_type') === 'shared' ? 'selected' : '' }}>Project / Shared</option>
                                        <option value="exclusive" {{ request('listing_type') === 'exclusive' ? 'selected' : '' }}>Exclusive</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label" for="bedrooms">Beds (min)</label>
                                    <select id="bedrooms" name="bedrooms" class="form-select">
                                        <option value="">Any</option>
                                        @foreach([1,2,3,4,5] as $n)
                                            <option value="{{ $n }}" {{ (int) request('bedrooms') === $n ? 'selected' : '' }}>{{ $n }}+</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label" for="bathrooms">Baths (min)</label>
                                    <select id="bathrooms" name="bathrooms" class="form-select">
                                        <option value="">Any</option>
                                        @foreach([1,2,3,4] as $n)
                                            <option value="{{ $n }}" {{ (int) request('bathrooms') === $n ? 'selected' : '' }}>{{ $n }}+</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Lot Area (sqm)</label>
                                    <div class="input-group">
                                        <input name="lot_area_min" class="form-control" type="number" min="0" step="1" value="{{ request('lot_area_min') }}" placeholder="Min">
                                        <span class="input-group-text px-2">–</span>
                                        <input name="lot_area_max" class="form-control" type="number" min="0" step="1" value="{{ request('lot_area_max') }}" placeholder="Max">
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Floor Area (sqm)</label>
                                    <div class="input-group">
                                        <input name="floor_area_min" class="form-control" type="number" min="0" step="1" value="{{ request('floor_area_min') }}" placeholder="Min">
                                        <span class="input-group-text px-2">–</span>
                                        <input name="floor_area_max" class="form-control" type="number" min="0" step="1" value="{{ request('floor_area_max') }}" placeholder="Max">
                                    </div>
                                </div>
                                @if(isset($brokers) && $brokers->count() > 0)
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="broker_id">Broker</label>
                                        <select id="broker_id" name="broker_id" class="form-select">
                                            <option value="">All Brokers</option>
                                            @foreach($brokers as $brokerOption)
                                                <option value="{{ $brokerOption->id }}" {{ request('broker_id') === $brokerOption->id ? 'selected' : '' }}>{{ $brokerOption->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                @if(isset($agents) && $agents->count() > 0)
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="agent_id">Agent</label>
                                        <select id="agent_id" name="agent_id" class="form-select">
                                            <option value="">All Agents</option>
                                            @foreach($agents as $agentOption)
                                                <option value="{{ $agentOption->id }}" {{ request('agent_id') === $agentOption->id ? 'selected' : '' }}>{{ $agentOption->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                @if(isset($projects) && $projects->count() > 0)
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="project_id">Project</label>
                                        <select id="project_id" name="project_id" class="form-select">
                                            <option value="">All Projects</option>
                                            @foreach($projects as $projectOption)
                                                <option value="{{ $projectOption->id }}" {{ request('project_id') === $projectOption->id ? 'selected' : '' }}>{{ $projectOption->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-12 d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary">Clear All</a>
                        </div>
                    </form>
                </div>
                </div>
            </div>

            @if(isset($featuredListings) && $featuredListings->isNotEmpty())
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Featured Properties</h2>
                        <span class="badge text-bg-warning text-dark"><i class="bi bi-star-fill"></i> Marketplace Picks</span>
                    </div>
                    <div class="row g-3">
                        @foreach($featuredListings as $featured)
                            @php
                                $featuredSaved = in_array($featured->id, $savedListingIds ?? [], true);
                            @endphp
                            <div class="col-12 col-md-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <img src="{{ $featured->images->first()?->image_url ?? asset('images/hero.jpg') }}" class="card-img-top" alt="{{ $featured->title }}" style="height:170px;object-fit:cover;">
                                    <div class="card-body">
                                        <div class="d-flex gap-1 flex-wrap mb-2">
                                            <span class="badge text-bg-warning text-dark">Featured</span>
                                            <span class="badge text-bg-info">{{ ucwords(str_replace('_', ' ', $featured->property_type ?? 'lot')) }}</span>
                                            <span class="badge text-bg-secondary">{{ ucfirst($featured->listing_type) }}</span>
                                        </div>
                                        <h3 class="h6 mb-2">{{ $featured->title }}</h3>
                                        <p class="fw-bold text-primary mb-0">₱{{ number_format((float) $featured->price, 2) }}</p>
                                    </div>
                                    <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 d-flex gap-2">
                                        <a href="{{ route('listings.show', $featured) }}" class="btn btn-outline-primary btn-sm flex-grow-1">View</a>
                                        @auth
                                            @if(auth()->user()->role === 'buyer')
                                                @if($featuredSaved)
                                                    <form method="POST" action="{{ route('buyer.wishlist.destroy', $featured) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Remove from wishlist" title="Remove from wishlist">
                                                            <i class="bi bi-heart-fill"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('buyer.wishlist.store', $featured) }}">
                                                        @csrf
                                                        <button class="btn btn-outline-secondary btn-sm" type="submit" aria-label="Add to wishlist" title="Add to wishlist">
                                                            <i class="bi bi-heart"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div id="propertiesResults" class="row g-4">
                @forelse($listings as $listing)
                    @php
                        $isSaved = in_array($listing->id, $savedListingIds ?? [], true);
                    @endphp
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <img src="{{ $listing->images->first()?->image_url ?? asset('images/hero.jpg') }}" class="card-img-top" alt="{{ $listing->title }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="d-flex gap-1 flex-wrap">
                                        <span class="badge text-bg-info">{{ ucwords(str_replace('_', ' ', $listing->property_type ?? 'lot')) }}</span>
                                        <span class="badge text-bg-secondary">{{ ucfirst($listing->listing_type) }}</span>
                                    </div>
                                    <span class="text-muted small">{{ ucfirst($listing->status) }}</span>
                                </div>
                                <h2 class="h5">{{ $listing->title }}</h2>
                                <p class="text-muted text-truncate">{{ $listing->description }}</p>

                                @php
                                    $cardDetails = [];
                                    $isProjectPosting = ($listing->listing_type ?? null) === 'shared';

                                    if (! is_null($listing->lot_area_sqm)) {
                                        if ($isProjectPosting && ! is_null($listing->lot_area_max_sqm)) {
                                            $cardDetails[] = number_format((float) $listing->lot_area_sqm, 2).'-'.number_format((float) $listing->lot_area_max_sqm, 2).' sqm lot';
                                        } else {
                                            $cardDetails[] = number_format((float) $listing->lot_area_sqm, 2).' sqm lot';
                                        }
                                    }

                                    if (($listing->property_type ?? 'lot') === 'house_and_lot' && ! is_null($listing->floor_area_sqm)) {
                                        $cardDetails[] = number_format((float) $listing->floor_area_sqm, 2).' sqm floor';
                                    }

                                    if (($listing->property_type ?? 'lot') === 'lot' && ! is_null($listing->price_per_sqm)) {
                                        $cardDetails[] = '₱'.number_format((float) $listing->price_per_sqm, 2).'/sqm';
                                    }

                                    if ($isProjectPosting && ! is_null($listing->available_units)) {
                                        $cardDetails[] = $listing->available_units.' units available';
                                    }

                                    if (! is_null($listing->bedrooms)) {
                                        if ($isProjectPosting) {
                                            $bedroomRange = is_null($listing->bedrooms_max)
                                                ? $listing->bedrooms.'+'
                                                : $listing->bedrooms.'-'.$listing->bedrooms_max;
                                            $cardDetails[] = $bedroomRange.' BR';
                                        } else {
                                            $cardDetails[] = $listing->bedrooms.' BR';
                                        }
                                    }

                                    if (! is_null($listing->bathrooms)) {
                                        if ($isProjectPosting && ! is_null($listing->bathrooms_max)) {
                                            $cardDetails[] = $listing->bathrooms.'-'.$listing->bathrooms_max.' BA';
                                        } else {
                                            $cardDetails[] = $listing->bathrooms.' BA';
                                        }
                                    }

                                    if (($listing->property_type ?? 'lot') === 'house_and_lot' && ! is_null($listing->floors)) {
                                        if ($isProjectPosting && ! is_null($listing->floors_max)) {
                                            $cardDetails[] = $listing->floors.'-'.$listing->floors_max.' Floors';
                                        } else {
                                            $cardDetails[] = $listing->floors.' Floors';
                                        }
                                    }
                                @endphp

                                @if(! empty($cardDetails))
                                    <p class="text-muted small mb-2">{{ implode(' • ', $cardDetails) }}</p>
                                @endif

                                <p class="fw-bold mb-0">
                                    ₱{{ number_format($listing->price, 2) }}
                                    @if($isProjectPosting && ! is_null($listing->price_max) && (float) $listing->price_max > (float) $listing->price)
                                        - ₱{{ number_format((float) $listing->price_max, 2) }}
                                    @endif
                                </p>
                            </div>
                            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 d-flex gap-2">
                                <a href="{{ route('listings.show', $listing) }}" class="btn btn-outline-primary flex-grow-1">View Details</a>
                                @auth
                                    @if(auth()->user()->role === 'buyer')
                                        @if($isSaved)
                                            <form method="POST" action="{{ route('buyer.wishlist.destroy', $listing) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger" type="submit" aria-label="Remove from wishlist" title="Remove from wishlist">
                                                    <i class="bi bi-heart-fill"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('buyer.wishlist.store', $listing) }}">
                                                @csrf
                                                <button class="btn btn-outline-secondary" type="submit" aria-label="Add to wishlist" title="Add to wishlist">
                                                    <i class="bi bi-heart"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                @endauth
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-light border text-center mb-0">
                            No properties match your selected filters.
                        </div>
                    </div>
                @endforelse
            </div>

            @if($listings->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $listings->links() }}
                </div>
            @endif
        </div>
    </main>

    @include('partials.site-footer')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterCollapse = document.getElementById('propertiesFilterCollapse');
            const filterToggleButton = document.querySelector('[data-bs-target="#propertiesFilterCollapse"]');
            const filterForm = document.querySelector('form[action="{{ route('properties.index') }}"]');

            if (!filterCollapse || !filterToggleButton) {
                if (filterForm) {
                    filterForm.addEventListener('submit', () => {
                        if (window.matchMedia('(max-width: 991.98px)').matches) {
                            filterForm.action = '{{ route('properties.index') }}#propertiesResults';
                        }
                    });
                }

                return;
            }

            const syncFilterToggleLabel = () => {
                const isExpanded = filterCollapse.classList.contains('show');
                filterToggleButton.textContent = isExpanded ? 'Hide Filters' : 'Show Filters';
                filterToggleButton.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            };

            filterCollapse.addEventListener('shown.bs.collapse', syncFilterToggleLabel);
            filterCollapse.addEventListener('hidden.bs.collapse', syncFilterToggleLabel);
            syncFilterToggleLabel();

            if (filterForm) {
                filterForm.addEventListener('submit', () => {
                    if (window.matchMedia('(max-width: 991.98px)').matches) {
                        filterForm.action = '{{ route('properties.index') }}#propertiesResults';
                    }
                });
            }
        });
    </script>
</body>
</html>
