import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

function postPopup(place) {
    const popup = document.createElement('div');
    const name = document.createElement('strong');
    name.textContent = place.cafe;
    popup.append(name);

    if (place.city) {
        const city = document.createElement('p');
        city.textContent = place.city;
        popup.append(city);
    }

    const visitCount = document.createElement('p');
    if (place.post_count > 0) {
        visitCount.textContent = `${place.post_count} pinned ${place.post_count === 1 ? 'post' : 'posts'}`;
        popup.append(visitCount);
    }

    if (Array.isArray(place.plans) && place.plans.length) {
        const plan = document.createElement('p');
        plan.textContent = `Planned: ${place.plans.map(item => item.date).join(', ')}`;
        popup.append(plan);
    }

    const stories = document.createElement('ul');
    place.posts.forEach(post => {
        const item = document.createElement('li');
        const link = document.createElement('a');
        link.href = post.url;
        link.textContent = post.title;
        item.append(link);

        const meta = document.createElement('small');
        meta.textContent = `${post.author} · ${post.date}`;
        item.append(meta);
        stories.append(item);
    });

    popup.append(stories);

    return popup;
}

function burgerMarkerIcon() {
    const wrapper = document.createElement('span');
    wrapper.className = 'map-burger-marker';
    wrapper.setAttribute('aria-hidden', 'true');
    wrapper.innerHTML = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.5 10.5C4.5 7.4 7.7 5 11 5c1 0 1.8.2 2.5.5.7.3.6 1.1 1.3 1.2.7.1.8-.9 1.5-.8.8.1.5 1.2 1.1 1.6.6.5 1.6-.2 2.1.4.5.6-.1 1.4.2 2.6M4 13h16M5 16h14M6.5 19h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="19" cy="5" r=".8" fill="var(--coffee-brand)"/><circle cx="21" cy="7" r=".65" fill="var(--coffee-brand)"/></svg>';

    return wrapper;
}

function communityMarkerIconElement() {
    const marker = document.createElement('span');
    marker.className = 'map-community-marker';

    return marker;
}

function statusMarkerIcon(status) {
    const wrapper = document.createElement('span');
    wrapper.className = status === 'want_to_go' ? 'map-want-marker' : 'map-planned-marker';
    const icon = status === 'want_to_go'
        ? '<path d="M7 5.5h10v14l-5-3-5 3z"/>'
        : '<rect x="4" y="5.5" width="16" height="15" rx="2"/><path d="M8 3.5v4M16 3.5v4M4 10h16"/><path d="m8 15 2.2 2.2L16 12"/>';
    wrapper.innerHTML = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${icon}</svg>`;

    return wrapper;
}

function currentLocationIcon(user) {
    const wrapper = document.createElement('span');
    wrapper.className = 'map-current-avatar';

    if (user?.photo) {
        const image = document.createElement('img');
        image.src = user.photo;
        image.alt = '';
        image.onerror = () => image.replaceWith(initials(user));
        wrapper.append(image);
    } else {
        wrapper.append(initials(user));
    }

    return L.divIcon({ className: '', html: wrapper, iconSize: [42, 42], iconAnchor: [21, 21] });
}

function initials(user) {
    const element = document.createElement('span');
    element.className = 'map-current-initials';
    element.textContent = user?.initials || 'You';

    return element;
}

function searchPopup(place) {
    const popup = document.createElement('div');
    const name = document.createElement('strong');
    name.textContent = place.name;
    popup.append(name);
    if (place.address) {
        const address = document.createElement('p');
        address.textContent = place.address;
        popup.append(address);
    }

    return popup;
}

export default function coffeeMap(wire, postPins, center, currentLocation, currentUser, hasPlannedPins = false) {
    return {
        map: null,
        postLayer: null,
        locationMarker: null,
        searchLayer: null,
        searchMarkers: new Map(),
        searchResults: [],
        locating: false,
        locationError: '',
        postPins,
        selectedCategories: wire.entangle('selectedCategories'),
        visibleLayers: { been_here: true, want_to_go: false, planned: hasPlannedPins },
        hasVisitedPins: postPins.some(pin => pin.visited),
        hasCommunityPins: postPins.some(pin => pin.community),
        hasCurrentLocation: false,
        nearbySettingsOpen: true,
        currentUser,

        init() {
            if (! this.$refs.map || this.$refs.map._leaflet_id) return;

            const initialPoint = currentLocation
                ? [currentLocation.latitude, currentLocation.longitude]
                : [center.latitude, center.longitude];

            this.map = L.map(this.$refs.map, {
                keyboard: true,
                scrollWheelZoom: false,
                zoomControl: true,
            }).setView(initialPoint, 13);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
            }).addTo(this.map);

            this.postLayer = L.layerGroup().addTo(this.map);
            this.searchLayer = L.layerGroup().addTo(this.map);
            this.drawPostPins();

            if (currentLocation) {
                this.setCurrentLocation(currentLocation.latitude, currentLocation.longitude, false);
            }

            const points = this.postPins.map(pin => [pin.latitude, pin.longitude]);
            if (currentLocation) points.push(initialPoint);

            if (! currentLocation && points.length > 1) {
                this.map.fitBounds(L.latLngBounds(points).pad(0.15), { maxZoom: 14 });
            } else if (points.length === 1) {
                this.map.setView(points[0], 14);
            }

            requestAnimationFrame(() => this.map?.invalidateSize());
        },

        drawPostPins() {
            this.postLayer.clearLayers();
            this.postPins.forEach(place => {
                const visitedVisible = place.visited && this.visibleLayers.been_here;
                const wantVisible = place.want_to_go && this.visibleLayers.want_to_go;
                const plannedVisible = place.planned && this.visibleLayers.planned;
                if (! visitedVisible && ! wantVisible && ! plannedVisible && ! place.community) return;

                const icon = visitedVisible ? burgerMarkerIcon()
                    : plannedVisible ? statusMarkerIcon('planned')
                        : wantVisible ? statusMarkerIcon('want_to_go')
                            : communityMarkerIconElement();
                if (visitedVisible && place.community) {
                    const communityBadge = document.createElement('span');
                    communityBadge.className = 'map-community-badge';
                    icon.append(communityBadge);
                }
                const marker = L.marker([place.latitude, place.longitude], {
                    icon: visitedVisible
                        ? L.divIcon({ className: '', html: icon, iconSize: [38, 38], iconAnchor: [19, 19] })
                        : L.divIcon({ className: '', html: icon, iconSize: wantVisible || plannedVisible ? [38, 38] : [22, 22], iconAnchor: wantVisible || plannedVisible ? [19, 19] : [11, 11] }),
                });

                marker.bindPopup(postPopup(place), { maxWidth: 280 });
                marker.addTo(this.postLayer);
            });
        },

        toggleLayer(layer) {
            this.visibleLayers[layer] = ! this.visibleLayers[layer];
            this.drawPostPins();
        },

        setCurrentLocation(latitude, longitude, centerMap = true) {
            this.locationMarker?.remove();
            this.locationMarker = L.marker([latitude, longitude], {
                icon: currentLocationIcon(this.currentUser),
                zIndexOffset: 1000,
            }).bindPopup('Your current location').addTo(this.map);
            this.hasCurrentLocation = true;

            if (centerMap) this.map.setView([latitude, longitude], 14);
        },

        showSearchResults(places) {
            this.searchLayer.clearLayers();
            this.searchMarkers.clear();
            this.searchResults = Array.isArray(places) ? places : [];
            if (this.searchResults.length === 0) return;

            const points = [];
            this.searchResults.forEach(place => {
                const point = [place.latitude, place.longitude];
                points.push(point);
                const icon = place.want_to_go
                    ? L.divIcon({ className: '', html: statusMarkerIcon('want_to_go'), iconSize: [38, 38], iconAnchor: [19, 19] })
                    : undefined;
                const marker = L.marker(point, { icon }).bindPopup(searchPopup(place)).addTo(this.searchLayer);
                this.searchMarkers.set(place.place_key, marker);
            });

            if (points.length > 1) this.map.fitBounds(L.latLngBounds(points).pad(0.2), { maxZoom: 15 });
            else this.map.setView(points[0], 15);
        },

        addWantToGoPlace(place) {
            const existing = this.postPins.find(pin => pin.place_key === place.place_key);
            if (existing) Object.assign(existing, place);
            else this.postPins.push(place);

            this.visibleLayers.want_to_go = true;
            this.drawPostPins();
            this.searchMarkers.get(place.place_key)?.remove();
            this.searchMarkers.delete(place.place_key);
            this.searchResults = this.searchResults.map(result => result.place_key === place.place_key
                ? { ...result, want_to_go: true }
                : result);
            this.map.setView([place.latitude, place.longitude], Math.max(this.map.getZoom(), 15));
        },

        requestLocation() {
            this.locationError = '';

            if (! window.isSecureContext || ! navigator.geolocation) {
                this.locationError = 'Location is unavailable here. Open CoffeeSpace over HTTPS or use a browser that supports location.';

                return;
            }

            this.locating = true;
            navigator.geolocation.getCurrentPosition(position => {
                this.locating = false;
                this.setCurrentLocation(position.coords.latitude, position.coords.longitude);
                wire.findNearbyCafes(position.coords.latitude, position.coords.longitude);
            }, error => {
                this.locating = false;
                this.locationError = error.code === error.PERMISSION_DENIED
                    ? 'Location access was denied. Allow it in your browser’s site settings, then try again.'
                    : error.code === error.TIMEOUT
                        ? 'Your location took too long to load. Please try again.'
                        : 'Your location could not be determined. Check your device location settings and try again.';
            }, { enableHighAccuracy: false, maximumAge: 120000, timeout: 12000 });
        },

        destroy() {
            this.map?.remove();
        },
    };
}
