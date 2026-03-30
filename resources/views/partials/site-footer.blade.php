<footer class="baylot-site-footer">
    <div class="container py-4 py-lg-5">
        <div class="row g-4">
            <div class="col-12 col-lg-5">
                <a class="d-inline-flex align-items-center gap-2 text-decoration-none mb-2" href="{{ route('home') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="BayLot" class="brand-logo brand-logo-sm">
                    <span class="footer-brand-text">BayLot</span>
                </a>
                <p class="mb-0 small text-white-50">
                    A modern marketplace for buyers, brokers, and agents across Negros Occidental.
                </p>
            </div>

            <div class="col-6 col-lg-3">
                <h2 class="h6 footer-heading">Explore</h2>
                <div class="d-flex flex-column gap-1">
                    <a href="{{ route('home') }}" class="footer-link">Home</a>
                    <a href="{{ route('properties.index') }}" class="footer-link">Properties</a>
                    <a href="{{ route('about') }}" class="footer-link">About</a>
                </div>
            </div>

            <div class="col-6 col-lg-4">
                <h2 class="h6 footer-heading">Contact</h2>
                <p class="footer-contact mb-1">support@baylot.local</p>
                <p class="footer-contact mb-0">+63 917 000 0000</p>
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-4 pt-3 border-top border-light border-opacity-25">
            <small class="text-white-50">&copy; {{ date('Y') }} {{ config('app.name', 'BayLot') }}. All rights reserved.</small>
            <small class="text-white-50">Built for Negros Occidental real estate operations.</small>
        </div>
    </div>
</footer>