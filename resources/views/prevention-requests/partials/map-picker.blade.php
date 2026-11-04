{{--
    Map location picker (optional) — Leaflet + OpenStreetMap Nominatim.
    No API key required.

    @param string|null  $initialLat   Pre-filled latitude (editing mode)
    @param string|null  $initialLng   Pre-filled longitude (editing mode)
    @param string       $latName      Hidden input name for latitude  (default: 'latitude')
    @param string       $lngName      Hidden input name for longitude (default: 'longitude')
    @param string       $mapId        Unique DOM id for the map container (default: 'map-picker')
--}}
@php
    $initialLat = $initialLat ?? null;
    $initialLng = $initialLng ?? null;
    $latName    = $latName    ?? 'latitude';
    $lngName    = $lngName    ?? 'longitude';
    $mapId      = $mapId      ?? 'map-picker';
    $hasCoords  = $initialLat !== null && $initialLat !== '' && $initialLng !== null && $initialLng !== '';
@endphp

{{-- ── Wrapper (the JS scans for [data-map-picker] and uses the attribute value as the mapId) ── --}}
<div data-map-picker="{{ $mapId }}">

@once
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .leaflet-container { z-index: 0; font: inherit; }
    .leaflet-control-attribution { font-size: 9px; }
    .onpc-map-recenter {
        position: absolute; bottom: 20px; right: 10px; z-index: 500;
        width: 40px; height: 40px; border-radius: 8px;
        background: #fff; border: 2px solid rgba(0,0,0,.15);
        box-shadow: 0 2px 8px rgba(0,0,0,.18);
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: all .15s ease;
    }
    .onpc-map-recenter:hover { background: #f8f8f8; transform: scale(1.05); }
    .dark .onpc-map-recenter { background: #374151; border-color: #4b5563; color: #e5e7eb; }
    .dark .onpc-map-recenter:hover { background: #4b5563; }
    @keyframes onpc-drop { 0% { transform: translateY(-20px); opacity: 0; } 60% { transform: translateY(2px); } 100% { transform: translateY(0); opacity: 1; } }
</style>
@endpush
@endonce

{{-- ── Hidden coordinate inputs (submitted with the form) ── --}}
<input type="hidden" name="{{ $latName }}"  id="{{ $mapId }}-lat"  value="{{ $initialLat }}">
<input type="hidden" name="{{ $lngName }}" id="{{ $mapId }}-lng" value="{{ $initialLng }}">

{{-- ── Toggle card (prominent, bordered) ── --}}
<button type="button"
        id="{{ $mapId }}-toggle"
        class="mt-3 w-full flex items-center gap-3 px-4 py-3.5 rounded-lg border-2 transition text-start
               {{ $hasCoords
                   ? 'border-emerald-300 bg-emerald-50/50 dark:border-emerald-700 dark:bg-emerald-900/15'
                   : 'border-surface-dim bg-surface-container-lowest hover:border-primary/40 hover:bg-surface-container dark:border-gray-600 dark:bg-gray-800 dark:hover:border-gov-400/40 dark:hover:bg-gray-700/60' }}">
    <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0
                 {{ $hasCoords
                     ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400'
                     : 'bg-primary/10 text-primary dark:bg-gov-400/15 dark:text-gov-400' }}">
        <span class="material-symbols-outlined text-xl" aria-hidden="true">{{ $hasCoords ? 'check_circle' : 'location_on' }}</span>
    </span>
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
            <span class="text-body-sm font-bold text-on-surface dark:text-gray-100">{{ __('prevention_request.map_pin_title') }}</span>
            <span class="text-[10px] font-semibold text-outline dark:text-gray-500 px-1.5 py-0.5 rounded-full
                         bg-surface-container dark:bg-gray-700">{{ __('prevention_request.map_optional') }}</span>
        </div>
        <p class="text-xs text-secondary dark:text-gray-400 mt-0.5 truncate">{{ __('prevention_request.map_pin_hint') }}</p>
        @if ($hasCoords)
            <p id="{{ $mapId }}-toggle-coords" class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 mt-1 font-mono" dir="ltr">
                ✅ {{ number_format((float)$initialLat, 5) }}, {{ number_format((float)$initialLng, 5) }}
            </p>
        @endif
    </div>
    <svg class="h-5 w-5 text-outline dark:text-gray-500 transition-transform shrink-0 {{ $hasCoords ? 'rotate-180' : '' }}"
         id="{{ $mapId }}-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
    </svg>
</button>

{{-- ── Map panel (collapsible) ── --}}
<div id="{{ $mapId }}-panel" class="{{ $hasCoords ? '' : 'hidden' }} mt-3 rounded-xl border border-surface-dim dark:border-gray-600 overflow-hidden shadow-sm">

    {{-- Search + actions bar --}}
    <div class="flex flex-col sm:flex-row gap-2 p-3 bg-surface-container-lowest dark:bg-gray-800 border-b border-surface-dim dark:border-gray-700">
        <div class="relative flex-1">
            <span class="material-symbols-outlined absolute start-2.5 top-1/2 -translate-y-1/2 text-base text-outline" aria-hidden="true">search</span>
            <input type="text"
                   id="{{ $mapId }}-search"
                   autocomplete="off"
                   class="block w-full h-10 ps-9 pe-3 text-body-sm text-on-surface rounded-lg border border-surface-dim bg-surface-container-lowest focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-outline dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                   placeholder="{{ __('prevention_request.map_search_placeholder') }}">
            <div id="{{ $mapId }}-spinner" class="hidden absolute end-2.5 top-1/2 -translate-y-1/2">
                <svg class="animate-spin h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </div>
        </div>
        <button type="button"
                id="{{ $mapId }}-locate"
                class="inline-flex items-center justify-center gap-1.5 h-10 px-3.5 rounded-lg border border-surface-dim bg-surface-container-lowest text-body-sm font-semibold text-on-surface transition hover:bg-surface-container dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-700 shrink-0">
            <span class="material-symbols-outlined text-base" aria-hidden="true">my_location</span>
            <span class="hidden sm:inline">{{ __('prevention_request.map_my_location') }}</span>
        </button>
    </div>

    {{-- Search results dropdown --}}
    <div id="{{ $mapId }}-results" class="hidden max-h-48 overflow-y-auto border-b border-surface-dim dark:border-gray-700 bg-surface-container-lowest dark:bg-gray-800"></div>

    {{-- Map canvas with floating recenter button ── --}}
    <div class="relative">
        <div id="{{ $mapId }}" class="w-full" style="height: 340px;"></div>
        <button type="button"
                id="{{ $mapId }}-recenter"
                title="{{ __('prevention_request.map_recenter') }}"
                class="onpc-map-recenter hidden"
                aria-label="{{ __('prevention_request.map_recenter') }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </button>
    </div>

    {{-- Hint banner (shown when no marker) ── --}}
    <div id="{{ $mapId }}-hint" class="{{ $hasCoords ? 'hidden' : '' }} flex items-start gap-2 px-4 py-3 bg-surface-container-low dark:bg-gray-800/50 border-t border-surface-dim dark:border-gray-700">
        <span class="material-symbols-outlined text-base text-primary dark:text-gov-400 shrink-0 mt-px" aria-hidden="true">info</span>
        <p class="text-xs text-secondary dark:text-gray-400 leading-relaxed">{{ __('prevention_request.map_hint_click') }}</p>
    </div>

    {{-- Drag hint (shown when marker exists) ── --}}
    <div id="{{ $mapId }}-drag-hint" class="{{ $hasCoords ? '' : 'hidden' }} flex items-center gap-2 px-4 py-2 bg-surface-container-low dark:bg-gray-800/50 border-t border-surface-dim dark:border-gray-700">
        <span class="material-symbols-outlined text-sm text-outline shrink-0" aria-hidden="true">drag_indicator</span>
        <p class="text-[11px] text-outline dark:text-gray-500">{{ __('prevention_request.map_drag_hint') }}</p>
    </div>

    {{-- Coordinates footer with Confirm + Clear ── --}}
    <div id="{{ $mapId }}-coords" class="{{ $hasCoords ? '' : 'hidden' }} flex flex-col sm:flex-row items-stretch sm:items-center gap-3 px-4 py-3 bg-surface-container-lowest dark:bg-gray-800 border-t-2 border-emerald-200 dark:border-emerald-700/40">
        <div class="flex items-center gap-2 min-w-0 flex-1">
            <span class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-base text-emerald-600 dark:text-emerald-400" aria-hidden="true">check_circle</span>
            </span>
            <div class="min-w-0">
                <span class="block text-[10px] uppercase tracking-wider font-bold text-secondary dark:text-gray-500">{{ __('prevention_request.map_selected_coords') }}</span>
                <span id="{{ $mapId }}-coords-text" class="block text-xs font-bold font-mono text-on-surface dark:text-gray-200" dir="ltr">
                    @if ($hasCoords){{ number_format((float)$initialLat, 5) }}, {{ number_format((float)$initialLng, 5) }}@endif
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <button type="button"
                    id="{{ $mapId }}-clear"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-red-600 border border-red-200 bg-red-50 transition hover:bg-red-100 dark:text-red-400 dark:border-red-700/40 dark:bg-red-900/15 dark:hover:bg-red-900/30">
                <span class="material-symbols-outlined text-sm" aria-hidden="true">delete</span>
                {{ __('prevention_request.map_clear') }}
            </button>
            <button type="button"
                    id="{{ $mapId }}-confirm"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-white bg-emerald-600 transition hover:bg-emerald-700 shadow-sm dark:bg-emerald-700 dark:hover:bg-emerald-600">
                <span class="material-symbols-outlined text-sm" aria-hidden="true">check</span>
                {{ __('prevention_request.map_confirm') }}
            </button>
        </div>
    </div>
</div>
</div>{{-- ── /wrapper ── --}}

@once
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush
@endonce

@once
@push('scripts')
<script>
(function () {
    // ── Leaflet must be loaded for map pickers ──
    if (typeof L === 'undefined') {
        var waitForLeaflet = setInterval(function () {
            if (typeof L !== 'undefined') { clearInterval(waitForLeaflet); initAll(); }
        }, 50);
        setTimeout(function () { clearInterval(waitForLeaflet); }, 5000);
    }

    // Tunisia default center
    var DEFAULT_CENTER = [34.0, 9.0];
    var DEFAULT_ZOOM   = 6;
    var SELECTED_ZOOM  = 17;

    // ── Tunisia bounding box (with small padding) ──
    // Real bounds: ~[30.2, 7.5] → [37.5, 11.6]; padding adds ~0.3° for usability.
    var TN_SW = [29.9,  7.2];
    var TN_NE = [37.8, 11.9];
    var TN_BOUNDS = L.latLngBounds(TN_SW, TN_NE);

    function isInTunisia(lat, lng) {
        return lat >= TN_SW[0] && lat <= TN_NE[0] && lng >= TN_SW[1] && lng <= TN_NE[1];
    }

    // Custom red marker icon (ONPC theme)
    function makeIcon() {
        return L.divIcon({
            className: 'onpc-marker',
            html: '<div style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;animation:onpc-drop .3s ease;">'
                + '<div style="width:18px;height:18px;background:#9e001f;border:3px solid #fff;border-radius:50% 50% 50% 0;transform:rotate(-45deg);box-shadow:0 2px 8px rgba(0,0,0,.45);"></div>'
                + '</div>',
            iconSize: [30, 30],
            iconAnchor: [15, 28],
        });
    }

    function initAll() {
        document.querySelectorAll('[data-map-picker]').forEach(initPicker);
    }

    function initPicker(container) {
        if (container._mapInited) return;
        container._mapInited = true;

        var mapId       = container.getAttribute('data-map-picker');
        var latInput    = document.getElementById(mapId + '-lat');
        var lngInput    = document.getElementById(mapId + '-lng');
        var toggleBtn   = document.getElementById(mapId + '-toggle');
        var panel       = document.getElementById(mapId + '-panel');
        var chevron     = document.getElementById(mapId + '-chevron');
        var searchInp   = document.getElementById(mapId + '-search');
        var spinner     = document.getElementById(mapId + '-spinner');
        var resultsDiv  = document.getElementById(mapId + '-results');
        var locateBtn   = document.getElementById(mapId + '-locate');
        var coordsDiv   = document.getElementById(mapId + '-coords');
        var coordsText  = document.getElementById(mapId + '-coords-text');
        var clearBtn    = document.getElementById(mapId + '-clear');
        var confirmBtn  = document.getElementById(mapId + '-confirm');
        var recenterBtn = document.getElementById(mapId + '-recenter');
        var hintDiv     = document.getElementById(mapId + '-hint');
        var dragHintDiv = document.getElementById(mapId + '-drag-hint');
        var toggleCoords = document.getElementById(mapId + '-toggle-coords');

        var map = null, marker = null, searchTimer = null;

        // ── Initial coords from hidden inputs ──
        var initLat = parseFloat(latInput.value);
        var initLng = parseFloat(lngInput.value);
        var hasInitial = !isNaN(initLat) && !isNaN(initLng);

        if (typeof L === 'undefined') return;

        // ── Initialize the map (lazy — called on first expand) ──
        function ensureMap() {
            if (map) { setTimeout(function () { map.invalidateSize(); }, 100); return; }

            map = L.map(mapId, {
                zoomControl: true,
                attributionControl: true,
                maxBounds: TN_BOUNDS,
                maxBoundsViscosity: 1.0,
                minZoom: 6,
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            if (hasInitial) {
                map.setView([initLat, initLng], SELECTED_ZOOM);
                marker = L.marker([initLat, initLng], { icon: makeIcon(), draggable: true }).addTo(map);
                marker.on('dragend', function (e) {
                    var ll = e.target.getLatLng();
                    setCoords(ll.lat, ll.lng);
                });
                recenterBtn.classList.remove('hidden');
            } else {
                map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
            }

            // Click to place marker (only inside Tunisia)
            map.on('click', function (e) {
                if (!isInTunisia(e.latlng.lat, e.latlng.lng)) {
                    alert('@json(__('prevention_request.map_outside_tunisia'))');
                    return;
                }
                placeMarker(e.latlng.lat, e.latlng.lng);
            });

            setTimeout(function () { map.invalidateSize(); }, 100);
        }

        // ── Toggle expand/collapse ──
        toggleBtn.addEventListener('click', function () {
            var isHidden = panel.classList.contains('hidden');
            if (isHidden) {
                panel.classList.remove('hidden');
                chevron.style.transform = 'rotate(180deg)';
                ensureMap();
            } else {
                panel.classList.add('hidden');
                chevron.style.transform = '';
            }
        });

        // If initial coords exist, auto-expand and init map
        if (hasInitial && !panel.classList.contains('hidden')) {
            setTimeout(ensureMap, 50);
        }

        // ── Place / move marker and save coords (Tunisia only) ──
        function placeMarker(lat, lng) {
            if (!isInTunisia(lat, lng)) {
                alert('@json(__('prevention_request.map_outside_tunisia'))');
                return;
            }
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { icon: makeIcon(), draggable: true }).addTo(map);
                marker.on('dragstart', function () {
                    marker._lastValid = marker.getLatLng();
                });
                marker.on('dragend', function (e) {
                    var ll = e.target.getLatLng();
                    if (!isInTunisia(ll.lat, ll.lng)) {
                        alert('@json(__('prevention_request.map_outside_tunisia'))');
                        if (marker._lastValid) {
                            marker.setLatLng(marker._lastValid);
                        }
                        return;
                    }
                    setCoords(ll.lat, ll.lng);
                });
            }
            setCoords(lat, lng);
            map.setView([lat, lng], Math.max(map.getZoom(), SELECTED_ZOOM));
            recenterBtn.classList.remove('hidden');
        }

        function setCoords(lat, lng) {
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
            coordsText.textContent = lat.toFixed(5) + ', ' + lng.toFixed(5);
            coordsDiv.classList.remove('hidden');
            hintDiv.classList.add('hidden');
            dragHintDiv.classList.remove('hidden');
        }

        // ─── Default (no position yet) styling for the toggle card ───
        var TOGGLE_DEFAULT = {
            remove: ['border-emerald-300', 'bg-emerald-50/50', 'dark:border-emerald-700', 'dark:bg-emerald-900/15'],
            add:    ['border-surface-dim', 'bg-surface-container-lowest', 'hover:border-primary/40', 'hover:bg-surface-container',
                     'dark:border-gray-600', 'dark:bg-gray-800', 'dark:hover:border-gov-400/40', 'dark:hover:bg-gray-700/60'],
        };

        // Reset the toggle card from the green "saved" state back to the neutral state
        function resetToggleState() {
            toggleBtn.classList.remove.apply(toggleBtn.classList, TOGGLE_DEFAULT.remove);
            toggleBtn.classList.add.apply(toggleBtn.classList, TOGGLE_DEFAULT.add);
            var iconSpan = toggleBtn.querySelector('.material-symbols-outlined');
            if (iconSpan) iconSpan.textContent = 'location_on';
            var iconWrap = toggleBtn.querySelector('span.w-10');
            if (iconWrap) {
                iconWrap.classList.remove('bg-emerald-100', 'text-emerald-600', 'dark:bg-emerald-900/30', 'dark:text-emerald-400');
                iconWrap.classList.add('bg-primary/10', 'text-primary', 'dark:bg-gov-400/15', 'dark:text-gov-400');
            }
            if (toggleCoords) toggleCoords.classList.add('hidden');
        }

        function clearCoords() {
            latInput.value = '';
            lngInput.value = '';
            coordsText.textContent = '';
            coordsDiv.classList.add('hidden');
            hintDiv.classList.remove('hidden');
            dragHintDiv.classList.add('hidden');
            recenterBtn.classList.add('hidden');
            if (marker) { map.removeLayer(marker); marker = null; }
            resultsDiv.classList.add('hidden');
            resultsDiv.innerHTML = '';
            resetToggleState();  // remove the green outline/highlight
        }

        clearBtn.addEventListener('click', clearCoords);

        // ── Confirm button: collapse the map panel, keep coords ──
        confirmBtn.addEventListener('click', function () {
            panel.classList.add('hidden');
            chevron.style.transform = '';
            // Update the toggle card to show "saved" state
            toggleBtn.classList.remove('border-surface-dim', 'bg-surface-container-lowest', 'hover:border-primary/40', 'hover:bg-surface-container');
            toggleBtn.classList.remove('dark:border-gray-600', 'dark:bg-gray-800', 'dark:hover:border-gov-400/40', 'dark:hover:bg-gray-700/60');
            toggleBtn.classList.add('border-emerald-300', 'bg-emerald-50/50', 'dark:border-emerald-700', 'dark:bg-emerald-900/15');
            var iconSpan = toggleBtn.querySelector('.material-symbols-outlined');
            if (iconSpan) iconSpan.textContent = 'check_circle';
            var iconWrap = toggleBtn.querySelector('span.w-10');
            if (iconWrap) {
                iconWrap.classList.remove('bg-primary/10', 'text-primary', 'dark:bg-gov-400/15', 'dark:text-gov-400');
                iconWrap.classList.add('bg-emerald-100', 'text-emerald-600', 'dark:bg-emerald-900/30', 'dark:text-emerald-400');
            }
        });

        // ── Recenter button: pan map back to the marker ──
        recenterBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (marker) {
                var ll = marker.getLatLng();
                map.setView([ll.lat, ll.lng], Math.max(map.getZoom(), SELECTED_ZOOM), { animate: true });
            }
        });

        // ── Nominatim search (debounced) ──
        searchInp.addEventListener('input', function () {
            var q = this.value.trim();
            clearTimeout(searchTimer);
            if (q.length < 3) {
                resultsDiv.classList.add('hidden');
                resultsDiv.innerHTML = '';
                return;
            }
            searchTimer = setTimeout(function () {
                doSearch(q);
            }, 400);
        });

        function doSearch(q) {
            spinner.classList.remove('hidden');
            resultsDiv.classList.remove('hidden');
            resultsDiv.innerHTML = '<div class="px-4 py-3 text-body-sm text-outline italic">' +
                '@json(__('prevention_request.map_searching'))' + '</div>';

            fetch('https://nominatim.openstreetmap.org/search?format=json&q=' +
                  encodeURIComponent(q) + '&countrycodes=tn&limit=5&addressdetails=1')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    spinner.classList.add('hidden');
                    if (!data || data.length === 0) {
                        resultsDiv.innerHTML = '<div class="px-4 py-3 text-body-sm text-outline italic">' +
                            '@json(__('prevention_request.map_no_results'))' + '</div>';
                        return;
                    }
                    resultsDiv.innerHTML = '';
                    data.forEach(function (item) {
                        var li = document.createElement('div');
                        li.className = 'flex items-start gap-2 px-4 py-2.5 cursor-pointer text-body-sm text-on-surface hover:bg-surface-container transition dark:text-gray-200 dark:hover:bg-gray-700';
                        li.innerHTML = '<span class="material-symbols-outlined text-base text-outline shrink-0 mt-0.5" aria-hidden="true">place</span>' +
                            '<span class="min-w-0">' + (item.display_name || '') + '</span>';
                        li.addEventListener('click', function () {
                            var lat = parseFloat(item.lat);
                            var lng = parseFloat(item.lon);
                            if (!isNaN(lat) && !isNaN(lng)) {
                                ensureMap();
                                placeMarker(lat, lng);
                                resultsDiv.classList.add('hidden');
                                resultsDiv.innerHTML = '';
                                searchInp.value = '';
                            }
                        });
                        resultsDiv.appendChild(li);
                    });
                })
                .catch(function () {
                    spinner.classList.add('hidden');
                    resultsDiv.innerHTML = '<div class="px-4 py-3 text-body-sm text-red-500 italic">Error</div>';
                });
        }

        // Hide results when clicking outside
        document.addEventListener('click', function (e) {
            if (!resultsDiv.contains(e.target) && e.target !== searchInp) {
                resultsDiv.classList.add('hidden');
            }
        });

        // ── Geolocation ("My location") ──
        locateBtn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('@json(__('prevention_request.map_geolocation_unsupported'))');
                return;
            }
            locateBtn.disabled = true;
            var origHtml = locateBtn.innerHTML;
            locateBtn.innerHTML = '<svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg><span class="hidden sm:inline">@json(__('prevention_request.map_locating'))</span>';

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    locateBtn.disabled = false;
                    locateBtn.innerHTML = origHtml;
                    if (!isInTunisia(pos.coords.latitude, pos.coords.longitude)) {
                        alert('@json(__('prevention_request.map_outside_tunisia'))');
                        return;
                    }
                    ensureMap();
                    placeMarker(pos.coords.latitude, pos.coords.longitude);
                },
                function () {
                    locateBtn.disabled = false;
                    locateBtn.innerHTML = origHtml;
                    alert('@json(__('prevention_request.map_geolocation_error'))');
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
    }

    if (typeof L !== 'undefined') initAll();
})();
</script>
@endpush
@endonce
