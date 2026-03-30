import './bootstrap';
import 'bootstrap';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

window.L = L;

const NEGROS_OCCIDENTAL_FALLBACK_BOUNDS = L.latLngBounds(
	L.latLng(9.85, 122.35),
	L.latLng(11.15, 123.45)
);

const GOOGLE_MAPS_AUTH_FAILURE_EVENT = 'baylot:google-maps-auth-failure';

let googleMapsAuthHandlerInstalled = false;

const installGoogleMapsAuthFailureHandler = () => {
	if (googleMapsAuthHandlerInstalled) {
		return;
	}

	const existingHandler = typeof window.gm_authFailure === 'function' ? window.gm_authFailure : null;

	window.gm_authFailure = () => {
		window.dispatchEvent(new CustomEvent(GOOGLE_MAPS_AUTH_FAILURE_EVENT));

		if (existingHandler) {
			try {
				existingHandler();
			} catch (_error) {
			}
		}
	};

	googleMapsAuthHandlerInstalled = true;
};

const resolveNegrosBounds = (geofenceGeoJson) => {
	if (!geofenceGeoJson) {
		return NEGROS_OCCIDENTAL_FALLBACK_BOUNDS;
	}

	try {
		const parsed = typeof geofenceGeoJson === 'string' ? JSON.parse(geofenceGeoJson) : geofenceGeoJson;
		const layer = L.geoJSON(parsed);
		const bounds = layer.getBounds();
		if (bounds.isValid()) {
			return bounds;
		}
	} catch (_error) {
	}

	return NEGROS_OCCIDENTAL_FALLBACK_BOUNDS;
};

const lockMapToBounds = (map, bounds) => {
	const limitedBounds = bounds.pad(0.02);
	map.setMaxBounds(limitedBounds);
	map.options.maxBoundsViscosity = 1.0;
	map.fitBounds(bounds);

	const minZoom = map.getBoundsZoom(bounds, true);
	map.setMinZoom(minZoom);
	map.setView(bounds.getCenter(), minZoom);
};

const constrainMapToBounds = (map, bounds) => {
	const limitedBounds = bounds.pad(0.02);
	map.setMaxBounds(limitedBounds);
	map.options.maxBoundsViscosity = 1.0;

	const minZoom = map.getBoundsZoom(bounds, true);
	map.setMinZoom(minZoom);
};

const keepPointInsideBounds = (latlng, bounds) => {
	if (bounds.contains(latlng)) {
		return latlng;
	}

	const south = bounds.getSouth();
	const north = bounds.getNorth();
	const west = bounds.getWest();
	const east = bounds.getEast();

	return L.latLng(
		Math.min(north, Math.max(south, latlng.lat)),
		Math.min(east, Math.max(west, latlng.lng))
	);
};

const extractOuterRings = (geojson) => {
	const rings = [];

	const addFromGeometry = (geometry) => {
		if (!geometry) {
			return;
		}

		if (geometry.type === 'Polygon' && Array.isArray(geometry.coordinates?.[0])) {
			rings.push(geometry.coordinates[0]);
		}

		if (geometry.type === 'MultiPolygon' && Array.isArray(geometry.coordinates)) {
			geometry.coordinates.forEach((polygon) => {
				if (Array.isArray(polygon?.[0])) {
					rings.push(polygon[0]);
				}
			});
		}
	};

	if (!geojson) {
		return rings;
	}

	if (geojson.type === 'FeatureCollection' && Array.isArray(geojson.features)) {
		geojson.features.forEach((feature) => addFromGeometry(feature.geometry));
	} else if (geojson.type === 'Feature') {
		addFromGeometry(geojson.geometry);
	} else {
		addFromGeometry(geojson);
	}

	return rings;
};

const ringToLatLng = (ring) => ring.map(([lng, lat]) => [lat, lng]);

const addOutsideMask = (map, parsedGeoJson) => {
	const outerRings = extractOuterRings(parsedGeoJson);

	if (!outerRings.length) {
		return;
	}

	if (!map.getPane('maskPane')) {
		map.createPane('maskPane');
		map.getPane('maskPane').style.zIndex = 350;
	}

	const worldRing = [
		[-85, -360],
		[85, -360],
		[85, 360],
		[-85, 360],
	];

	const holes = outerRings.map((ring) => ringToLatLng(ring));

	L.polygon([worldRing, ...holes], {
		pane: 'maskPane',
		stroke: false,
		fillColor: '#000000',
		fillOpacity: 0.35,
		interactive: false,
	}).addTo(map);
};

const getPolygonCoordinatesFromGeometry = (geometry) => {
	const polygons = [];

	if (!geometry) {
		return polygons;
	}

	if (geometry.type === 'Polygon' && Array.isArray(geometry.coordinates)) {
		polygons.push(geometry.coordinates);
	}

	if (geometry.type === 'MultiPolygon' && Array.isArray(geometry.coordinates)) {
		geometry.coordinates.forEach((polygonCoordinates) => {
			if (Array.isArray(polygonCoordinates)) {
				polygons.push(polygonCoordinates);
			}
		});
	}

	if (geometry.type === 'GeometryCollection' && Array.isArray(geometry.geometries)) {
		geometry.geometries.forEach((childGeometry) => {
			polygons.push(...getPolygonCoordinatesFromGeometry(childGeometry));
		});
	}

	return polygons;
};

const extractPolygonsFromGeoJson = (geojson) => {
	const polygons = [];

	if (!geojson) {
		return polygons;
	}

	if (geojson.type === 'FeatureCollection' && Array.isArray(geojson.features)) {
		geojson.features.forEach((feature) => {
			polygons.push(...extractPolygonsFromGeoJson(feature));
		});
		return polygons;
	}

	if (geojson.type === 'Feature') {
		polygons.push(...getPolygonCoordinatesFromGeometry(geojson.geometry));
		return polygons;
	}

	polygons.push(...getPolygonCoordinatesFromGeometry(geojson));
	return polygons;
};

const isPointOnSegment = (pointCoordinates, segmentStart, segmentEnd) => {
	const [pointLng, pointLat] = pointCoordinates;
	const [startLng, startLat] = segmentStart;
	const [endLng, endLat] = segmentEnd;

	const crossProduct =
		(pointLat - startLat) * (endLng - startLng) -
		(pointLng - startLng) * (endLat - startLat);

	if (Math.abs(crossProduct) > 1e-10) {
		return false;
	}

	const dotProduct =
		(pointLng - startLng) * (endLng - startLng) +
		(pointLat - startLat) * (endLat - startLat);

	if (dotProduct < 0) {
		return false;
	}

	const squaredLength =
		(endLng - startLng) * (endLng - startLng) +
		(endLat - startLat) * (endLat - startLat);

	return dotProduct <= squaredLength;
};

const isPointInsideRing = (pointCoordinates, ringCoordinates) => {
	if (!Array.isArray(ringCoordinates) || ringCoordinates.length < 3) {
		return false;
	}

	const [pointLng, pointLat] = pointCoordinates;
	let inside = false;

	for (
		let ringIndex = 0, previousIndex = ringCoordinates.length - 1;
		ringIndex < ringCoordinates.length;
		previousIndex = ringIndex, ringIndex += 1
	) {
		const currentCoordinate = ringCoordinates[ringIndex];
		const previousCoordinate = ringCoordinates[previousIndex];

		if (!Array.isArray(currentCoordinate) || !Array.isArray(previousCoordinate)) {
			continue;
		}

		if (isPointOnSegment(pointCoordinates, previousCoordinate, currentCoordinate)) {
			return true;
		}

		const [currentLng, currentLat] = currentCoordinate;
		const [previousLng, previousLat] = previousCoordinate;
		const intersects =
			(currentLat > pointLat) !== (previousLat > pointLat) &&
			pointLng < ((previousLng - currentLng) * (pointLat - currentLat)) / (previousLat - currentLat) + currentLng;

		if (intersects) {
			inside = !inside;
		}
	}

	return inside;
};

const isPointInsidePolygon = (pointCoordinates, polygonCoordinates) => {
	if (!Array.isArray(polygonCoordinates) || polygonCoordinates.length === 0) {
		return false;
	}

	const outerRing = polygonCoordinates[0];
	if (!isPointInsideRing(pointCoordinates, outerRing)) {
		return false;
	}

	for (let holeIndex = 1; holeIndex < polygonCoordinates.length; holeIndex += 1) {
		if (isPointInsideRing(pointCoordinates, polygonCoordinates[holeIndex])) {
			return false;
		}
	}

	return true;
};

const nominatimCache = new Map();

const formatNominatimLabel = (payload) => {
	if (!payload) {
		return '';
	}

	if (payload.display_name) {
		return payload.display_name;
	}

	const address = payload.address || {};
	const cityLike = address.city || address.town || address.village || address.municipality;
	const province = address.state || address.county;
	const country = address.country;

	return [cityLike, province, country].filter(Boolean).join(', ');
};

const reverseGeocodeWithNominatim = async (lat, lng, signal) => {
	const cacheKey = `${lat.toFixed(5)},${lng.toFixed(5)}`;
	if (nominatimCache.has(cacheKey)) {
		return nominatimCache.get(cacheKey);
	}

	const params = new URLSearchParams({
		format: 'jsonv2',
		lat: String(lat),
		lon: String(lng),
		zoom: '17',
		addressdetails: '1',
	});

	const response = await fetch(`https://nominatim.openstreetmap.org/reverse?${params.toString()}`, {
		method: 'GET',
		headers: {
			Accept: 'application/json',
		},
		signal,
	});

	if (!response.ok) {
		throw new Error(`Nominatim request failed: ${response.status}`);
	}

	const payload = await response.json();
	const label = formatNominatimLabel(payload);
	nominatimCache.set(cacheKey, label);

	return label;
};

let googleMapsLoaderPromise = null;

const loadGoogleMapsApi = (apiKey) => {
	if (!apiKey) {
		return Promise.reject(new Error('Missing Google Maps API key.'));
	}

	installGoogleMapsAuthFailureHandler();

	if (window.google?.maps) {
		return Promise.resolve(window.google.maps);
	}

	if (googleMapsLoaderPromise) {
		return googleMapsLoaderPromise;
	}

	googleMapsLoaderPromise = new Promise((resolve, reject) => {
		const callbackName = '__baylotInitGoogleMapsApi';
		const existingScript = document.getElementById('baylot-google-maps-sdk');
		const authFailureListener = () => {
			cleanUpCallback();
			reject(new Error('Google Maps authentication failed.'));
		};

		const cleanUpCallback = () => {
			if (window[callbackName]) {
				delete window[callbackName];
			}

			window.removeEventListener(GOOGLE_MAPS_AUTH_FAILURE_EVENT, authFailureListener);
		};

		window.addEventListener(GOOGLE_MAPS_AUTH_FAILURE_EVENT, authFailureListener, { once: true });

		window[callbackName] = () => {
			cleanUpCallback();
			resolve(window.google.maps);
		};

		if (existingScript) {
			existingScript.addEventListener(
				'load',
				() => {
					cleanUpCallback();
					resolve(window.google.maps);
				},
				{ once: true }
			);

			existingScript.addEventListener(
				'error',
				(error) => {
					cleanUpCallback();
					reject(error);
				},
				{ once: true }
			);

			return;
		}

		const script = document.createElement('script');
		script.id = 'baylot-google-maps-sdk';
		script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&callback=${callbackName}`;
		script.async = true;
		script.defer = true;
		script.onerror = (error) => {
			cleanUpCallback();
			reject(error);
		};

		document.head.appendChild(script);
	}).catch((error) => {
		googleMapsLoaderPromise = null;
		throw error;
	});

	return googleMapsLoaderPromise;
};

const parseGeoJsonSafely = (geojson) => {
	if (!geojson) {
		return null;
	}

	try {
		return typeof geojson === 'string' ? JSON.parse(geojson) : geojson;
	} catch (_error) {
		return null;
	}
};

const createPointValidator = (geofenceGeoJson = null) => {
	const bounds = resolveNegrosBounds(geofenceGeoJson);
	const parsedGeofence = parseGeoJsonSafely(geofenceGeoJson);
	const geofencePolygons = parsedGeofence ? extractPolygonsFromGeoJson(parsedGeofence) : [];

	const isPointInsideAllowedArea = (point) => {
		if (!point || Number.isNaN(Number(point.lat)) || Number.isNaN(Number(point.lng))) {
			return false;
		}

		if (!bounds.contains(point)) {
			return false;
		}

		if (!geofencePolygons.length) {
			return true;
		}

		const pointCoordinates = [point.lng, point.lat];
		return geofencePolygons.some((polygonCoordinates) => isPointInsidePolygon(pointCoordinates, polygonCoordinates));
	};

	const filterMarkerList = (markerList = []) =>
		markerList.filter((marker) => {
			const markerLat = Number.parseFloat(marker?.lat);
			const markerLng = Number.parseFloat(marker?.lng);

			if (Number.isNaN(markerLat) || Number.isNaN(markerLng)) {
				return false;
			}

			return isPointInsideAllowedArea(L.latLng(markerLat, markerLng));
		});

	return {
		bounds,
		parsedGeofence,
		isPointInsideAllowedArea,
		filterMarkerList,
	};
};

const toGoogleBoundsLiteral = (bounds) => {
	if (!bounds?.isValid?.()) {
		return null;
	}

	return {
		north: bounds.getNorth(),
		south: bounds.getSouth(),
		east: bounds.getEast(),
		west: bounds.getWest(),
	};
};

const drawGeofenceOnGoogleMap = (map, parsedGeoJson) => {
	const polygonCoordinatesList = extractPolygonsFromGeoJson(parsedGeoJson);
	if (!polygonCoordinatesList.length) {
		return null;
	}

	const geofenceBounds = new google.maps.LatLngBounds();

	polygonCoordinatesList.forEach((polygonCoordinates) => {
		const polygonPaths = polygonCoordinates
			.filter((ringCoordinates) => Array.isArray(ringCoordinates) && ringCoordinates.length >= 3)
			.map((ringCoordinates) =>
				ringCoordinates
					.filter((coordinate) => Array.isArray(coordinate) && coordinate.length >= 2)
					.map(([lng, lat]) => {
						const point = { lat, lng };
						geofenceBounds.extend(point);
						return point;
					})
			)
			.filter((ringCoordinates) => ringCoordinates.length >= 3);

		if (!polygonPaths.length) {
			return;
		}

		new google.maps.Polygon({
			paths: polygonPaths,
			strokeColor: '#0d6efd',
			strokeOpacity: 1,
			strokeWeight: 2,
			fillColor: '#0d6efd',
			fillOpacity: 0.05,
			clickable: false,
			map,
		});
	});

	return geofenceBounds;
};

const initLeafletViewMap = (mapId, lat, lng, geofenceGeoJson = null) => {
	const mapEl = document.getElementById(mapId);
	if (!mapEl || lat === null || lng === null) {
		return;
	}

	const geofencePointValidator = createPointValidator(geofenceGeoJson);
	const bounds = geofencePointValidator.bounds;
	const listingPoint = L.latLng(lat, lng);
	const hasValidListingPoint = geofencePointValidator.isPointInsideAllowedArea(listingPoint);
	const initialCenter = hasValidListingPoint ? listingPoint : bounds.getCenter();
	const map = L.map(mapEl, { maxBoundsViscosity: 1.0 }).setView(
		[initialCenter.lat, initialCenter.lng],
		hasValidListingPoint ? 15 : 10
	);
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; OpenStreetMap contributors',
	}).addTo(map);

	if (hasValidListingPoint) {
		L.marker(listingPoint).addTo(map);
	}

	if (geofencePointValidator.parsedGeofence) {
		const geofenceLayer = L.geoJSON(geofencePointValidator.parsedGeofence, {
			style: { color: '#0d6efd', weight: 2, fillOpacity: 0.05 },
		}).addTo(map);

		addOutsideMask(map, geofencePointValidator.parsedGeofence);

		const allowedBounds = geofenceLayer.getBounds().isValid() ? geofenceLayer.getBounds() : bounds;
		constrainMapToBounds(map, allowedBounds);

		if (hasValidListingPoint) {
			map.panTo(listingPoint);
		} else {
			map.fitBounds(allowedBounds);
		}

		return;
	}

	constrainMapToBounds(map, bounds);

	if (hasValidListingPoint) {
		map.panTo(listingPoint);
	} else {
		map.fitBounds(bounds);
	}
};

window.BayLotMap = {
	createPointValidator(geofenceGeoJson = null) {
		return createPointValidator(geofenceGeoJson);
	},

	initPicker(mapId, latInputId, lngInputId, geofenceGeoJson = null, locationNameInputId = null, validationFeedbackId = null) {
		const mapEl = document.getElementById(mapId);
		const latInput = document.getElementById(latInputId);
		const lngInput = document.getElementById(lngInputId);
		const locationNameInput = locationNameInputId ? document.getElementById(locationNameInputId) : null;
		const validationFeedback = validationFeedbackId ? document.getElementById(validationFeedbackId) : null;

		if (!mapEl || !latInput || !lngInput) {
			return;
		}

		const geofencePointValidator = this.createPointValidator(geofenceGeoJson);
		const bounds = geofencePointValidator.bounds;
		const initialLat = parseFloat(latInput.value || bounds.getCenter().lat);
		const initialLng = parseFloat(lngInput.value || bounds.getCenter().lng);
		const initialPoint = keepPointInsideBounds(L.latLng(initialLat, initialLng), bounds);
		const map = L.map(mapEl, { maxBoundsViscosity: 1.0 }).setView([initialPoint.lat, initialPoint.lng], 11);

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap contributors',
		}).addTo(map);

		let geofenceLayer = null;
		if (geofencePointValidator.parsedGeofence) {
			geofenceLayer = L.geoJSON(geofencePointValidator.parsedGeofence, {
				style: { color: '#0d6efd', weight: 2, fillOpacity: 0.05 },
			}).addTo(map);

			addOutsideMask(map, geofencePointValidator.parsedGeofence);
		}

		lockMapToBounds(map, geofenceLayer?.getBounds()?.isValid() ? geofenceLayer.getBounds() : bounds);

		const geofenceValidationMessage = 'Location must be inside Negros Occidental geofence.';

		const emitCoordinateValidityChange = (isValid, message = '') => {
			const payload = { isValid, message };
			latInput.dispatchEvent(
				new CustomEvent('baylot:coordinate-validity-changed', {
					detail: payload,
					bubbles: true,
				})
			);
			lngInput.dispatchEvent(
				new CustomEvent('baylot:coordinate-validity-changed', {
					detail: payload,
					bubbles: true,
				})
			);
		};

		const setCoordinateValidity = (isValid, message = '') => {
			latInput.setCustomValidity(isValid ? '' : message);
			lngInput.setCustomValidity(isValid ? '' : message);
			latInput.classList.toggle('is-invalid', !isValid);
			lngInput.classList.toggle('is-invalid', !isValid);

			if (validationFeedback) {
				validationFeedback.textContent = isValid ? '' : message;
				validationFeedback.classList.toggle('d-none', isValid);
			}

			emitCoordinateValidityChange(isValid, message);
		};

		const validatePoint = (point) => {
			const isValid = geofencePointValidator.isPointInsideAllowedArea(point);
			setCoordinateValidity(isValid, geofenceValidationMessage);
			return isValid;
		};

		const clearValidationState = () => {
			setCoordinateValidity(true);
		};

		latInput.value = initialPoint.lat.toFixed(6);
		lngInput.value = initialPoint.lng.toFixed(6);

		let marker = L.marker([initialPoint.lat, initialPoint.lng], { draggable: true }).addTo(map);
		let reverseGeocodeAbortController = null;
		let reverseGeocodeTimer = null;

		const updateLocationName = (point) => {
			if (!locationNameInput) {
				return;
			}

			if (reverseGeocodeTimer) {
				clearTimeout(reverseGeocodeTimer);
			}

			if (reverseGeocodeAbortController) {
				reverseGeocodeAbortController.abort();
			}

			locationNameInput.value = 'Resolving location...';

			reverseGeocodeTimer = window.setTimeout(async () => {
				reverseGeocodeAbortController = new AbortController();
				try {
					const label = await reverseGeocodeWithNominatim(point.lat, point.lng, reverseGeocodeAbortController.signal);
					locationNameInput.value = label || 'Location name unavailable';
				} catch (_error) {
					locationNameInput.value = 'Location name unavailable';
				}
			}, 650);
		};

		const updateCoordinates = (point, resolveName = true) => {
			latInput.value = point.lat.toFixed(6);
			lngInput.value = point.lng.toFixed(6);
			const isValid = validatePoint(point);
			if (resolveName && isValid) {
				updateLocationName(point);
			}
		};

		const syncMarkerFromInputs = ({ normalize = false, resolveName = false, panToPoint = false } = {}) => {
			const manualLat = Number.parseFloat(latInput.value);
			const manualLng = Number.parseFloat(lngInput.value);

			if (Number.isNaN(manualLat) || Number.isNaN(manualLng)) {
				clearValidationState();
				return;
			}

			const point = L.latLng(manualLat, manualLng);
			const isValid = validatePoint(point);
			if (!isValid) {
				return;
			}

			marker.setLatLng(point);

			if (panToPoint) {
				map.panTo(point);
			}

			if (normalize) {
				latInput.value = point.lat.toFixed(6);
				lngInput.value = point.lng.toFixed(6);
			}

			if (resolveName) {
				updateLocationName(point);
			}
		};

		updateCoordinates(initialPoint, true);

		latInput.addEventListener('input', () => syncMarkerFromInputs());
		lngInput.addEventListener('input', () => syncMarkerFromInputs());
		latInput.addEventListener('change', () => syncMarkerFromInputs({ normalize: true, resolveName: true, panToPoint: true }));
		lngInput.addEventListener('change', () => syncMarkerFromInputs({ normalize: true, resolveName: true, panToPoint: true }));
		latInput.addEventListener('blur', () => syncMarkerFromInputs({ normalize: true, resolveName: true, panToPoint: true }));
		lngInput.addEventListener('blur', () => syncMarkerFromInputs({ normalize: true, resolveName: true, panToPoint: true }));

		marker.on('dragend', () => {
			const point = keepPointInsideBounds(marker.getLatLng(), bounds);
			marker.setLatLng(point);
			updateCoordinates(point, true);
		});

		map.on('click', (event) => {
			const point = keepPointInsideBounds(event.latlng, bounds);
			marker.setLatLng(point);
			updateCoordinates(point, true);
		});
	},

	initViewMap(mapId, lat, lng, geofenceGeoJson = null, googleMapsApiKey = null) {
		const mapEl = document.getElementById(mapId);
		const parsedLat = Number.parseFloat(lat);
		const parsedLng = Number.parseFloat(lng);

		if (!mapEl || Number.isNaN(parsedLat) || Number.isNaN(parsedLng)) {
			return;
		}

		const geofencePointValidator = createPointValidator(geofenceGeoJson);
		const bounds = geofencePointValidator.bounds;
		const listingPoint = L.latLng(parsedLat, parsedLng);
		const hasValidListingPoint = geofencePointValidator.isPointInsideAllowedArea(listingPoint);
		const initialCenter = hasValidListingPoint ? listingPoint : bounds.getCenter();
		const normalizedApiKey = typeof googleMapsApiKey === 'string' ? googleMapsApiKey.trim() : '';
		const fallbackToLeaflet = () => {
			if (mapEl.dataset.baylotMapProvider === 'leaflet') {
				return;
			}

			mapEl.dataset.baylotMapProvider = 'leaflet';
			mapEl.innerHTML = '';
			initLeafletViewMap(mapId, parsedLat, parsedLng, geofenceGeoJson);
		};

		if (!normalizedApiKey) {
			fallbackToLeaflet();
			return;
		}

		window.addEventListener(GOOGLE_MAPS_AUTH_FAILURE_EVENT, fallbackToLeaflet, { once: true });

		loadGoogleMapsApi(normalizedApiKey)
			.then(() => {
				if (mapEl.dataset.baylotMapProvider === 'leaflet') {
					return;
				}

				mapEl.dataset.baylotMapProvider = 'google';

				const mapOptions = {
					center: { lat: initialCenter.lat, lng: initialCenter.lng },
					zoom: hasValidListingPoint ? 15 : 10,
					mapTypeControl: false,
					streetViewControl: false,
					fullscreenControl: true,
				};

				const restrictedBounds = toGoogleBoundsLiteral(bounds);
				if (restrictedBounds) {
					mapOptions.restriction = {
						latLngBounds: restrictedBounds,
						strictBounds: true,
					};
				}

				const map = new google.maps.Map(mapEl, mapOptions);

				if (hasValidListingPoint) {
					new google.maps.Marker({
						position: { lat: listingPoint.lat, lng: listingPoint.lng },
						map,
					});
				}

				if (geofencePointValidator.parsedGeofence) {
					const geofenceBounds = drawGeofenceOnGoogleMap(map, geofencePointValidator.parsedGeofence);
					if (geofenceBounds && !geofenceBounds.isEmpty()) {
						map.fitBounds(geofenceBounds, 24);

						if (hasValidListingPoint) {
							map.panTo({ lat: listingPoint.lat, lng: listingPoint.lng });
						}
					}
				}
			})
			.catch(() => {
				fallbackToLeaflet();
			});
	},

	initBrowseMap(mapId, initialMarkers, mapDataUrl, geofenceGeoJson = null) {
		const mapEl = document.getElementById(mapId);
		if (!mapEl) return null;

		const geofencePointValidator = createPointValidator(geofenceGeoJson);
		const bounds = geofencePointValidator.bounds;
		const center = bounds.getCenter();
		const map = L.map(mapEl, { maxBoundsViscosity: 1.0 }).setView([center.lat, center.lng], 10);

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap contributors',
		}).addTo(map);

		let geofenceLayer = null;
		if (geofencePointValidator.parsedGeofence) {
			geofenceLayer = L.geoJSON(geofencePointValidator.parsedGeofence, {
				style: { color: '#0d6efd', weight: 2, fillOpacity: 0.05 },
			}).addTo(map);

			addOutsideMask(map, geofencePointValidator.parsedGeofence);
		}

		const allowedBounds = geofenceLayer?.getBounds()?.isValid() ? geofenceLayer.getBounds() : bounds;
		lockMapToBounds(map, allowedBounds);

		const markerLayer = L.layerGroup().addTo(map);

		const formatPrice = (price) =>
			'\u20B1' + Number(price).toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

		const renderMarkers = (markerList) => {
			const visibleMarkers = geofencePointValidator.filterMarkerList(markerList);
			markerLayer.clearLayers();
			visibleMarkers.forEach((data) => {
				const marker = L.marker([data.lat, data.lng]);
				marker.bindPopup(
					'<div style="min-width:190px;max-width:220px">' +
					'<img src="' + data.thumb + '" style="width:100%;height:110px;object-fit:cover;border-radius:4px;margin-bottom:8px" alt="" loading="lazy">' +
					'<p style="font-weight:600;font-size:.875rem;margin:0 0 4px">' + data.title + '</p>' +
					'<p style="color:#0d6efd;font-weight:600;margin:0 0 10px">' + formatPrice(data.price) + '</p>' +
					'<a href="' + data.url + '" style="display:block;text-align:center;padding:6px 12px;background:#0d6efd;color:#fff;border-radius:4px;text-decoration:none;font-size:.875rem">View Details</a>' +
					'</div>',
					{ maxWidth: 250 }
				);
				markerLayer.addLayer(marker);
			});

			return visibleMarkers;
		};

		const visibleInitialMarkers = renderMarkers(initialMarkers);

		if (visibleInitialMarkers.length > 0) {
			try {
				map.fitBounds(
					visibleInitialMarkers.map((m) => [m.lat, m.lng]),
					{ padding: [40, 40], maxZoom: 14 }
				);
			} catch (_e) {
				map.fitBounds(allowedBounds);
			}
		} else {
			map.fitBounds(allowedBounds);
		}

		if (mapDataUrl) {
			let boundsTimer = null;
			const refreshForBounds = () => {
				clearTimeout(boundsTimer);
				boundsTimer = window.setTimeout(() => {
					const b = map.getBounds();
					const params = new URLSearchParams(window.location.search);
					params.set('sw_lat', b.getSouth().toFixed(6));
					params.set('sw_lng', b.getWest().toFixed(6));
					params.set('ne_lat', b.getNorth().toFixed(6));
					params.set('ne_lng', b.getEast().toFixed(6));
					fetch(mapDataUrl + '?' + params.toString(), { headers: { Accept: 'application/json' } })
						.then((r) => r.json())
						.then((data) => renderMarkers(data))
						.catch(() => {});
				}, 400);
			};

			map.on('moveend', refreshForBounds);
			map.on('zoomend', refreshForBounds);
		}

		return map;
	},
};

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
		anchor.addEventListener('click', (event) => {
			const targetId = anchor.getAttribute('href');

			if (!targetId || targetId === '#') {
				return;
			}

			const target = document.querySelector(targetId);
			if (!target) {
				return;
			}

			event.preventDefault();
			target.scrollIntoView({ behavior: 'smooth', block: 'start' });
		});
	});

	const observer = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					entry.target.classList.add('in-view');
					observer.unobserve(entry.target);
				}
			});
		},
		{ threshold: 0.18 }
	);

	document.querySelectorAll('.animate-on-scroll').forEach((element) => {
		observer.observe(element);
	});
});
