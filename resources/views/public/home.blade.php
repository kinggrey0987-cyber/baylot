<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'BayLot') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg fixed-top baylot-navbar" aria-label="Main navigation">
            <div class="container">
                <a class="navbar-brand" href="{{ route('home') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="BayLot" class="brand-logo">
                </a>
                <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                        <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('properties.index') }}">Properties</a></li>
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

    <main>
        <section id="home" class="hero-section d-flex align-items-center" style="--hero-image: url('{{ asset('images/chatgpt-image.png') }}');">
            <div class="hero-overlay"></div>
            <div class="container position-relative hero-content text-center">
                <h1 class="display-4 fw-bold mb-3 animate-on-scroll">Buy and sell real estate properties</h1>
                <p class="lead mb-4 text-white-50 mx-auto animate-on-scroll" style="max-width: 680px;">Find your ideal property, compare opportunities, and schedule site visits with confidence anywhere in Negros Occidental.</p>
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 animate-on-scroll">
                    <a href="{{ route('properties.index') }}" class="btn btn-warning btn-lg px-4" aria-label="Browse properties">Browse Properties</a>
                    <a href="{{ route('about') }}" class="btn btn-outline-light btn-lg px-4" aria-label="Learn about BayLot">Learn More</a>
                </div>
                <a href="#how-it-works" class="scroll-down" aria-label="Scroll to How It Works section">
                    <i class="bi bi-chevron-double-down"></i>
                </a>
            </div>
        </section>

        <section id="how-it-works" class="py-5 bg-white">
            <div class="container py-4">
                <div class="text-center mb-5 animate-on-scroll">
                    <p class="text-uppercase fw-semibold text-muted mb-2">Simple Process</p>
                    <h2 class="section-title-accent">How It Works</h2>
                </div>
                <div class="row g-4">
                    <div class="col-12 col-md-4 animate-on-scroll">
                        <div class="card h-100 border-0 shadow-sm p-4 text-center">
                            <div class="icon-circle mx-auto mb-3"><i class="bi bi-search"></i></div>
                            <h3 class="h5">Find Property</h3>
                            <p class="text-muted mb-0">Browse quality listings by budget, location, and property type in one place.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 animate-on-scroll">
                        <div class="card h-100 border-0 shadow-sm p-4 text-center">
                            <div class="icon-circle mx-auto mb-3"><i class="bi bi-cash-coin"></i></div>
                            <h3 class="h5">Secure Best Deal</h3>
                            <p class="text-muted mb-0">Compare options and discuss with agents to get the best value for your investment.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 animate-on-scroll">
                        <div class="card h-100 border-0 shadow-sm p-4 text-center">
                            <div class="icon-circle mx-auto mb-3"><i class="bi bi-house-check"></i></div>
                            <h3 class="h5">Book Site Visit</h3>
                            <p class="text-muted mb-0">Schedule a visit and move forward with complete visibility and support.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="properties" class="py-5 bg-light">
            <div class="container py-4">
                <div class="d-flex justify-content-between align-items-center mb-4 animate-on-scroll">
                    <h2 class="h3 mb-0">Featured Properties</h2>
                    <a href="{{ route('properties.index') }}" class="text-decoration-none">View all properties</a>
                </div>
                <div class="row g-4">
                    @forelse($featuredListings as $listing)
                        <div class="col-12 col-md-6 col-lg-4 animate-on-scroll">
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
                                    <h3 class="h5">{{ $listing->title }}</h3>
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
                                <div class="card-footer bg-white border-0 pt-0 pb-3 px-3">
                                    <a href="{{ route('listings.show', $listing) }}" class="btn btn-outline-primary w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 animate-on-scroll">
                            <div class="alert alert-light border text-center mb-0">
                                No properties available right now.
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="about" class="py-5 bg-white">
            <div class="container py-4">
                <div class="row g-4 text-center">
                    <div class="col-6 col-lg-3 animate-on-scroll">
                        <p class="display-6 fw-bold mb-1">1,200+</p>
                        <p class="text-muted mb-0">Active Listings</p>
                    </div>
                    <div class="col-6 col-lg-3 animate-on-scroll">
                        <p class="display-6 fw-bold mb-1">98%</p>
                        <p class="text-muted mb-0">Client Satisfaction</p>
                    </div>
                    <div class="col-6 col-lg-3 animate-on-scroll">
                        <p class="display-6 fw-bold mb-1">320+</p>
                        <p class="text-muted mb-0">Site Visits / Month</p>
                    </div>
                    <div class="col-6 col-lg-3 animate-on-scroll">
                        <p class="display-6 fw-bold mb-1">15+</p>
                        <p class="text-muted mb-0">Partner Agents</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer-dark py-4">
        <div class="container">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-4 text-center text-md-start">
                    <img src="{{ asset('images/logo.png') }}" alt="BayLot" class="brand-logo brand-logo-sm d-inline-block">
                </div>
                <div class="col-12 col-md-4 text-center">
                    <a href="{{ route('about') }}" class="footer-link me-3">About</a>
                    <a href="{{ route('properties.index') }}" class="footer-link">Properties</a>
                </div>
                <div class="col-12 col-md-4 text-center text-md-end text-white-50">
                    support@baylot.local · +63 917 000 0000
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
