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
        <nav class="navbar navbar-expand-lg fixed-top baylot-navbar" aria-label="Main navigation">
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
        <div class="container" style="padding-top: 5.5rem;">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">Available Properties</h1>
                    <p class="text-muted mb-0">Browse posted listings and refine by category and budget.</p>
                </div>
                <a href="{{ route('home') }}" class="btn btn-outline-secondary">Back to Home</a>
            </div>

            @if (session('success'))
                <div class="alert alert-success shadow-sm border-0" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @php
                $selectedType = request()->string('property_type')->toString();
                $baseFilter = request()->only(['keyword', 'min_price', 'max_price']);
                $hasActiveFilters = request()->filled('property_type')
                    || request()->filled('keyword')
                    || request()->filled('min_price')
                    || request()->filled('max_price');
                $propertyTypes = [
                    '' => 'All',
                    'lot' => 'Lot',
                    'house_and_lot' => 'House and Lot',
                    'condo' => 'Condo',
                ];
            @endphp

            <div class="card border-0 shadow-sm mb-4 properties-filter-sticky">
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
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="keyword">Keyword</label>
                            <input id="keyword" name="keyword" class="form-control" value="{{ request('keyword') }}" placeholder="Title or description">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="min_price">Min Price</label>
                            <input id="min_price" name="min_price" class="form-control" type="number" step="0.01" min="0" value="{{ request('min_price') }}" placeholder="0.00">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="max_price">Max Price</label>
                            <input id="max_price" name="max_price" class="form-control" type="number" step="0.01" min="0" value="{{ request('max_price') }}" placeholder="10000000.00">
                        </div>
                        <div class="col-12 col-md-2 d-grid d-md-flex justify-content-md-end gap-2">
                            <button type="submit" class="btn btn-primary">Apply</button>
                            <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
                </div>
            </div>

            <div id="propertiesResults" class="row g-4">
                @forelse($listings as $listing)
                    @php
                        $isSaved = in_array($listing->id, $savedListingIds ?? [], true);
                    @endphp
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <img src="{{ $listing->images->first()?->image_url ?? 'https://via.placeholder.com/640x360?text=BayLot' }}" class="card-img-top" alt="{{ $listing->title }}">
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
