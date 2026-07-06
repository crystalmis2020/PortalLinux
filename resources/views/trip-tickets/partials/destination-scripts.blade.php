<script>
    document.addEventListener('DOMContentLoaded', function () {
        const locations = @json($destinationLocations);
        const oldRegion = @json(old('destination_region', $selectedDestinationRegion ?? null));
        const oldProvince = @json(old('destination_province', $selectedDestinationProvince ?? null));
        const oldCity = @json(old('destination_city', $selectedDestinationCity ?? null));
        const oldDestinationMode = @json(old('destination_mode', isset($ticket) && ! $ticket->trip_ticket_location_id ? 'local_maramag' : 'mindanao'));
        const oldDistance = @json(old('distance_km_display', isset($ticket) && $ticket->distance_km !== null ? number_format($ticket->distance_km, 2) : null));
        const oldDistanceValue = @json(old('distance_km', $ticket->distance_km ?? null));
        const distanceUrlTemplate = @json(route('trip-tickets.locations.distance', ['tripTicketLocation' => '__LOCATION_ID__']));

        const destinationModeSelect = document.getElementById('destination_mode');
        const localDestinationGroup = document.getElementById('local_destination_group');
        const localDestinationInput = document.getElementById('local_destination');
        const mindanaoGroups = document.querySelectorAll('.destination-mindanao-group');
        const distanceGroups = document.querySelectorAll('.destination-distance-group');
        const regionSelect = document.getElementById('destination_region');
        const provinceSelect = document.getElementById('destination_province');
        const citySelect = document.getElementById('destination_city');
        const destinationInput = document.getElementById('destination');
        const locationIdInput = document.getElementById('trip_ticket_location_id');
        const distanceDisplayInput = document.getElementById('distance_km_display');
        const distanceInput = document.getElementById('distance_km');
        const roundTripDistanceDisplayInput = document.getElementById('round_trip_distance_km_display');

        if (!destinationModeSelect || !localDestinationGroup || !localDestinationInput || !regionSelect || !provinceSelect || !citySelect || !destinationInput || !locationIdInput || !distanceDisplayInput || !distanceInput || !roundTripDistanceDisplayInput) {
            return;
        }

        $('.trip-ticket-select').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        function resetSelect(select, placeholder, disabled = true) {
            select.innerHTML = '';
            select.append(new Option(placeholder, ''));
            select.disabled = disabled;
            $(select).trigger('change.select2');
        }

        function fillSelect(select, options, placeholder, selectedValue = '') {
            resetSelect(select, placeholder, false);
            options.forEach(function (option) {
                if (typeof option === 'object') {
                    const selectOption = new Option(option.name, option.name, false, option.name === selectedValue);
                    selectOption.dataset.locationId = option.id || '';
                    selectOption.dataset.destination = option.destination || '';
                    selectOption.dataset.distanceKm = option.distance_km || '';
                    select.append(selectOption);
                    return;
                }

                select.append(new Option(option, option, false, option === selectedValue));
            });
            $(select).trigger('change.select2');
        }

        function setDistance(value) {
            if (!value) {
                distanceDisplayInput.value = '';
                distanceInput.value = '';
                roundTripDistanceDisplayInput.value = '';
                return;
            }

            const numericValue = Number(value);
            distanceDisplayInput.value = value;
            distanceInput.value = value;
            roundTripDistanceDisplayInput.value = Number.isFinite(numericValue) ? (numericValue * 2).toFixed(2) : '';
        }

        function isLocalMaramag() {
            return destinationModeSelect.value === 'local_maramag';
        }

        function syncDestinationMode() {
            const localMode = isLocalMaramag();

            localDestinationGroup.classList.toggle('d-none', !localMode);
            mindanaoGroups.forEach(function (group) {
                group.classList.toggle('d-none', localMode);
            });
            distanceGroups.forEach(function (group) {
                group.classList.toggle('d-none', localMode);
            });
            localDestinationInput.required = localMode;
            regionSelect.required = !localMode;
            provinceSelect.required = !localMode;
            citySelect.required = !localMode;
            regionSelect.disabled = localMode;
            provinceSelect.disabled = localMode || !regionSelect.value;
            citySelect.disabled = localMode || !provinceSelect.value;

            $('.trip-ticket-select').trigger('change.select2');
            syncDestination();
        }

        function syncDestination() {
            if (isLocalMaramag()) {
                const localDestination = localDestinationInput.value.trim();
                destinationInput.value = localDestination ? `${localDestination}, Maramag, Bukidnon, Philippines` : '';
                locationIdInput.value = '';
                setDistance('0');
                return;
            }

            if (regionSelect.value && provinceSelect.value && citySelect.value) {
                const selectedOption = citySelect.options[citySelect.selectedIndex];
                const cachedDistance = selectedOption?.dataset.distanceKm || '';
                destinationInput.value = selectedOption?.dataset.destination || `${citySelect.value}, ${provinceSelect.value}, ${regionSelect.value}`;
                locationIdInput.value = selectedOption?.dataset.locationId || '';

                if (cachedDistance) {
                    setDistance(cachedDistance);
                } else {
                    distanceDisplayInput.value = 'Calculating...';
                    distanceInput.value = '';
                    roundTripDistanceDisplayInput.value = '';
                }

                loadDistance(locationIdInput.value, selectedOption);
                return;
            }

            destinationInput.value = '';
            locationIdInput.value = '';
            setDistance('');
        }

        function loadDistance(locationId, selectedOption) {
            if (!locationId || selectedOption?.dataset.distanceKm) {
                return;
            }

            fetch(distanceUrlTemplate.replace('__LOCATION_ID__', locationId), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Distance request failed');
                    }

                    return response.json();
                })
                .then(function (data) {
                    if (data.distance_km === null || data.distance_km === undefined) {
                        distanceDisplayInput.value = 'Unavailable';
                        distanceInput.value = '';
                        roundTripDistanceDisplayInput.value = '';
                        return;
                    }

                    selectedOption.dataset.distanceKm = data.distance_km;
                    setDistance(data.distance_km);
                })
                .catch(function () {
                    distanceDisplayInput.value = 'Unavailable';
                    distanceInput.value = '';
                    roundTripDistanceDisplayInput.value = '';
                });
        }

        fillSelect(regionSelect, Object.keys(locations), 'Select region', oldRegion);

        if (oldRegion && locations[oldRegion]) {
            fillSelect(provinceSelect, Object.keys(locations[oldRegion]), 'Select province', oldProvince);
        }

        if (oldRegion && oldProvince && locations[oldRegion]?.[oldProvince]) {
            fillSelect(citySelect, locations[oldRegion][oldProvince], 'Select city/municipality', oldCity);
        }

        if (oldDistance || oldDistanceValue) {
            setDistance(oldDistanceValue || oldDistance);
        }

        destinationModeSelect.value = oldDestinationMode || 'mindanao';
        syncDestinationMode();

        $(regionSelect).on('change', function () {
            resetSelect(provinceSelect, 'Select province');
            resetSelect(citySelect, 'Select city/municipality');

            if (locations[regionSelect.value]) {
                fillSelect(provinceSelect, Object.keys(locations[regionSelect.value]), 'Select province');
            }

            syncDestination();
        });

        $(provinceSelect).on('change', function () {
            resetSelect(citySelect, 'Select city/municipality');

            if (locations[regionSelect.value]?.[provinceSelect.value]) {
                fillSelect(citySelect, locations[regionSelect.value][provinceSelect.value], 'Select city/municipality');
            }

            syncDestination();
        });

        $(citySelect).on('change', syncDestination);
        destinationModeSelect.addEventListener('change', syncDestinationMode);
        localDestinationInput.addEventListener('input', syncDestination);
    });
</script>
