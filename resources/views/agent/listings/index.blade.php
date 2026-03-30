<x-app-layout>
    <div class="app-section-head">
        <div>
            <h1 class="app-section-title">My Exclusive Listings</h1>
            <p class="app-section-subtitle">Manage your private inventory and keep listing details updated.</p>
        </div>
        <a href="{{ route('agent.listings.create') }}" class="btn btn-primary">New Listing</a>
    </div>

    <div class="card module-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('agent.listings.index') }}" class="row g-2 module-form">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="filter_property_type">Filter by Property Type</label>
                    <select id="filter_property_type" class="form-select" name="property_type">
                        <option value="">All</option>
                        <option value="lot" @selected(request('property_type') === 'lot')>Lot</option>
                        <option value="house_and_lot" @selected(request('property_type') === 'house_and_lot')>House and Lot</option>
                        <option value="condo" @selected(request('property_type') === 'condo')>Condo</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid align-self-end">
                    <button class="btn btn-outline-primary" type="submit">Apply</button>
                </div>
                @if(request()->filled('property_type'))
                    <div class="col-12 col-md-2 d-grid align-self-end">
                        <a href="{{ route('agent.listings.index') }}" class="btn btn-outline-secondary">Clear</a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="row g-3">
        @forelse($listings as $listing)
            @php
                $statusClass = match ($listing->status) {
                    'available' => 'text-bg-success',
                    'reserved' => 'text-bg-warning',
                    'sold' => 'text-bg-dark',
                    'unavailable' => 'text-bg-secondary',
                    default => 'text-bg-secondary',
                };
                $statusLabel = $listing->status === 'unavailable' ? 'Not Available' : ucfirst($listing->status);
            @endphp
            <div class="col-12 col-md-6">
                <div class="card module-card h-100">
                    <div class="card-body">
                        <h5>{{ $listing->title }}</h5>
                            <div class="d-flex flex-wrap gap-1 mb-1">
                                <span class="badge text-bg-info">{{ ucwords(str_replace('_', ' ', $listing->property_type ?? 'lot')) }}</span>
                                <span class="badge {{ $statusClass }} status-badge">{{ $statusLabel }}</span>
                            </div>

                            @php
                                $cardDetails = [];

                                if (! is_null($listing->lot_area_sqm)) {
                                    $cardDetails[] = number_format((float) $listing->lot_area_sqm, 2).' sqm lot';
                                }

                                if (($listing->property_type ?? 'lot') === 'house_and_lot' && ! is_null($listing->floor_area_sqm)) {
                                    $cardDetails[] = number_format((float) $listing->floor_area_sqm, 2).' sqm floor';
                                }

                                if (($listing->property_type ?? 'lot') === 'lot' && ! is_null($listing->price_per_sqm)) {
                                    $cardDetails[] = '₱'.number_format((float) $listing->price_per_sqm, 2).'/sqm';
                                }

                                if (! is_null($listing->bedrooms)) {
                                    $cardDetails[] = $listing->bedrooms.' BR';
                                }

                                if (! is_null($listing->bathrooms)) {
                                    $cardDetails[] = $listing->bathrooms.' BA';
                                }

                                if (($listing->property_type ?? 'lot') === 'house_and_lot' && ! is_null($listing->floors)) {
                                    $cardDetails[] = $listing->floors.' Floors';
                                }
                            @endphp

                            @if(! empty($cardDetails))
                                <p class="text-muted small mb-1">{{ implode(' • ', $cardDetails) }}</p>
                            @endif

                        <p class="mb-1">
                            ₱{{ number_format($listing->price, 2) }}
                        </p>
                    </div>
                    <div class="card-footer d-flex gap-2 bg-white">
                        <a href="{{ route('agent.listings.edit', $listing) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" action="{{ route('agent.listings.destroy', $listing) }}" onsubmit="return confirm('Delete listing?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card module-card">
                    <div class="card-body table-empty">No exclusive listings found.</div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $listings->links() }}</div>
</x-app-layout>
