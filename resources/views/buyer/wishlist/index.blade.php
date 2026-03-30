<x-app-layout>
    <div class="app-section-head d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="app-section-title">My Wishlist</h1>
            <p class="app-section-subtitle">Saved properties you can revisit anytime.</p>
        </div>
        <a href="{{ route('properties.index') }}" class="btn btn-outline-primary btn-sm">Browse More Properties</a>
    </div>

    <div class="card module-card">
        <div class="card-body">
            <div class="row g-3">
                @forelse($wishlists as $wishlist)
                    @php
                        $listing = $wishlist->listing;
                    @endphp

                    @if($listing)
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <img src="{{ $listing->images->first()?->image_url ?? asset('images/hero.jpg') }}" class="card-img-top" alt="{{ $listing->title }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <div class="d-flex gap-1 flex-wrap">
                                            <span class="badge text-bg-info">{{ ucwords(str_replace('_', ' ', $listing->property_type ?? 'lot')) }}</span>
                                            <span class="badge text-bg-secondary">{{ ucfirst($listing->listing_type) }}</span>
                                        </div>
                                        <span class="text-muted small">{{ $listing->status === 'unavailable' ? 'Not Available' : ucfirst($listing->status) }}</span>
                                    </div>

                                    <h2 class="h5">{{ $listing->title }}</h2>
                                    <p class="text-muted text-truncate">{{ $listing->description }}</p>
                                    <p class="fw-bold mb-0">₱{{ number_format($listing->price, 2) }}</p>
                                </div>
                                <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 d-flex gap-2">
                                    <a href="{{ route('listings.show', $listing) }}" class="btn btn-outline-primary flex-grow-1">View Details</a>
                                    <form method="POST" action="{{ route('buyer.wishlist.destroy', $listing) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger" type="submit" aria-label="Remove from wishlist" title="Remove from wishlist">
                                            <i class="bi bi-heart-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="col-12">
                        <div class="alert alert-light border text-center mb-0">
                            Your wishlist is empty. Save properties by tapping the heart icon on listings.
                        </div>
                    </div>
                @endforelse
            </div>

            @if($wishlists->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $wishlists->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
