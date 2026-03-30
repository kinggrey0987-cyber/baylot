<x-app-layout>
    @php
        $isProjectPosting = ($listing->listing_type ?? null) === 'shared';
        $statusLabel = $listing->status === 'unavailable' ? 'Not Available' : ucfirst($listing->status);
        $statusClass = match ($listing->status) {
            'available' => 'text-bg-success',
            'reserved' => 'text-bg-warning',
            'sold' => 'text-bg-dark',
            'unavailable' => 'text-bg-secondary',
            default => 'text-bg-secondary',
        };

        $images = $listing->images->sortBy('sort_order')->values();
        $carouselId = 'listingImagesCarousel-'.$listing->id;
        $overviewFullId = 'overviewFull-'.$listing->id;
        $overviewToggleId = 'overviewToggle-'.$listing->id;
        $overviewPreviewId = 'overviewPreview-'.$listing->id;
    @endphp

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div id="listingMap" class="map-canvas"></div>

                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                        <div>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge text-bg-info">{{ ucwords(str_replace('_', ' ', $listing->property_type ?? 'lot')) }}</span>
                                <span class="badge text-bg-primary">{{ ucfirst($listing->listing_type) }}</span>
                                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            </div>

                            <h1 class="h3 mb-2">{{ $listing->title }}</h1>
                            <p class="text-muted mb-2">
                                <i class="bi bi-geo-alt"></i>
                                Negros Occidental, Philippines
                                @if(! is_null($lat) && ! is_null($lng))
                                    <span class="small">(Lat {{ number_format($lat, 5) }}, Lng {{ number_format($lng, 5) }})</span>
                                @endif
                            </p>
                            <p class="display-6 fw-semibold text-primary mb-0">
                                ₱{{ number_format($listing->price, 2) }}
                                @if($isProjectPosting && ! is_null($listing->price_max) && (float) $listing->price_max > (float) $listing->price)
                                    - ₱{{ number_format((float) $listing->price_max, 2) }}
                                @endif
                            </p>
                        </div>

                        <div class="d-grid gap-2 align-content-start" style="min-width: 180px;">
                            <a href="#request-site-visit" class="btn btn-primary btn-sm">
                                <i class="bi bi-chat-dots"></i> Contact
                            </a>

                            @auth
                                @if(auth()->user()->role === 'buyer')
                                    @if($isSaved)
                                        <form method="POST" action="{{ route('buyer.wishlist.destroy', $listing) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm w-100" type="submit" aria-label="Remove from wishlist" title="Remove from wishlist">
                                                <i class="bi bi-heart-fill"></i> Saved
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('buyer.wishlist.store', $listing) }}">
                                            @csrf
                                            <button class="btn btn-outline-secondary btn-sm w-100" type="submit" aria-label="Add to wishlist" title="Add to wishlist">
                                                <i class="bi bi-heart"></i> Save
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            @endauth
                        </div>
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-12 col-lg-6">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <h2 class="h6 mb-3">Overview Facts</h2>
                                <dl class="row mb-0 small">
                                    <dt class="col-6 text-muted">Listing Type</dt>
                                    <dd class="col-6 mb-2">{{ ucfirst($listing->listing_type) }}</dd>

                                    <dt class="col-6 text-muted">Status</dt>
                                    <dd class="col-6 mb-2">{{ $statusLabel }}</dd>

                                    @if(! is_null($listing->lot_area_sqm))
                                        <dt class="col-6 text-muted">Lot Area</dt>
                                        <dd class="col-6 mb-2">
                                            {{ number_format((float) $listing->lot_area_sqm, 2) }} sqm
                                            @if($isProjectPosting && ! is_null($listing->lot_area_max_sqm))
                                                - {{ number_format((float) $listing->lot_area_max_sqm, 2) }} sqm
                                            @endif
                                        </dd>
                                    @endif

                                    @if(! is_null($listing->floor_area_sqm))
                                        <dt class="col-6 text-muted">Floor Area</dt>
                                        <dd class="col-6 mb-2">{{ number_format((float) $listing->floor_area_sqm, 2) }} sqm</dd>
                                    @endif

                                    @if(! is_null($listing->bedrooms))
                                        <dt class="col-6 text-muted">Bedrooms</dt>
                                        <dd class="col-6 mb-2">
                                            {{ $listing->bedrooms }}
                                            @if($isProjectPosting && ! is_null($listing->bedrooms_max))
                                                - {{ $listing->bedrooms_max }}
                                            @elseif($isProjectPosting)
                                                +
                                            @endif
                                        </dd>
                                    @endif

                                    @if(! is_null($listing->bathrooms))
                                        <dt class="col-6 text-muted">Bathrooms</dt>
                                        <dd class="col-6 mb-2">
                                            {{ $listing->bathrooms }}
                                            @if($isProjectPosting && ! is_null($listing->bathrooms_max))
                                                - {{ $listing->bathrooms_max }}
                                            @endif
                                        </dd>
                                    @endif

                                    @if($isProjectPosting && ! is_null($listing->available_units))
                                        <dt class="col-6 text-muted">Available Units</dt>
                                        <dd class="col-6 mb-2">{{ $listing->available_units }}</dd>
                                    @endif
                                </dl>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <h2 class="h6 mb-3">Seller / Poster</h2>
                                @if($listing->listing_type === 'exclusive')
                                    <p class="mb-1">
                                        <strong>Agent:</strong>
                                        @if($listing->owner)
                                            <a href="{{ route('agents.show', $listing->owner) }}" class="text-decoration-none">{{ $listing->owner->name }}</a>
                                        @else
                                            Unknown agent
                                        @endif
                                    </p>
                                    @if($listing->owner?->broker)
                                        <p class="mb-1">
                                            <strong>Brokerage:</strong>
                                            <a href="{{ route('brokers.show', $listing->owner->broker) }}" class="text-decoration-none">{{ $listing->owner->broker->name }}</a>
                                        </p>
                                    @endif
                                @else
                                    <p class="mb-1"><strong>Project:</strong> {{ $listing->project?->name ?? 'Shared project' }}</p>
                                    <p class="mb-1">
                                        <strong>Published by Broker:</strong>
                                        @if($listing->project?->broker)
                                            <a href="{{ route('brokers.show', $listing->project->broker) }}" class="text-decoration-none">{{ $listing->project->broker->name }}</a>
                                        @else
                                            Unassigned broker
                                        @endif
                                    </p>
                                @endif
                                <p class="text-muted small mb-0 mt-2">Use the contact panel to send an inquiry or schedule a site visit.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h2 class="h6 mb-3">Description</h2>
                        <div class="border rounded p-3 bg-white">
                            <div class="mb-3">
                                <h3 class="h6 text-uppercase text-muted mb-2">Overview</h3>

                                <p id="{{ $overviewPreviewId }}" class="mb-2 text-secondary" style="white-space: pre-line;">
                                    {{ $structuredDescription['has_long_overview'] ? $structuredDescription['overview_preview'] : $structuredDescription['overview'] }}
                                </p>

                                @if($structuredDescription['has_long_overview'])
                                    <div class="collapse" id="{{ $overviewFullId }}">
                                        <p class="mb-2 text-secondary" style="white-space: pre-line;">{{ $structuredDescription['overview'] }}</p>
                                    </div>
                                    <button
                                        id="{{ $overviewToggleId }}"
                                        type="button"
                                        class="btn btn-link btn-sm p-0 text-decoration-none"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#{{ $overviewFullId }}"
                                        aria-expanded="false"
                                        aria-controls="{{ $overviewFullId }}"
                                    >
                                        View more
                                    </button>
                                @endif
                            </div>

                            @if(! empty($structuredDescription['highlights']))
                                <div class="mb-3">
                                    <h3 class="h6 text-uppercase text-muted mb-2">Property Highlights</h3>
                                    <ul class="mb-0 ps-3 small text-secondary">
                                        @foreach($structuredDescription['highlights'] as $highlight)
                                            <li>{{ $highlight }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(! empty($structuredDescription['amenities']))
                                <div class="mb-3">
                                    <h3 class="h6 text-uppercase text-muted mb-2">Amenities / Facilities</h3>
                                    <ul class="mb-0 ps-3 small text-secondary">
                                        @foreach($structuredDescription['amenities'] as $amenity)
                                            <li>{{ $amenity }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(! empty($structuredDescription['location_summary']))
                                <div>
                                    <h3 class="h6 text-uppercase text-muted mb-2">Location Summary</h3>
                                    <ul class="mb-0 ps-3 small text-secondary">
                                        @foreach($structuredDescription['location_summary'] as $locationLine)
                                            <li>{{ $locationLine }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card shadow-sm mb-3" id="request-site-visit">
                <div class="card-header">Contact / Request Site Visit</div>
                <div class="card-body">
                    @auth
                        @if(auth()->user()->role === 'buyer')
                            <form id="siteVisitForm" method="POST" action="{{ route('listings.request-visit', $listing) }}" class="row g-2">
                                @csrf
                                <div class="col-12">
                                    <textarea class="form-control" name="message" rows="3" placeholder="Your message or question (optional)">{{ old('message') }}</textarea>
                                </div>
                                <div class="col-12 form-check form-switch my-1">
                                    <input class="form-check-input" type="checkbox" role="switch" id="scheduleVisitToggle" @if(old('requested_at')) checked @endif>
                                    <label class="form-check-label" for="scheduleVisitToggle">Schedule a specific site visit</label>
                                </div>
                                <div id="scheduleSection" class="col-12 {{ old('requested_at') ? '' : 'd-none' }}">
                                    <label class="form-label">Preferred Date &amp; Time</label>
                                    <input id="requested_at" class="form-control" type="datetime-local" name="requested_at" value="{{ old('requested_at') }}" step="900">
                                    <div class="form-text">Working hours: 8:00 AM to 5:00 PM (last start time: 4:00 PM).</div>
                                </div>
                                <div class="col-12 d-grid">
                                    <button id="contactSubmitBtn" class="btn btn-primary">Send Inquiry</button>
                                </div>
                                <div class="col-12">
                                    <div class="form-text">After submitting, you will continue in a live chat thread with the handling agent.</div>
                                </div>
                            </form>
                        @else
                            <div class="alert alert-warning mb-0">Only buyer accounts can submit inquiries and appointment requests.</div>
                        @endif
                    @else
                        <a class="btn btn-primary w-100" href="{{ route('listings.request-visit.start', $listing) }}">Sign in to Contact</a>
                        <div class="form-text text-center mt-2">You will be asked to log in first.</div>
                    @endauth
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">Listing Photos</div>
                <div class="card-body">
                    @if($images->count() > 1)
                        <div id="{{ $carouselId }}" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-indicators">
                                @foreach($images as $index => $image)
                                    <button
                                        type="button"
                                        data-bs-target="#{{ $carouselId }}"
                                        data-bs-slide-to="{{ $index }}"
                                        class="{{ $index === 0 ? 'active' : '' }}"
                                        @if($index === 0) aria-current="true" @endif
                                        aria-label="Slide {{ $index + 1 }}"
                                    ></button>
                                @endforeach
                            </div>

                            <div class="carousel-inner rounded">
                                @foreach($images as $index => $image)
                                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                        <img
                                            src="{{ $image->image_url }}"
                                            class="d-block w-100"
                                            alt="Listing image {{ $index + 1 }}"
                                            style="height: 300px; object-fit: cover;"
                                        >
                                    </div>
                                @endforeach
                            </div>

                            <button class="carousel-control-prev" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    @elseif($images->count() === 1)
                        <img src="{{ $images->first()->image_url }}" class="img-fluid rounded" alt="Listing image" style="height: 300px; width: 100%; object-fit: cover;">
                    @else
                        <div class="alert alert-light border mb-0">No images uploaded for this listing yet.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <section class="mt-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <h2 class="h4 mb-1">Available Units</h2>
                @if($isProjectPosting)
                    <p class="text-muted mb-0">Other active listings from the same project.</p>
                @else
                    <p class="text-muted mb-0">Other active listings from the same agent.</p>
                @endif
            </div>
            <span class="badge text-bg-light border">{{ $availableUnits->count() }} unit(s)</span>
        </div>

        @if($availableUnits->isNotEmpty())
            <div class="row g-3">
                @foreach($availableUnits as $unit)
                    @php
                        $unitIsSaved = in_array($unit->id, $savedListingIds ?? [], true);
                        $unitSeller = $unit->listing_type === 'exclusive'
                            ? ($unit->owner?->name ?? 'Unknown agent')
                            : ($unit->project?->broker?->name ?? 'Unassigned broker');
                        $unitStatusLabel = $unit->status === 'unavailable' ? 'Not Available' : ucfirst($unit->status);
                    @endphp

                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <img src="{{ $unit->images->first()?->image_url ?? asset('images/hero.jpg') }}" class="card-img-top" alt="{{ $unit->title }}" style="height: 190px; object-fit: cover;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="d-flex gap-1 flex-wrap">
                                        <span class="badge text-bg-info">{{ ucwords(str_replace('_', ' ', $unit->property_type ?? 'lot')) }}</span>
                                        <span class="badge text-bg-secondary">{{ ucfirst($unit->listing_type) }}</span>
                                    </div>
                                    <span class="small text-muted">{{ $unitStatusLabel }}</span>
                                </div>

                                <h3 class="h6 mb-2">{{ $unit->title }}</h3>
                                <p class="fw-semibold text-primary mb-2">
                                    ₱{{ number_format($unit->price, 2) }}
                                    @if(($unit->listing_type ?? null) === 'shared' && ! is_null($unit->price_max) && (float) $unit->price_max > (float) $unit->price)
                                        - ₱{{ number_format((float) $unit->price_max, 2) }}
                                    @endif
                                </p>

                                <ul class="list-unstyled small text-muted mb-0">
                                    @if(! is_null($unit->lot_area_sqm))
                                        <li>Lot Area: {{ number_format((float) $unit->lot_area_sqm, 2) }} sqm</li>
                                    @endif
                                    @if(! is_null($unit->floor_area_sqm))
                                        <li>Floor Area: {{ number_format((float) $unit->floor_area_sqm, 2) }} sqm</li>
                                    @endif
                                    @if(! is_null($unit->bedrooms))
                                        <li>Bedrooms: {{ $unit->bedrooms }}</li>
                                    @endif
                                    @if(! is_null($unit->bathrooms))
                                        <li>Bathrooms: {{ $unit->bathrooms }}</li>
                                    @endif
                                    <li>Seller: {{ $unitSeller }}</li>
                                </ul>
                            </div>
                            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 d-flex gap-2 align-items-center">
                                <a href="{{ route('listings.show', $unit) }}" class="btn btn-outline-primary btn-sm flex-grow-1">Details</a>
                                <a href="{{ route('listings.show', $unit) }}#request-site-visit" class="btn btn-primary btn-sm flex-grow-1">Contact</a>
                                @auth
                                    @if(auth()->user()->role === 'buyer')
                                        @if($unitIsSaved)
                                            <form method="POST" action="{{ route('buyer.wishlist.destroy', $unit) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Remove from wishlist" title="Remove from wishlist">
                                                    <i class="bi bi-heart-fill"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('buyer.wishlist.store', $unit) }}">
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
        @else
            <div class="alert alert-light border mb-0">
                No other active units are available for this {{ $isProjectPosting ? 'project' : 'agent' }} right now.
            </div>
        @endif
    </section>

    <section class="mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h4 mb-1">Frequently Asked Questions</h2>
                <p class="text-muted mb-0">Quick answers generated from this listing's details.</p>
            </div>
        </div>

        <div class="accordion" id="listingFaqAccordion-{{ $listing->id }}">
            @forelse($faqItems as $index => $faq)
                @php
                    $faqHeadingId = 'faqHeading-'.$listing->id.'-'.$index;
                    $faqCollapseId = 'faqCollapse-'.$listing->id.'-'.$index;
                @endphp

                <div class="accordion-item">
                    <h2 class="accordion-header" id="{{ $faqHeadingId }}">
                        <button
                            class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#{{ $faqCollapseId }}"
                            aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                            aria-controls="{{ $faqCollapseId }}"
                        >
                            {{ $faq['question'] }}
                        </button>
                    </h2>
                    <div
                        id="{{ $faqCollapseId }}"
                        class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                        aria-labelledby="{{ $faqHeadingId }}"
                        data-bs-parent="#listingFaqAccordion-{{ $listing->id }}"
                    >
                        <div class="accordion-body">
                            {{ $faq['answer'] }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-light border mb-0">No FAQ entries available for this listing yet.</div>
            @endforelse
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.BayLotMap.initViewMap(
                'listingMap',
                @json($lat ?? 10.6713),
                @json($lng ?? 122.9556),
                @json($geofenceGeoJson),
                @json(config('services.google_maps.key'))
            );

            const overviewCollapse = document.getElementById(@json($overviewFullId));
            const overviewPreview = document.getElementById(@json($overviewPreviewId));
            const overviewToggle = document.getElementById(@json($overviewToggleId));

            if (overviewCollapse && overviewPreview && overviewToggle) {
                overviewCollapse.addEventListener('show.bs.collapse', () => {
                    overviewPreview.classList.add('d-none');
                    overviewToggle.textContent = 'View less';
                });

                overviewCollapse.addEventListener('hide.bs.collapse', () => {
                    overviewPreview.classList.remove('d-none');
                    overviewToggle.textContent = 'View more';
                });
            }

            const visitForm = document.getElementById('siteVisitForm');
            const scheduleToggle = document.getElementById('scheduleVisitToggle');
            const scheduleSection = document.getElementById('scheduleSection');
            const scheduleInput = document.getElementById('requested_at');
            const submitBtn = document.getElementById('contactSubmitBtn');

            if (!visitForm || !scheduleToggle || !scheduleSection || !submitBtn) {
                return;
            }

            const updateScheduleVisibility = () => {
                const isScheduling = scheduleToggle.checked;
                scheduleSection.classList.toggle('d-none', !isScheduling);
                submitBtn.textContent = isScheduling ? 'Request Site Visit' : 'Send Inquiry';

                if (!isScheduling && scheduleInput) {
                    scheduleInput.value = '';
                    scheduleInput.setCustomValidity('');
                }
            };

            updateScheduleVisibility();
            scheduleToggle.addEventListener('change', updateScheduleVisibility);

            const validateWorkingHours = () => {
                if (!scheduleInput || !scheduleInput.value || !scheduleToggle.checked) {
                    if (scheduleInput) {
                        scheduleInput.setCustomValidity('');
                    }
                    return true;
                }

                const selectedAt = new Date(scheduleInput.value);
                if (Number.isNaN(selectedAt.getTime())) {
                    scheduleInput.setCustomValidity('');
                    return true;
                }

                const totalMinutes = (selectedAt.getHours() * 60) + selectedAt.getMinutes();
                const isWithinWorkingHours = totalMinutes >= 480 && totalMinutes <= 960;

                scheduleInput.setCustomValidity(
                    isWithinWorkingHours ? '' : 'Please choose a start time between 8:00 AM and 4:00 PM only.'
                );

                return isWithinWorkingHours;
            };

            scheduleInput?.addEventListener('change', validateWorkingHours);

            visitForm.addEventListener('submit', (event) => {
                if (scheduleToggle.checked && !validateWorkingHours()) {
                    event.preventDefault();
                    scheduleInput?.reportValidity();
                }
            });
        });
    </script>
</x-app-layout>
