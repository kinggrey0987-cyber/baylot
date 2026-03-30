@php
    $isEdit = isset($listing);
    $action = $isEdit ? route('agent.listings.update', $listing) : route('agent.listings.store');
@endphp

<form id="listingForm" method="POST" action="{{ $action }}" class="card module-card" enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif
    @unless($isEdit)
        <input type="hidden" name="listing_type" value="exclusive">
        <input type="hidden" name="owner_agent_id" value="{{ auth()->id() }}">
    @endunless
    <div class="card-body row g-3 module-form">
        <div class="col-12"><label class="form-label" for="title">Title</label><input id="title" class="form-control" name="title" value="{{ old('title', $listing->title ?? '') }}" required></div>
        <div class="col-12"><label class="form-label" for="description">Description</label><textarea id="description" class="form-control" name="description" rows="3">{{ old('description', $listing->description ?? '') }}</textarea></div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="property_type">Property Type</label>
            <select id="property_type" class="form-select" name="property_type" required>
                @foreach(['lot' => 'Lot', 'house_and_lot' => 'House and Lot', 'condo' => 'Condo'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('property_type', $listing->property_type ?? 'lot') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="price">Price</label>
            <input id="price" class="form-control" type="number" step="0.01" max="999999999999.99" name="price" value="{{ old('price', $listing->price ?? '') }}" required>
            <div class="form-text">For lot postings, this is auto-calculated from lot area and price per sqm.</div>
        </div>
        @if($isEdit)
            <div class="col-12 col-md-6">
                <label class="form-label" for="status">Status</label>
                <select id="status" class="form-select" name="status">
                    @foreach(['available','sold','unavailable'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $listing->status ?? 'available')===$status)>{{ $status === 'unavailable' ? 'Not Available' : ucfirst($status) }}</option>
                    @endforeach
                </select>
                <div class="form-text">Reserved is set automatically when an appointment is approved and down payment is recorded.</div>
            </div>
        @else
            <input type="hidden" name="status" value="available">
            <div class="col-12 col-md-6">
                <label class="form-label">Status</label>
                <input type="text" class="form-control" value="Available" disabled>
                <div class="form-text">New listings are automatically posted as available.</div>
            </div>
        @endif

        <div id="lotAreaFieldWrapper" class="col-12 col-md-6">
            <label class="form-label" for="lot_area_sqm">Lot Area (sqm)</label>
            <input id="lot_area_sqm" class="form-control" type="number" step="0.01" min="0.01" max="9999999999.99" name="lot_area_sqm" value="{{ old('lot_area_sqm', $listing->lot_area_sqm ?? '') }}">
        </div>
        <div id="floorAreaFieldWrapper" class="col-12 col-md-6">
            <label class="form-label" for="floor_area_sqm">Floor Area (sqm)</label>
            <input id="floor_area_sqm" class="form-control" type="number" step="0.01" min="0.01" max="9999999999.99" name="floor_area_sqm" value="{{ old('floor_area_sqm', $listing->floor_area_sqm ?? '') }}">
        </div>
        <div id="pricePerSqmFieldWrapper" class="col-12 col-md-6">
            <label class="form-label" for="price_per_sqm">Price per sqm</label>
            <input id="price_per_sqm" class="form-control" type="number" step="0.01" min="0" max="999999999999.99" name="price_per_sqm" value="{{ old('price_per_sqm', $listing->price_per_sqm ?? '') }}">
        </div>

        <div id="propertyDetailsSection" class="col-12">
            <label class="form-label">Optional Property Details</label>
            <div class="row g-3">
                <div class="col-12 col-md-4"><label class="form-label" for="bedrooms">Bedrooms</label><input id="bedrooms" class="form-control" type="number" step="1" min="0" name="bedrooms" value="{{ old('bedrooms', $listing->bedrooms ?? '') }}"></div>
                <div class="col-12 col-md-4"><label class="form-label" for="bathrooms">Bathrooms</label><input id="bathrooms" class="form-control" type="number" step="1" min="0" name="bathrooms" value="{{ old('bathrooms', $listing->bathrooms ?? '') }}"></div>
                <div id="floorsFieldWrapper" class="col-12 col-md-4"><label class="form-label" for="floors">Floors</label><input id="floors" class="form-control" type="number" step="1" min="0" name="floors" value="{{ old('floors', $listing->floors ?? '') }}"></div>
            </div>
            <div class="form-text">Provide exact values for bedrooms, bathrooms, and floors when applicable.</div>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="lat">Latitude</label>
            <input id="lat" class="form-control" type="number" step="0.000001" min="-90" max="90" name="lat" value="{{ old('lat', $lat ?? '10.500000') }}" required>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="lng">Longitude</label>
            <input id="lng" class="form-control" type="number" step="0.000001" min="-180" max="180" name="lng" value="{{ old('lng', $lng ?? '123.100000') }}" required>
        </div>
        <div class="col-12">
            <div id="coordinateValidationFeedback" class="form-text text-danger d-none"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Property Location</label>
            <div id="pickerMap" class="map-canvas"></div>
            <div class="form-text">Pin the location on the map or manually enter the exact latitude and longitude above.</div>
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
            <div class="form-text">Upload up to 10 photos (max 5MB each). A new file chooser appears after each selected photo.{{ $isEdit ? ' New photos will be added alongside existing ones.' : '' }}</div>
        </div>

        @if ($isEdit && $listing->images->isNotEmpty())
            <div class="col-12">
                <label class="form-label">Current Photos</label>
                <div class="row g-2" id="existingImagesContainer">
                    @foreach ($listing->images as $image)
                        <div class="col-6 col-md-3 existing-image-item" id="existing-image-{{ $image->id }}">
                            <div class="card h-100">
                                <img src="{{ $image->image_url }}" alt="Listing photo" class="existing-photo-thumb card-img-top" style="aspect-ratio:4/3;object-fit:cover;">
                                <div class="card-body p-2">
                                    <div class="d-flex gap-1 existing-photo-actions">
                                        <label class="btn btn-sm btn-outline-secondary flex-fill mb-0" title="Replace this photo">
                                            Change
                                            <input type="file" name="replace_images[{{ $image->id }}]" accept="image/png,image/jpeg,image/webp" class="d-none replace-image-input" data-image-id="{{ $image->id }}" data-original-src="{{ $image->image_url }}">
                                        </label>
                                        <button type="button" class="btn btn-sm btn-outline-danger flex-fill remove-existing-image" data-image-id="{{ $image->id }}">Remove</button>
                                    </div>
                                    <input type="hidden" name="delete_images[]" value="{{ $image->id }}" class="delete-image-input" disabled>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="form-text">Use <strong>Change</strong> to replace a photo or <strong>Remove</strong> to delete it. Removed photos can be undone before saving.</div>
            </div>
        @endif
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-2">
        <a href="{{ route('agent.listings.index') }}" class="btn btn-outline-secondary">Back</a>
        <button id="listingSubmitButton" class="btn btn-primary" type="submit">Save Listing</button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const propertyTypeSelect = document.getElementById('property_type');
        const priceInput = document.getElementById('price');
        const lotAreaFieldWrapper = document.getElementById('lotAreaFieldWrapper');
        const lotAreaInput = document.getElementById('lot_area_sqm');
        const floorAreaFieldWrapper = document.getElementById('floorAreaFieldWrapper');
        const floorAreaInput = document.getElementById('floor_area_sqm');
        const pricePerSqmFieldWrapper = document.getElementById('pricePerSqmFieldWrapper');
        const pricePerSqmInput = document.getElementById('price_per_sqm');
        const propertyDetailsSection = document.getElementById('propertyDetailsSection');
        const bedroomsInput = document.getElementById('bedrooms');
        const bathroomsInput = document.getElementById('bathrooms');
        const floorsFieldWrapper = document.getElementById('floorsFieldWrapper');
        const floorsInput = document.getElementById('floors');
        const listingForm = document.getElementById('listingForm');
        const latInput = document.getElementById('lat');
        const lngInput = document.getElementById('lng');
        const listingSubmitButton = document.getElementById('listingSubmitButton');

        const updateComputedLotPrice = () => {
            if (!propertyTypeSelect || !priceInput || propertyTypeSelect.value !== 'lot') {
                return;
            }

            const lotArea = Number.parseFloat(lotAreaInput?.value ?? '');
            const pricePerSqm = Number.parseFloat(pricePerSqmInput?.value ?? '');

            if (Number.isFinite(lotArea) && lotArea > 0 && Number.isFinite(pricePerSqm) && pricePerSqm >= 0) {
                priceInput.value = (lotArea * pricePerSqm).toFixed(2);
            } else {
                priceInput.value = '';
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
            const canSubmit = hasLat && hasLng && latInput.checkValidity() && lngInput.checkValidity() && hasValidPrice;

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
            const showFloorArea = selectedType === 'house_and_lot';
            const showPricePerSqm = selectedType === 'lot';

            propertyDetailsSection?.classList.toggle('d-none', !showDetails);
            floorsFieldWrapper?.classList.toggle('d-none', !showFloors);
            lotAreaFieldWrapper?.classList.toggle('d-none', !showLotArea);
            floorAreaFieldWrapper?.classList.toggle('d-none', !showFloorArea);
            pricePerSqmFieldWrapper?.classList.toggle('d-none', !showPricePerSqm);

            if (bedroomsInput) {
                bedroomsInput.disabled = !showDetails;
            }

            if (bathroomsInput) {
                bathroomsInput.disabled = !showDetails;
            }

            if (floorsInput) {
                floorsInput.disabled = !showFloors;
            }

            if (lotAreaInput) {
                lotAreaInput.disabled = !showLotArea;
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

        if (pricePerSqmInput) {
            pricePerSqmInput.addEventListener('input', updateComputedLotPrice);
            pricePerSqmInput.addEventListener('change', updateComputedLotPrice);
        }

        if (priceInput) {
            priceInput.addEventListener('input', updateListingSubmitState);
            priceInput.addEventListener('change', updateListingSubmitState);
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

        // Existing photo remove / change interactions
        document.querySelectorAll('.remove-existing-image').forEach((btn) => {
            btn.addEventListener('click', () => {
                const imageId = btn.dataset.imageId;
                const container = document.getElementById(`existing-image-${imageId}`);
                if (!container) return;

                const deleteInput = container.querySelector('.delete-image-input');
                const replaceInput = container.querySelector('.replace-image-input');
                const thumb = container.querySelector('.existing-photo-thumb');
                const actionsDiv = container.querySelector('.existing-photo-actions');

                if (deleteInput) deleteInput.disabled = false;
                if (replaceInput) { replaceInput.disabled = true; replaceInput.value = ''; }
                container.style.opacity = '0.45';
                if (actionsDiv) actionsDiv.style.display = 'none';

                const undoBtn = document.createElement('button');
                undoBtn.type = 'button';
                undoBtn.className = 'btn btn-sm btn-outline-success w-100 undo-remove-image';
                undoBtn.textContent = 'Undo Remove';
                undoBtn.addEventListener('click', () => {
                    if (deleteInput) deleteInput.disabled = true;
                    if (replaceInput) replaceInput.disabled = false;
                    container.style.opacity = '';
                    if (actionsDiv) actionsDiv.style.display = '';
                    undoBtn.remove();
                    if (thumb) thumb.src = thumb.dataset.originalSrc || thumb.src;
                });
                container.querySelector('.card-body').appendChild(undoBtn);
            });
        });

        document.querySelectorAll('.replace-image-input').forEach((input) => {
            input.addEventListener('change', () => {
                const imageId = input.dataset.imageId;
                const container = document.getElementById(`existing-image-${imageId}`);
                if (!container) return;

                const thumb = container.querySelector('.existing-photo-thumb');
                if (input.files && input.files[0] && thumb) {
                    const reader = new FileReader();
                    reader.onload = (e) => { thumb.src = e.target.result; };
                    reader.readAsDataURL(input.files[0]);
                }
            });
        });
    });
</script>
