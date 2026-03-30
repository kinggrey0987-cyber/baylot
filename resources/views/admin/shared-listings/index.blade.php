<x-app-layout>
    <div class="app-section-head">
        <div>
            <h1 class="app-section-title">Shared Listings</h1>
            <p class="app-section-subtitle">Create collective subdivision or housing listings for your brokerage.</p>
        </div>
    </div>

    <div class="alert alert-info mb-3">
        Step 2 workflow: choose a project, pin coordinates, upload photos, and fill in listing details so your assigned agents can claim leads.
    </div>

    <div class="card module-card mb-3">
        <div class="card-header">Create Collective Listing</div>
        <div class="card-body">
            <form id="sharedListingForm" method="POST" action="{{ route('broker.shared-listings.store') }}" class="row g-3 module-form" enctype="multipart/form-data">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label" for="title">Title</label>
                    <input id="title" class="form-control" name="title" value="{{ old('title') }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="price">Price From</label>
                    <input id="price" class="form-control" type="number" step="0.01" max="999999999999.99" name="price" value="{{ old('price') }}" required>
                    <div class="form-text">For lot postings, this is auto-calculated from lot area and price per sqm.</div>
                </div>
                <div id="priceMaxFieldWrapper" class="col-12 col-md-6">
                    <label class="form-label" for="price_max">Price To</label>
                    <input id="price_max" class="form-control" type="number" step="0.01" max="999999999999.99" name="price_max" value="{{ old('price_max') }}">
                    <div class="form-text">Optional max price for subdivision-style postings.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="property_type">Property Type</label>
                    <select id="property_type" class="form-select" name="property_type" required>
                        <option value="lot" @selected(old('property_type') === 'lot')>Lot</option>
                        <option value="house_and_lot" @selected(old('property_type') === 'house_and_lot')>House and Lot</option>
                        <option value="condo" @selected(old('property_type') === 'condo')>Condo</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="project_id">Project</label>
                    <select id="project_id" class="form-select" name="project_id" required>
                        <option value="">Select project</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" @selected(old('project_id', $selectedProjectId) === $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" class="form-select" name="status">
                        @foreach(['available','reserved','sold','unavailable'] as $status)
                            <option value="{{ $status }}" @selected(old('status', 'available') === $status)>{{ $status === 'unavailable' ? 'Not Available' : ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label" for="available_units">Available Units (houses/lots)</label>
                    <input id="available_units" class="form-control" type="number" step="1" min="0" max="1000000" name="available_units" value="{{ old('available_units') }}">
                    <div class="form-text">If this reaches 0, status is automatically set to not available.</div>
                </div>

                <div id="lotAreaFieldWrapper" class="col-12 col-md-6">
                    <label class="form-label" for="lot_area_sqm">Lot Area (sqm)</label>
                    <input id="lot_area_sqm" class="form-control" type="number" step="0.01" min="0.01" max="9999999999.99" name="lot_area_sqm" value="{{ old('lot_area_sqm') }}">
                </div>
                <div id="lotAreaMaxFieldWrapper" class="col-12 col-md-6">
                    <label class="form-label" for="lot_area_max_sqm">Lot Area Max (sqm)</label>
                    <input id="lot_area_max_sqm" class="form-control" type="number" step="0.01" min="0.01" max="9999999999.99" name="lot_area_max_sqm" value="{{ old('lot_area_max_sqm') }}">
                </div>
                <div id="floorAreaFieldWrapper" class="col-12 col-md-6">
                    <label class="form-label" for="floor_area_sqm">Floor Area (sqm)</label>
                    <input id="floor_area_sqm" class="form-control" type="number" step="0.01" min="0.01" max="9999999999.99" name="floor_area_sqm" value="{{ old('floor_area_sqm') }}">
                </div>
                <div id="pricePerSqmFieldWrapper" class="col-12 col-md-6">
                    <label class="form-label" for="price_per_sqm">Price per sqm</label>
                    <input id="price_per_sqm" class="form-control" type="number" step="0.01" min="0" max="999999999999.99" name="price_per_sqm" value="{{ old('price_per_sqm') }}">
                </div>

                <div id="propertyDetailsSection" class="col-12">
                    <label class="form-label">Optional Property Details</label>
                    <div class="row g-3">
                        <div class="col-12 col-md-2"><label class="form-label" for="bedrooms">Bedrooms Min</label><input id="bedrooms" class="form-control" type="number" step="1" min="0" name="bedrooms" value="{{ old('bedrooms') }}"></div>
                        <div class="col-12 col-md-2"><label class="form-label" for="bedrooms_max">Bedrooms Max</label><input id="bedrooms_max" class="form-control" type="number" step="1" min="0" name="bedrooms_max" value="{{ old('bedrooms_max') }}"></div>
                        <div class="col-12 col-md-2"><label class="form-label" for="bathrooms">Bathrooms Min</label><input id="bathrooms" class="form-control" type="number" step="1" min="0" name="bathrooms" value="{{ old('bathrooms') }}"></div>
                        <div class="col-12 col-md-2"><label class="form-label" for="bathrooms_max">Bathrooms Max</label><input id="bathrooms_max" class="form-control" type="number" step="1" min="0" name="bathrooms_max" value="{{ old('bathrooms_max') }}"></div>
                        <div id="floorsFieldWrapper" class="col-12 col-md-2"><label class="form-label" for="floors">Floors Min</label><input id="floors" class="form-control" type="number" step="1" min="0" name="floors" value="{{ old('floors') }}"></div>
                        <div id="floorsMaxFieldWrapper" class="col-12 col-md-2"><label class="form-label" for="floors_max">Floors Max</label><input id="floors_max" class="form-control" type="number" step="1" min="0" name="floors_max" value="{{ old('floors_max') }}"></div>
                    </div>
                    <div class="form-text">Use min and max values for bedrooms, bathrooms, and floors to represent available housing variants.</div>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label" for="lat">Latitude</label>
                    <input id="lat" class="form-control" type="number" step="0.000001" min="-90" max="90" name="lat" value="{{ old('lat', '10.500000') }}" required>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="lng">Longitude</label>
                    <input id="lng" class="form-control" type="number" step="0.000001" min="-180" max="180" name="lng" value="{{ old('lng', '123.100000') }}" required>
                </div>
                <div class="col-12">
                    <div id="coordinateValidationFeedback" class="form-text text-danger d-none"></div>
                </div>
                <div class="col-12">
                    <label class="form-label">Property Location</label>
                    <div id="pickerMap" class="map-canvas"></div>
                    <div class="form-text">Pin the location on the map or manually enter exact coordinates above.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="location_name">Location Name (OpenStreetMap)</label>
                    <input id="location_name" type="text" class="form-control" value="" readonly>
                </div>

                <div class="col-12">
                    <label class="form-label" for="images">Upload Listing Photos</label>
                    <div id="imageInputsContainer" class="d-grid gap-2">
                        <div class="input-group listing-image-row">
                            <input class="form-control listing-image-input" type="file" name="images[]" accept="image/png,image/jpeg,image/webp">
                            <button class="btn btn-outline-danger listing-image-remove" type="button" disabled>Remove</button>
                        </div>
                    </div>
                    <div class="form-text">Upload up to 10 photos (max 5MB each). A new file chooser appears after each selected photo.</div>
                </div>
                <input type="hidden" name="listing_type" value="shared">
                <div class="col-12 d-grid"><button id="listingSubmitButton" class="btn btn-primary">Create Listing</button></div>
            </form>
        </div>
    </div>

    <div class="card module-card">
        <div class="card-header">Shared Listings</div>
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('broker.shared-listings.index') }}" class="row g-2 module-form">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="filter_project_id">Filter by Project</label>
                    <select id="filter_project_id" class="form-select" name="project_id">
                        <option value="">All projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" @selected(request('project_id') === $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
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
                @if(request()->filled('property_type') || request()->filled('project_id'))
                    <div class="col-12 col-md-2 d-grid align-self-end">
                        <a href="{{ route('broker.shared-listings.index') }}" class="btn btn-outline-secondary">Clear</a>
                    </div>
                @endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table module-table mb-0">
                <thead><tr><th>Title</th><th>Project</th><th>Type / Details</th><th>Status</th><th>Price</th><th>Actions</th></tr></thead>
                <tbody>
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
                    <tr>
                        <td>{{ $listing->title }}</td>
                        <td>{{ $listing->project?->name }}</td>
                        <td>
                            <div>{{ ucwords(str_replace('_', ' ', $listing->property_type ?? 'lot')) }}</div>
                            @php
                                $tableDetails = [];

                                if (! is_null($listing->lot_area_sqm)) {
                                    $lotRange = is_null($listing->lot_area_max_sqm)
                                        ? number_format((float) $listing->lot_area_sqm, 2)
                                        : number_format((float) $listing->lot_area_sqm, 2).'-'.number_format((float) $listing->lot_area_max_sqm, 2);
                                    $tableDetails[] = $lotRange.' sqm lot';
                                }

                                if (($listing->property_type ?? 'lot') === 'house_and_lot' && ! is_null($listing->floor_area_sqm)) {
                                    $tableDetails[] = number_format((float) $listing->floor_area_sqm, 2).' sqm floor';
                                }

                                if (($listing->property_type ?? 'lot') === 'lot' && ! is_null($listing->price_per_sqm)) {
                                    $tableDetails[] = '₱'.number_format((float) $listing->price_per_sqm, 2).'/sqm';
                                }

                                if (! is_null($listing->available_units)) {
                                    $tableDetails[] = $listing->available_units.' units available';
                                }

                                if (! is_null($listing->bedrooms)) {
                                    $bedroomRange = is_null($listing->bedrooms_max)
                                        ? $listing->bedrooms.'+'
                                        : $listing->bedrooms.'-'.$listing->bedrooms_max;
                                    $tableDetails[] = $bedroomRange.' BR';
                                }

                                if (! is_null($listing->bathrooms)) {
                                    $bathRange = is_null($listing->bathrooms_max)
                                        ? (string) $listing->bathrooms
                                        : $listing->bathrooms.'-'.$listing->bathrooms_max;
                                    $tableDetails[] = $bathRange.' BA';
                                }

                                if (($listing->property_type ?? 'lot') === 'house_and_lot' && ! is_null($listing->floors)) {
                                    $floorRange = is_null($listing->floors_max)
                                        ? (string) $listing->floors
                                        : $listing->floors.'-'.$listing->floors_max;
                                    $tableDetails[] = $floorRange.' Floors';
                                }
                            @endphp
                            @if(! empty($tableDetails))
                                <div class="text-muted small">{{ implode(' • ', $tableDetails) }}</div>
                            @endif
                        </td>
                        <td><span class="badge {{ $statusClass }} status-badge">{{ $statusLabel }}</span></td>
                        <td>
                            ₱{{ number_format($listing->price, 2) }}
                            @if(! is_null($listing->price_max) && (float) $listing->price_max > (float) $listing->price)
                                - ₱{{ number_format((float) $listing->price_max, 2) }}
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('broker.shared-listings.edit', $listing) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('broker.shared-listings.destroy', $listing) }}" onsubmit="return confirm('Delete shared listing?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty">No shared listings found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $listings->links() }}</div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const propertyTypeSelect = document.getElementById('property_type');
            const priceInput = document.getElementById('price');
            const priceMaxFieldWrapper = document.getElementById('priceMaxFieldWrapper');
            const priceMaxInput = document.getElementById('price_max');
            const lotAreaFieldWrapper = document.getElementById('lotAreaFieldWrapper');
            const lotAreaInput = document.getElementById('lot_area_sqm');
            const lotAreaMaxFieldWrapper = document.getElementById('lotAreaMaxFieldWrapper');
            const lotAreaMaxInput = document.getElementById('lot_area_max_sqm');
            const floorAreaFieldWrapper = document.getElementById('floorAreaFieldWrapper');
            const floorAreaInput = document.getElementById('floor_area_sqm');
            const pricePerSqmFieldWrapper = document.getElementById('pricePerSqmFieldWrapper');
            const pricePerSqmInput = document.getElementById('price_per_sqm');
            const availableUnitsInput = document.getElementById('available_units');
            const propertyDetailsSection = document.getElementById('propertyDetailsSection');
            const bedroomsInput = document.getElementById('bedrooms');
            const bedroomsMaxInput = document.getElementById('bedrooms_max');
            const bathroomsInput = document.getElementById('bathrooms');
            const bathroomsMaxInput = document.getElementById('bathrooms_max');
            const floorsFieldWrapper = document.getElementById('floorsFieldWrapper');
            const floorsInput = document.getElementById('floors');
            const floorsMaxFieldWrapper = document.getElementById('floorsMaxFieldWrapper');
            const floorsMaxInput = document.getElementById('floors_max');
            const listingForm = document.getElementById('sharedListingForm');
            const latInput = document.getElementById('lat');
            const lngInput = document.getElementById('lng');
            const listingSubmitButton = document.getElementById('listingSubmitButton');

            const updateComputedLotPrice = () => {
                if (!propertyTypeSelect || !priceInput || propertyTypeSelect.value !== 'lot') {
                    return;
                }

                const lotArea = Number.parseFloat(lotAreaInput?.value ?? '');
                const lotAreaMax = Number.parseFloat(lotAreaMaxInput?.value ?? '');
                const pricePerSqm = Number.parseFloat(pricePerSqmInput?.value ?? '');

                if (Number.isFinite(lotArea) && lotArea > 0 && Number.isFinite(pricePerSqm) && pricePerSqm >= 0) {
                    priceInput.value = (lotArea * pricePerSqm).toFixed(2);
                } else {
                    priceInput.value = '';
                }

                if (priceMaxInput) {
                    if (Number.isFinite(lotAreaMax) && lotAreaMax > 0 && Number.isFinite(pricePerSqm) && pricePerSqm >= 0) {
                        priceMaxInput.value = (lotAreaMax * pricePerSqm).toFixed(2);
                    } else {
                        priceMaxInput.value = '';
                    }
                }

                updateListingSubmitState();
            };

            const updateListingSubmitState = () => {
                if (!latInput || !lngInput || !listingSubmitButton) {
                    return;
                }

                const hasLat = latInput.value.trim() !== '';
                const hasLng = lngInput.value.trim() !== '';
                const hasValidPrice = !priceInput || (priceInput.value.trim() !== '' && priceInput.checkValidity());
                const hasValidPriceMax = !priceMaxInput || priceMaxInput.value.trim() === '' || priceMaxInput.checkValidity();
                const hasValidUnits = !availableUnitsInput || availableUnitsInput.value.trim() === '' || availableUnitsInput.checkValidity();
                const canSubmit = hasLat && hasLng && latInput.checkValidity() && lngInput.checkValidity() && hasValidPrice && hasValidPriceMax && hasValidUnits;

                listingSubmitButton.disabled = !canSubmit;
            };

            const togglePropertyDetailFields = () => {
                if (!propertyTypeSelect) {
                    return;
                }

                const selectedType = propertyTypeSelect.value;
                const showDetails = selectedType === 'house_and_lot' || selectedType === 'condo';
                const showFloors = selectedType === 'house_and_lot';
                const showLotArea = selectedType === 'lot' || selectedType === 'house_and_lot';
                const showLotAreaMax = showLotArea;
                const showFloorArea = selectedType === 'house_and_lot';
                const showPricePerSqm = selectedType === 'lot';

                propertyDetailsSection?.classList.toggle('d-none', !showDetails);
                floorsFieldWrapper?.classList.toggle('d-none', !showFloors);
                floorsMaxFieldWrapper?.classList.toggle('d-none', !showFloors);
                lotAreaFieldWrapper?.classList.toggle('d-none', !showLotArea);
                lotAreaMaxFieldWrapper?.classList.toggle('d-none', !showLotAreaMax);
                floorAreaFieldWrapper?.classList.toggle('d-none', !showFloorArea);
                pricePerSqmFieldWrapper?.classList.toggle('d-none', !showPricePerSqm);
                priceMaxFieldWrapper?.classList.toggle('d-none', false);

                if (bedroomsInput) {
                    bedroomsInput.disabled = !showDetails;
                }

                if (bedroomsMaxInput) {
                    bedroomsMaxInput.disabled = !showDetails;
                }

                if (bathroomsInput) {
                    bathroomsInput.disabled = !showDetails;
                }

                if (bathroomsMaxInput) {
                    bathroomsMaxInput.disabled = !showDetails;
                }

                if (floorsInput) {
                    floorsInput.disabled = !showFloors;
                }

                if (floorsMaxInput) {
                    floorsMaxInput.disabled = !showFloors;
                }

                if (lotAreaInput) {
                    lotAreaInput.disabled = !showLotArea;
                }

                if (lotAreaMaxInput) {
                    lotAreaMaxInput.disabled = !showLotAreaMax;
                }

                if (floorAreaInput) {
                    floorAreaInput.disabled = !showFloorArea;
                }

                if (pricePerSqmInput) {
                    pricePerSqmInput.disabled = !showPricePerSqm;
                }

                if (priceInput) {
                    priceInput.readOnly = showPricePerSqm;
                }

                if (priceMaxInput) {
                    priceMaxInput.readOnly = showPricePerSqm;
                }

                if (showPricePerSqm) {
                    updateComputedLotPrice();
                }

                updateListingSubmitState();
            };

            if (propertyTypeSelect) {
                propertyTypeSelect.addEventListener('change', togglePropertyDetailFields);
                togglePropertyDetailFields();
            }

            if (lotAreaInput) {
                lotAreaInput.addEventListener('input', updateComputedLotPrice);
                lotAreaInput.addEventListener('change', updateComputedLotPrice);
            }

            if (lotAreaMaxInput) {
                lotAreaMaxInput.addEventListener('input', updateComputedLotPrice);
                lotAreaMaxInput.addEventListener('change', updateComputedLotPrice);
            }

            if (pricePerSqmInput) {
                pricePerSqmInput.addEventListener('input', updateComputedLotPrice);
                pricePerSqmInput.addEventListener('change', updateComputedLotPrice);
            }

            if (priceInput) {
                priceInput.addEventListener('input', updateListingSubmitState);
                priceInput.addEventListener('change', updateListingSubmitState);
            }

            if (priceMaxInput) {
                priceMaxInput.addEventListener('input', updateListingSubmitState);
                priceMaxInput.addEventListener('change', updateListingSubmitState);
            }

            if (availableUnitsInput) {
                availableUnitsInput.addEventListener('input', updateListingSubmitState);
                availableUnitsInput.addEventListener('change', updateListingSubmitState);
            }

            const mapElement = document.getElementById('pickerMap');
            let mapInitAttempts = 0;

            const tryInitMap = () => {
                if (!mapElement || mapElement.dataset.initialized === '1') {
                    return;
                }

                if (!window.BayLotMap || typeof window.BayLotMap.initPicker !== 'function') {
                    mapInitAttempts += 1;
                    if (mapInitAttempts < 15) {
                        window.setTimeout(tryInitMap, 120);
                    }
                    return;
                }

                try {
                    window.BayLotMap.initPicker('pickerMap', 'lat', 'lng', @json($geofenceGeoJson ?? null), 'location_name', 'coordinateValidationFeedback');
                    mapElement.dataset.initialized = '1';
                    updateListingSubmitState();
                } catch (_error) {
                    mapInitAttempts += 1;
                    if (mapInitAttempts < 15) {
                        window.setTimeout(tryInitMap, 120);
                    }
                }
            };

            if (latInput && lngInput) {
                latInput.addEventListener('input', updateListingSubmitState);
                lngInput.addEventListener('input', updateListingSubmitState);
                latInput.addEventListener('change', updateListingSubmitState);
                lngInput.addEventListener('change', updateListingSubmitState);
                latInput.addEventListener('blur', updateListingSubmitState);
                lngInput.addEventListener('blur', updateListingSubmitState);
                latInput.addEventListener('baylot:coordinate-validity-changed', updateListingSubmitState);
                lngInput.addEventListener('baylot:coordinate-validity-changed', updateListingSubmitState);
            }

            if (listingForm) {
                listingForm.addEventListener('submit', (event) => {
                    updateListingSubmitState();

                    if (listingSubmitButton && listingSubmitButton.disabled) {
                        event.preventDefault();
                        latInput?.reportValidity();
                    }
                });
            }

            updateListingSubmitState();
            tryInitMap();

            const maxImages = 10;
            const imageInputsContainer = document.getElementById('imageInputsContainer');

            if (!imageInputsContainer) {
                return;
            }

            const hasFile = (input) => input && input.files && input.files.length > 0;

            const createImageRow = () => {
                const row = document.createElement('div');
                row.className = 'input-group listing-image-row';

                const input = document.createElement('input');
                input.type = 'file';
                input.name = 'images[]';
                input.accept = 'image/png,image/jpeg,image/webp';
                input.className = 'form-control listing-image-input';

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'btn btn-outline-danger listing-image-remove';
                removeButton.textContent = 'Remove';

                input.addEventListener('change', normalizeImageRows);
                removeButton.addEventListener('click', () => {
                    row.remove();
                    normalizeImageRows();
                });

                row.append(input, removeButton);
                return row;
            };

            const normalizeImageRows = () => {
                let rows = Array.from(imageInputsContainer.querySelectorAll('.listing-image-row'));

                if (rows.length === 0) {
                    const row = createImageRow();
                    imageInputsContainer.appendChild(row);
                    rows = [row];
                }

                const emptyRows = rows.filter((row) => {
                    const input = row.querySelector('.listing-image-input');
                    return !hasFile(input);
                });

                if (emptyRows.length > 1) {
                    emptyRows.slice(0, -1).forEach((row) => row.remove());
                    rows = Array.from(imageInputsContainer.querySelectorAll('.listing-image-row'));
                }

                const filledCount = rows.filter((row) => hasFile(row.querySelector('.listing-image-input'))).length;
                const hasEmptyTail = rows.some((row) => !hasFile(row.querySelector('.listing-image-input')));

                if (!hasEmptyTail && filledCount < maxImages) {
                    imageInputsContainer.appendChild(createImageRow());
                    rows = Array.from(imageInputsContainer.querySelectorAll('.listing-image-row'));
                }

                rows.forEach((row) => {
                    const input = row.querySelector('.listing-image-input');
                    const removeButton = row.querySelector('.listing-image-remove');

                    if (!removeButton) {
                        return;
                    }

                    removeButton.disabled = !hasFile(input) && rows.length === 1;
                });
            };

            imageInputsContainer.querySelectorAll('.listing-image-row').forEach((row) => {
                const input = row.querySelector('.listing-image-input');
                const removeButton = row.querySelector('.listing-image-remove');

                if (input) {
                    input.addEventListener('change', normalizeImageRows);
                }

                if (removeButton) {
                    removeButton.addEventListener('click', () => {
                        row.remove();
                        normalizeImageRows();
                    });
                }
            });

            normalizeImageRows();
        });
    </script>
</x-app-layout>
