<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>About Us - {{ config('app.name', 'BayLot') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg fixed-top baylot-navbar" aria-label="Main navigation">
            <div class="container">
                <a class="navbar-brand" href="{{ route('home') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="BayLot" class="brand-logo">
                </a>
                <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#aboutNav" aria-controls="aboutNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="aboutNav">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                        <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('properties.index') }}">Properties</a></li>
                        <li class="nav-item"><a class="nav-link active" href="{{ route('about') }}">About</a></li>
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
        <!-- About Hero Section -->
        <section class="about-hero-section text-white" style="--about-hero-image: url('{{ asset('images/aboutus.png') }}');">
            <div class="about-hero-overlay"></div>
            <div class="container position-relative">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-8 animate-on-scroll">
                        <h1 class="display-5 fw-bold mb-3">Your Trusted Real Estate Partner in Negros Occidental</h1>
                        <p class="lead text-white-50 mb-4">BayLot makes buying and selling properties simpler through verified listings, transparent details, and reliable local support.</p>
                        <p class="text-white-50 mb-4">From your first search to site visit scheduling, we help buyers and sellers move forward with confidence.</p>
                        <div class="d-flex gap-3">
                            <a href="{{ route('properties.index') }}" class="btn btn-warning btn-lg px-4">Explore Listings</a>
                            <a href="#our-mission" class="btn btn-outline-light btn-lg px-4">Our Mission</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our Mission Section -->
        <section id="our-mission" class="py-5 bg-white">
            <div class="container">
                <div class="text-center mb-5 animate-on-scroll">
                    <p class="text-uppercase fw-semibold text-muted mb-2">What We Stand For</p>
                    <h2 class="section-title-accent display-6 fw-bold">Our Mission &amp; Values</h2>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 col-lg-4 animate-on-scroll">
                        <div class="card h-100 border-0 shadow-sm p-4 text-center">
                            <div class="icon-circle mx-auto mb-3"><i class="bi bi-shield-check"></i></div>
                            <h3 class="h5 mb-2">Transparency</h3>
                            <p class="text-muted mb-0">Every listing, every detail, every transaction is transparent. No hidden fees, no surprises.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 animate-on-scroll">
                        <div class="card h-100 border-0 shadow-sm p-4 text-center">
                            <div class="icon-circle mx-auto mb-3"><i class="bi bi-people-fill"></i></div>
                            <h3 class="h5 mb-2">Community Focused</h3>
                            <p class="text-muted mb-0">We're built for and by the people of Negros Occidental. Your success is our mission.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 animate-on-scroll">
                        <div class="card h-100 border-0 shadow-sm p-4 text-center">
                            <div class="icon-circle mx-auto mb-3"><i class="bi bi-lightning-fill"></i></div>
                            <h3 class="h5 mb-2">Efficiency</h3>
                            <p class="text-muted mb-0">Modern tools and streamlined processes mean faster, smoother transactions.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 animate-on-scroll">
                        <div class="card h-100 border-0 shadow-sm p-4 text-center">
                            <div class="icon-circle mx-auto mb-3"><i class="bi bi-heart-fill"></i></div>
                            <h3 class="h5 mb-2">Integrity</h3>
                            <p class="text-muted mb-0">We partner with verified agents and maintain strict quality standards across all listings.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 animate-on-scroll">
                        <div class="card h-100 border-0 shadow-sm p-4 text-center">
                            <div class="icon-circle mx-auto mb-3"><i class="bi bi-geo-alt-fill"></i></div>
                            <h3 class="h5 mb-2">Local Expertise</h3>
                            <p class="text-muted mb-0">Our agents know Negros Occidental inside and out—geography, market, and culture.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 animate-on-scroll">
                        <div class="card h-100 border-0 shadow-sm p-4 text-center">
                            <div class="icon-circle mx-auto mb-3"><i class="bi bi-chat-heart-fill"></i></div>
                            <h3 class="h5 mb-2">Support</h3>
                            <p class="text-muted mb-0">Responsive customer support at every stage of your property journey.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How We Work Section -->
        <section class="py-5 bg-light">
            <div class="container">
                <div class="text-center mb-5 animate-on-scroll">
                    <p class="text-uppercase fw-semibold text-muted mb-2">Our Process</p>
                    <h2 class="section-title-accent display-6 fw-bold">How BayLot Works</h2>
                </div>

                <div class="row g-4 align-items-stretch">
                    <div class="col-md-6 col-lg-3 animate-on-scroll">
                        <div class="card h-100 border-0 shadow-sm p-4">
                            <div class="badge text-bg-warning mb-3">1</div>
                            <h4 class="h6 mb-2">Register or Log In</h4>
                            <p class="text-muted small mb-0">Create a free account or sign in to access all features and personalized listings.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3 animate-on-scroll">
                        <div class="card h-100 border-0 shadow-sm p-4">
                            <div class="badge text-bg-warning mb-3">2</div>
                            <h4 class="h6 mb-2">Browse &amp; Filter</h4>
                            <p class="text-muted small mb-0">Explore properties by type, location, price range, and other criteria that matter to you.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3 animate-on-scroll">
                        <div class="card h-100 border-0 shadow-sm p-4">
                            <div class="badge text-bg-warning mb-3">3</div>
                            <h4 class="h6 mb-2">Request a Visit</h4>
                            <p class="text-muted small mb-0">Schedule a site visit with a qualified agent—quickly and conveniently.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3 animate-on-scroll">
                        <div class="card h-100 border-0 shadow-sm p-4">
                            <div class="badge text-bg-warning mb-3">4</div>
                            <h4 class="h6 mb-2">Decide &amp; Move Forward</h4>
                            <p class="text-muted small mb-0">Work with our agents and your legal advisors to complete the transaction with confidence.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Trust & Stats Section -->
        <section class="py-5 bg-white">
            <div class="container">
                <div class="text-center mb-5 animate-on-scroll">
                    <p class="text-uppercase fw-semibold text-muted mb-2">Our Impact</p>
                    <h2 class="section-title-accent display-6 fw-bold">Trusted by Our Community</h2>
                </div>

                <div class="row g-4 text-center mb-5">
                    <div class="col-6 col-lg-3 animate-on-scroll">
                        <div class="stat-card">
                            <div class="value text-warning">1,200+</div>
                            <div class="label">Active Listings</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3 animate-on-scroll">
                        <div class="stat-card">
                            <div class="value text-warning">2,500+</div>
                            <div class="label">Registered Buyers</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3 animate-on-scroll">
                        <div class="stat-card">
                            <div class="value text-warning">98%</div>
                            <div class="label">Client Satisfaction</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3 animate-on-scroll">
                        <div class="stat-card">
                            <div class="value text-warning">15+</div>
                            <div class="label">Partner Agents</div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 col-lg-4 animate-on-scroll">
                        <svg class="text-warning mb-3" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2z"/>
                        </svg>
                        <h5 class="mb-2">Secure Transactions</h5>
                        <p class="text-muted small">Your personal and financial data is protected with industry-standard encryption.</p>
                    </div>

                    <div class="col-md-6 col-lg-4 animate-on-scroll">
                        <svg class="text-warning mb-3" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3zM7 9a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V9zM13 1a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h2z"/>
                        </svg>
                        <h5 class="mb-2">Real Data</h5>
                        <p class="text-muted small">All pricing, availability, and property details are verified and current.</p>
                    </div>

                    <div class="col-md-6 col-lg-4 animate-on-scroll">
                        <svg class="text-warning mb-3" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="m10.97 4.97-.02.02-3.6 3.85-.915-.93a.5.5 0 0 0-.707.707l1.5 1.5a.5.5 0 0 0 .757-.02l4.2-4.5a.5.5 0 0 0-.757-.735z"/>
                        </svg>
                        <h5 class="mb-2">Verified Agents</h5>
                        <p class="text-muted small">Every agent on our platform is vetted and maintains our standards of professionalism.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Team / Contact CTA Section -->
        <section class="py-5 bg-gradient text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
            <div class="container">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-6 animate-on-scroll">
                        <h2 class="display-6 fw-bold mb-3">Ready to get started?</h2>
                        <p class="lead mb-4">Whether you're buying, selling, or just exploring, BayLot is here to guide you every step of the way.</p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="{{ route('properties.index') }}" class="btn btn-warning btn-lg px-4">Browse Properties</a>
                            <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg px-4">Create Account</a>
                        </div>
                    </div>
                    <div class="col-lg-6 animate-on-scroll">
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <h5 class="mb-1">Support</h5>
                                <p class="text-white-50 mb-0">support@baylot.local</p>
                            </div>
                            <div>
                                <h5 class="mb-1">Call Us</h5>
                                <p class="text-white-50 mb-0">+63 917 000 0000</p>
                            </div>
                            <div>
                                <h5 class="mb-1">Service Hours</h5>
                                <p class="text-white-50 mb-0">Monday - Friday: 8:00 AM - 5:00 PM<br>Saturday: 9:00 AM - 1:00 PM</p>
                            </div>
                        </div>
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
                    <a href="{{ route('home') }}" class="footer-link me-3">Home</a>
                    <a href="{{ route('properties.index') }}" class="footer-link me-3">Properties</a>
                    <a href="{{ route('about') }}" class="footer-link">About</a>
                </div>
                <div class="col-12 col-md-4 text-center text-md-end text-white-50">
                    support@baylot.local · +63 917 000 0000
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
