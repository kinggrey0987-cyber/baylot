<x-app-layout>
    <div class="app-section-head">
        <div>
            <h1 class="app-section-title">Edit Exclusive Listing</h1>
            <p class="app-section-subtitle">Update listing data and attach lot geometry.</p>
        </div>
    </div>

    @include('agent.listings.form', ['listing' => $listing, 'lat' => $lat, 'lng' => $lng])

    <div class="card module-card mt-3">
        <div class="card-header">Add Lot (GeoJSON)</div>
        <div class="card-body">
            <form id="lotGeoJsonForm" method="POST" action="{{ route('agent.listings.lots.store', $listing) }}" class="row g-3 module-form">
                @csrf
                <div class="col-12 col-md-4"><label class="form-label" for="lot_code">Lot code</label><input id="lot_code" class="form-control" name="lot_code" value="{{ old('lot_code') }}" required></div>
                <div class="col-12 col-md-4"><label class="form-label" for="area_sqm">Area (sqm)</label><input id="area_sqm" class="form-control" name="area_sqm" type="number" step="0.01" value="{{ old('area_sqm') }}"></div>
                <div class="col-12 col-md-4"><label class="form-label" for="lot_status">Status</label><select id="lot_status" class="form-select" name="status"><option value="available" @selected(old('status', 'available') === 'available')>Available</option><option value="reserved" @selected(old('status') === 'reserved')>Reserved</option><option value="sold" @selected(old('status') === 'sold')>Sold</option></select></div>
                <div class="col-12 col-md-6"><label class="form-label" for="lot_lat">Latitude (optional)</label><input id="lot_lat" class="form-control" name="lot_lat" type="number" step="0.000001" min="-90" max="90" placeholder="10.500000" value="{{ old('lot_lat') }}"></div>
                <div class="col-12 col-md-6"><label class="form-label" for="lot_lng">Longitude (optional)</label><input id="lot_lng" class="form-control" name="lot_lng" type="number" step="0.000001" min="-180" max="180" placeholder="123.100000" value="{{ old('lot_lng') }}"></div>
                <div class="col-12">
                    <div id="lotCoordHint" class="form-text text-danger d-none">Enter both latitude and longitude, or leave both empty.</div>
                </div>
                <div class="col-12">
                    <div id="lotCoordGeofenceHint" class="form-text text-danger d-none">Coordinates must be inside Negros Occidental geofence.</div>
                </div>
                <div class="col-12"><button id="fillLotGeoJson" class="btn btn-outline-secondary" type="button">Use coordinates as Point GeoJSON</button></div>
                <div class="col-12"><textarea id="geom_geojson" class="form-control" name="geom_geojson" rows="4" placeholder='{"type":"Point","coordinates":[123.1,10.5]}' required>{{ old('geom_geojson') }}</textarea><div class="form-text">Enter GeoJSON directly, or use latitude/longitude above to auto-fill a point.</div></div>
                <div class="col-12 d-grid"><button id="lotSubmitButton" class="btn btn-outline-primary" type="submit">Add Lot</button></div>
            </form>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const lotForm = document.getElementById('lotGeoJsonForm');
        const latInput = document.getElementById('lot_lat');
        const lngInput = document.getElementById('lot_lng');
        const geomInput = document.getElementById('geom_geojson');
        const fillButton = document.getElementById('fillLotGeoJson');
        const lotSubmitButton = document.getElementById('lotSubmitButton');
        const lotCoordHint = document.getElementById('lotCoordHint');
        const lotCoordGeofenceHint = document.getElementById('lotCoordGeofenceHint');
        const geofenceGeoJson = @json($geofenceGeoJson ?? null);

        const geofenceValidator =
            window.BayLotMap && typeof window.BayLotMap.createPointValidator === 'function'
                ? window.BayLotMap.createPointValidator(geofenceGeoJson)
                : null;

        if (!lotForm || !latInput || !lngInput || !geomInput || !fillButton || !lotSubmitButton || !lotCoordHint || !lotCoordGeofenceHint) {
            return;
        }

        const buildPointGeoJson = (lat, lng) => JSON.stringify({
            type: 'Point',
            coordinates: [Number(lng.toFixed(6)), Number(lat.toFixed(6))],
        });

        const setLotCoordValidityState = (isValid, message = '', { showPairHint = false, showGeofenceHint = false } = {}) => {
            latInput.setCustomValidity(isValid ? '' : message);
            lngInput.setCustomValidity(isValid ? '' : message);
            latInput.classList.toggle('is-invalid', !isValid);
            lngInput.classList.toggle('is-invalid', !isValid);
            lotCoordHint.classList.toggle('d-none', !showPairHint);
            lotCoordGeofenceHint.classList.toggle('d-none', !showGeofenceHint);
            lotSubmitButton.disabled = !isValid;
        };

        const updateCoordinateHint = () => {
            const hasLat = latInput.value.trim() !== '';
            const hasLng = lngInput.value.trim() !== '';

            if ((hasLat && !hasLng) || (!hasLat && hasLng)) {
                setLotCoordValidityState(false, 'Enter both latitude and longitude, or leave both empty.', {
                    showPairHint: true,
                    showGeofenceHint: false,
                });
                return;
            }

            if (!hasLat && !hasLng) {
                setLotCoordValidityState(true);
                return;
            }

            if (!latInput.validity.valid || !lngInput.validity.valid) {
                setLotCoordValidityState(false, 'Enter valid latitude and longitude values.', {
                    showPairHint: true,
                    showGeofenceHint: false,
                });
                return;
            }

            const lat = Number.parseFloat(latInput.value);
            const lng = Number.parseFloat(lngInput.value);

            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                setLotCoordValidityState(false, 'Enter valid latitude and longitude values.', {
                    showPairHint: true,
                    showGeofenceHint: false,
                });
                return;
            }

            if (!geofenceValidator || !window.L) {
                setLotCoordValidityState(true);
                return;
            }

            const isInside = geofenceValidator.isPointInsideAllowedArea(window.L.latLng(lat, lng));
            setLotCoordValidityState(isInside, 'Coordinates must be inside Negros Occidental geofence.', {
                showPairHint: false,
                showGeofenceHint: !isInside,
            });
        };

        fillButton.addEventListener('click', () => {
            updateCoordinateHint();

            if (!latInput.checkValidity() || !lngInput.checkValidity()) {
                latInput.reportValidity();
                return;
            }

            const lat = Number.parseFloat(latInput.value);
            const lng = Number.parseFloat(lngInput.value);

            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                return;
            }

            geomInput.value = buildPointGeoJson(lat, lng);
        });

        lotForm.addEventListener('submit', (event) => {
            updateCoordinateHint();

            if (lotSubmitButton.disabled) {
                event.preventDefault();
                latInput.reportValidity();
            }
        });

        latInput.addEventListener('input', updateCoordinateHint);
        lngInput.addEventListener('input', updateCoordinateHint);
        latInput.addEventListener('change', updateCoordinateHint);
        lngInput.addEventListener('change', updateCoordinateHint);
        latInput.addEventListener('blur', updateCoordinateHint);
        lngInput.addEventListener('blur', updateCoordinateHint);
        updateCoordinateHint();
    });
</script>
</x-app-layout>
