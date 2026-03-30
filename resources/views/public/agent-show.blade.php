<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $agent->name }} — Agent Profile | {{ config('app.name', 'BayLot') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg fixed-top baylot-navbar" aria-label="Main navigation">
            <div class="container">
                <a class="navbar-brand" href="{{ route('home') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="BayLot" class="brand-logo">
                </a>
                <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse"
                    data-bs-target="#agentNav" aria-controls="agentNav" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="agentNav">
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

    <main class="bg-light" style="padding-top: 5.5rem; padding-bottom: 3rem;">
        <div class="container">

            {{-- Agent Header --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-column flex-md-row align-items-md-center gap-4">
                        <div class="flex-shrink-0">
                            @if(! empty($agent->profile_photo_path))
                                <img src="{{ asset(ltrim($agent->profile_photo_path, '/')) }}" alt="{{ $agent->name }}" class="rounded-circle object-fit-cover" style="width:80px;height:80px;">
                            @else
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                     style="width:80px;height:80px;font-size:2rem;font-weight:700;">
                                    {{ strtoupper(mb_substr($agent->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                                <h1 class="h3 mb-0">{{ $agent->name }}</h1>
                                <span class="badge text-bg-secondary">Agent</span>
                            </div>
                            <p class="text-muted mb-1">{{ $agent->email }}</p>
                            @if(! empty($agent->phone))
                                <p class="text-muted small mb-1"><i class="bi bi-telephone me-1"></i>{{ $agent->phone }}</p>
                            @endif
                            @if($agent->broker)
                                <p class="mb-1 small">
                                    <i class="bi bi-building me-1 text-muted"></i>
                                    Under brokerage:
                                    <a href="{{ route('brokers.show', $agent->broker) }}" class="fw-semibold text-decoration-none">{{ $agent->broker->name }}</a>
                                </p>
                            @endif
                            <p class="text-muted small mb-2">{{ $agent->bio ?: 'This agent actively handles exclusive listings and buyer inquiries within assigned service areas.' }}</p>
                            <div class="d-flex flex-wrap gap-3 text-muted small">
                                <span><i class="bi bi-house-door me-1"></i>{{ $agent->active_exclusive_listings_count }} active exclusive listing(s)</span>
                                <span><i class="bi bi-grid-1x2 me-1"></i>{{ $relatedProjects->count() }} related project collaboration(s)</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            @if($listings->isNotEmpty())
                                <a href="{{ route('listings.show', $listings->first()) }}#request-site-visit"
                                   class="btn btn-primary">
                                    <i class="bi bi-chat-dots"></i> Inquire
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Listings --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Exclusive Listings</h2>
                <span class="badge text-bg-secondary">{{ $listingsCount }} total</span>
            </div>

            @if($listings->isNotEmpty())
                <div class="row g-3 mb-4">
                    @foreach($listings as $listing)
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <img src="{{ $listing->images->first()?->image_url ?? asset('images/hero.jpg') }}"
                                     class="card-img-top" alt="{{ $listing->title }}"
                                     style="height:180px;object-fit:cover;">
                                <div class="card-body">
                                    <div class="d-flex gap-1 flex-wrap mb-2">
                                        <span class="badge text-bg-info">{{ ucwords(str_replace('_',' ',$listing->property_type ?? 'lot')) }}</span>
                                        <span class="badge text-bg-light border text-dark">Exclusive</span>
                                    </div>
                                    <h3 class="h6">{{ $listing->title }}</h3>
                                    <p class="fw-bold text-primary mb-1">
                                        ₱{{ number_format($listing->price, 0) }}
                                    </p>
                                    <p class="text-muted small mb-0">
                                        @if(! is_null($listing->lot_area_sqm))
                                            {{ number_format((float)$listing->lot_area_sqm, 0) }} sqm lot&nbsp;
                                        @endif
                                        @if(! is_null($listing->bedrooms))
                                            · {{ $listing->bedrooms }}BR
                                        @endif
                                        @if(! is_null($listing->bathrooms))
                                            · {{ $listing->bathrooms }}BA
                                        @endif
                                    </p>
                                </div>
                                <div class="card-footer bg-white border-0 pt-0 pb-3 px-3">
                                    <a href="{{ route('listings.show', $listing) }}" class="btn btn-outline-primary btn-sm w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($listings->hasPages())
                    <div class="d-flex justify-content-center mb-4">{{ $listings->links() }}</div>
                @endif
            @else
                <div class="alert alert-light border mb-4">This agent has no active exclusive listings at the moment.</div>
            @endif

            @if($relatedProjects->isNotEmpty())
                <div class="mb-4">
                    <h2 class="h5 mb-3">Related Broker Projects</h2>
                    <div class="row g-3">
                        @foreach($relatedProjects as $project)
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h3 class="h6 mb-1">{{ $project->name }}</h3>
                                        <p class="text-muted small mb-2">{{ \Illuminate\Support\Str::limit((string) $project->description, 110) }}</p>
                                        <span class="badge text-bg-light border text-dark">{{ $project->active_shared_listings_count }} active shared listing(s)</span>
                                    </div>
                                    <div class="card-footer bg-white border-0 pt-0 pb-3 px-3">
                                        <a href="{{ route('properties.index', ['project_id' => $project->id]) }}" class="btn btn-outline-primary btn-sm w-100">Browse Project Listings</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <a href="{{ route('properties.index', ['agent_id' => $agent->id]) }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-search"></i> See All Listings by This Agent
            </a>
            @if($agent->broker)
                <a href="{{ route('brokers.show', $agent->broker) }}" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-building"></i> View Broker Profile
                </a>
            @endif
            <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary">
                &larr; Back to Properties
            </a>
        </div>
    </main>

    @include('partials.site-footer')
</body>
</html>
