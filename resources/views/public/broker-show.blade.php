<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $broker->name }} — Broker Profile | {{ config('app.name', 'BayLot') }}</title>
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
                    data-bs-target="#brokerNav" aria-controls="brokerNav" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="brokerNav">
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

            {{-- Broker Header --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4 p-lg-5">
                    @php
                        $firstInquiryListing = $sharedListings->first() ?? $teamExclusiveListings->first();
                    @endphp
                    <div class="d-flex flex-column flex-md-row align-items-md-center gap-4">
                        <div class="flex-shrink-0">
                            @if(! empty($broker->profile_photo_path))
                                <img src="{{ asset(ltrim($broker->profile_photo_path, '/')) }}" alt="{{ $broker->name }}" class="rounded-circle object-fit-cover" style="width:80px;height:80px;">
                            @else
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                     style="width:80px;height:80px;font-size:2rem;font-weight:700;">
                                    {{ strtoupper(mb_substr($broker->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                                <h1 class="h3 mb-0">{{ $broker->name }}</h1>
                                <span class="badge text-bg-primary">Broker</span>
                            </div>
                            <p class="text-muted mb-1">{{ $broker->email }}</p>
                            @if(! empty($broker->phone))
                                <p class="text-muted small mb-1"><i class="bi bi-telephone me-1"></i>{{ $broker->phone }}</p>
                            @endif
                            <p class="text-muted small mb-2">{{ $broker->bio ?: 'This broker manages a curated portfolio of shared and team-exclusive inventory across Negros Occidental.' }}</p>
                            <div class="d-flex flex-wrap gap-3 text-muted small">
                                <span><i class="bi bi-house-door me-1"></i>{{ $stats['shared_listings_count'] }} shared listing(s)</span>
                                <span><i class="bi bi-house-check me-1"></i>{{ $stats['team_exclusive_listings_count'] }} team exclusive listing(s)</span>
                                <span><i class="bi bi-folder2 me-1"></i>{{ $stats['projects_count'] }} project(s)</span>
                                <span><i class="bi bi-people me-1"></i>{{ $stats['active_agents_count'] }} active agent(s)</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            @if($firstInquiryListing)
                                <a href="{{ route('listings.show', $firstInquiryListing) }}#request-site-visit" class="btn btn-primary">
                                    <i class="bi bi-chat-dots"></i> Inquire
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Projects --}}
            @if($projects->isNotEmpty())
                <h2 class="h5 mb-3">Projects</h2>
                <div class="row g-3 mb-4">
                    @foreach($projects as $project)
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h3 class="h6 fw-semibold mb-1">{{ $project->name }}</h3>
                                    @if($project->description)
                                        <p class="text-muted small mb-2">{{ \Illuminate\Support\Str::limit($project->description, 120) }}</p>
                                    @endif
                                    <div class="d-flex gap-3 text-muted small">
                                        <span><i class="bi bi-house-door me-1"></i>{{ $project->active_shared_listings_count }} active unit(s)</span>
                                        <span class="text-muted">{{ $project->total_shared_listings_count }} total</span>
                                    </div>
                                </div>
                                @if($project->active_shared_listings_count > 0)
                                    <div class="card-footer bg-white border-0 pt-0">
                                        <a href="{{ route('properties.index', ['project_id' => $project->id]) }}"
                                           class="btn btn-outline-primary btn-sm">Browse Listings</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Shared Listings --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Broker Shared Inventory</h2>
                <span class="badge text-bg-secondary">{{ $sharedListings->total() }} total</span>
            </div>

            @if($sharedListings->isNotEmpty())
                <div class="row g-3 mb-4">
                    @foreach($sharedListings as $listing)
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <img src="{{ $listing->images->first()?->image_url ?? asset('images/hero.jpg') }}"
                                     class="card-img-top" alt="{{ $listing->title }}"
                                     style="height:180px;object-fit:cover;">
                                <div class="card-body">
                                    <div class="d-flex gap-1 flex-wrap mb-2">
                                        <span class="badge text-bg-info">{{ ucwords(str_replace('_',' ',$listing->property_type ?? 'lot')) }}</span>
                                        @if($listing->project)
                                            <span class="badge text-bg-light border text-dark">{{ $listing->project->name }}</span>
                                        @endif
                                    </div>
                                    <h3 class="h6">{{ $listing->title }}</h3>
                                    <p class="fw-bold text-primary mb-1">
                                        ₱{{ number_format($listing->price, 0) }}
                                        @if(! is_null($listing->price_max) && (float)$listing->price_max > (float)$listing->price)
                                            – ₱{{ number_format((float)$listing->price_max, 0) }}
                                        @endif
                                    </p>
                                    <p class="text-muted small mb-0">
                                        @if(! is_null($listing->lot_area_sqm))
                                            {{ number_format((float)$listing->lot_area_sqm, 0) }} sqm lot &nbsp;
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
                @if($sharedListings->hasPages())
                    <div class="d-flex justify-content-center mb-4">{{ $sharedListings->links() }}</div>
                @endif
            @else
                <div class="alert alert-light border mb-4">No active shared listings at the moment.</div>
            @endif

            {{-- Team Exclusive Listings --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Exclusive Listings Under Broker Team</h2>
                <span class="badge text-bg-secondary">{{ $teamExclusiveListings->total() }} total</span>
            </div>

            @if($teamExclusiveListings->isNotEmpty())
                <div class="row g-3 mb-4">
                    @foreach($teamExclusiveListings as $listing)
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
                                    <p class="fw-bold text-primary mb-1">₱{{ number_format((float) $listing->price, 0) }}</p>
                                    <p class="text-muted small mb-0">
                                        Agent: {{ $listing->owner?->name ?? 'Unknown agent' }}
                                        @if(! is_null($listing->bedrooms)) · {{ $listing->bedrooms }}BR @endif
                                        @if(! is_null($listing->bathrooms)) · {{ $listing->bathrooms }}BA @endif
                                    </p>
                                </div>
                                <div class="card-footer bg-white border-0 pt-0 pb-3 px-3">
                                    <a href="{{ route('listings.show', $listing) }}" class="btn btn-outline-primary btn-sm w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($teamExclusiveListings->hasPages())
                    <div class="d-flex justify-content-center mb-4">{{ $teamExclusiveListings->links() }}</div>
                @endif
            @else
                <div class="alert alert-light border mb-4">No active exclusive team listings at the moment.</div>
            @endif

            {{-- Team of Agents --}}
            @if($activeAgents->isNotEmpty())
                <h2 class="h5 mb-3">Agents Under This Broker</h2>
                <div class="row g-3 mb-4">
                    @foreach($activeAgents as $agentMember)
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="flex-shrink-0 rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                         style="width:48px;height:48px;font-size:1.25rem;font-weight:700;">
                                        {{ strtoupper(mb_substr($agentMember->name, 0, 1)) }}
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <h3 class="h6 mb-0">
                                            <a href="{{ route('agents.show', $agentMember) }}" class="text-decoration-none stretched-link">{{ $agentMember->name }}</a>
                                        </h3>
                                        <p class="text-muted small mb-0">{{ $agentMember->active_exclusive_listings_count }} active listing(s)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <a href="{{ route('properties.index', ['broker_id' => $broker->id]) }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-search"></i> See All Listings by This Broker
            </a>
            <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary">
                &larr; Back to Properties
            </a>
        </div>
    </main>

    @include('partials.site-footer')
</body>
</html>
