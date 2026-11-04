{{--
    Read-only map display — shows a Leaflet map with a pinned marker.
    No API key required.

    @param string|null  $lat     Latitude to display
    @param string|null  $lng     Longitude to display
    @param string       $mapId   Unique DOM id (default: 'map-display')
--}}
@php
    $mapId = $mapId ?? 'map-display';
    $lat   = $lat ?? null;
    $lng   = $lng ?? null;
    $hasCoords = $lat !== null && $lat !== '' && $lng !== null && $lng !== '';
@endphp

@if ($hasCoords)
@once
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .leaflet-container { z-index: 0; font: inherit; }
    .leaflet-control-attribution { font-size: 9px; }
</style>
@endpush
@endonce

@once
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush
@endonce

<div class="rounded-lg border border-surface-dim overflow-hidden dark:border-gray-600">
    <div class="flex items-center gap-2 px-3 py-2 bg-surface-container-lowest border-b border-surface-dim dark:bg-gray-800 dark:border-gray-700">
        <span class="material-symbols-outlined text-base text-primary dark:text-gov-400" aria-hidden="true">location_on</span>
        <span class="text-xs font-semibold text-on-surface dark:text-gray-200">
            {{ __('prevention_request.map_selected_coords') }}:
            <span class="font-mono" dir="ltr">{{ number_format((float)$lat, 5) }}, {{ number_format((float)$lng, 5) }}</span>
        </span>
    </div>
    <div id="{{ $mapId }}" data-display-lat="{{ $lat }}" data-display-lng="{{ $lng }}" style="height: 280px; width: 100%;"></div>
</div>

@once
@push('scripts')
<script>
(function () {
    function initDisplay(container) {
        if (container._mapInited || typeof L === 'undefined') return;
        container._mapInited = true;

        var lat = parseFloat(container.getAttribute('data-display-lat'));
        var lng = parseFloat(container.getAttribute('data-display-lng'));

        // ── Tunisia bounds (prevent panning outside) ──
        var TN_BOUNDS = L.latLngBounds([29.9, 7.2], [37.8, 11.9]);

        var map = L.map(container.id, {
            zoomControl: true, attributionControl: true,
            dragging: true, scrollWheelZoom: false,
            maxBounds: TN_BOUNDS, maxBoundsViscosity: 1.0, minZoom: 6,
        });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        var icon = L.divIcon({
            className: 'onpc-marker',
            html: '<div style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;">'
                + '<div style="width:16px;height:16px;background:#9e001f;border:2px solid #fff;border-radius:50% 50% 50% 0;transform:rotate(-45deg);box-shadow:0 2px 6px rgba(0,0,0,.4);"></div>'
                + '</div>',
            iconSize: [30, 30],
            iconAnchor: [15, 28],
        });

        L.marker([lat, lng], { icon: icon }).addTo(map);
        map.setView([lat, lng], 17);
        setTimeout(function () { map.invalidateSize(); }, 100);
    }

    function initAll() {
        document.querySelectorAll('[data-display-lat]').forEach(initDisplay);
    }

    if (typeof L !== 'undefined') {
        initAll();
    } else {
        var wait = setInterval(function () {
            if (typeof L !== 'undefined') { clearInterval(wait); initAll(); }
        }, 50);
        setTimeout(function () { clearInterval(wait); }, 5000);
    }
})();
</script>
@endpush
@endonce
@endif
