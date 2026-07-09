@php
    $distanceDisplayValue = old('distance_km_display', isset($ticket) && $ticket->distance_km !== null ? number_format($ticket->distance_km, 2) . ' km' : '');
    $distanceValue = old('distance_km', $ticket->distance_km ?? '');
    $roundTripDistanceDisplayValue = $distanceValue !== '' && $distanceValue !== null ? number_format(((float) $distanceValue) * 2, 2) . ' km' : '';
    $destinationMode = old('destination_mode', isset($ticket) && ! $ticket->trip_ticket_location_id ? 'local_maramag' : 'mindanao');
    $localDestinationValue = old('local_destination', $destinationMode === 'local_maramag' ? preg_replace('/,\s*Maramag,\s*Bukidnon,\s*Philippines$/i', '', $ticket->destination ?? '') : '');
@endphp

<div class="col-12">
    <div class="border-top pt-3 mt-1">
        <h6 class="trip-ticket-section-title mb-0">Destination</h6>
    </div>
</div>
<div class="col-12 col-lg-4">
    <label for="destination_mode" class="form-label">Destination Type</label>
    <select class="form-select" id="destination_mode" name="destination_mode" required>
        <option value="mindanao" {{ $destinationMode === 'mindanao' ? 'selected' : '' }}>Mindanao destination</option>
        <option value="local_maramag" {{ $destinationMode === 'local_maramag' ? 'selected' : '' }}>Within Maramag, please specify</option>
    </select>
</div>
<div class="w-100 d-none d-lg-block"></div>
<div class="col-12" id="local_destination_group">
    <label for="local_destination" class="form-label">Specify Destination Within Maramag</label>
    <input type="text" class="form-control" id="local_destination" name="local_destination" value="{{ $localDestinationValue }}">
    <div class="form-text">Distance calculation is skipped for destinations within Maramag.</div>
</div>
<div class="col-12 col-lg-4 destination-mindanao-group">
    <label for="destination_region" class="form-label">Region</label>
    <select class="form-select trip-ticket-select" id="destination_region" name="destination_region" required>
        <option value="">Select region</option>
    </select>
</div>
<div class="col-12 col-lg-4 destination-mindanao-group">
    <label for="destination_province" class="form-label">Province</label>
    <select class="form-select trip-ticket-select" id="destination_province" name="destination_province" required disabled>
        <option value="">Select province</option>
    </select>
</div>
<div class="col-12 col-lg-4 destination-mindanao-group">
    <label for="destination_city" class="form-label">City / Municipality</label>
    <select class="form-select trip-ticket-select" id="destination_city" name="destination_city" required disabled>
        <option value="">Select city/municipality</option>
    </select>
    <input type="hidden" id="destination" name="destination" value="{{ old('destination', $ticket->destination ?? '') }}">
    <input type="hidden" id="trip_ticket_location_id" name="trip_ticket_location_id" value="{{ old('trip_ticket_location_id', $ticket->trip_ticket_location_id ?? '') }}">
</div>
<div class="col-12 col-lg-4 destination-distance-group">
    <label for="distance_km_display" class="form-label">Road KM from Maramag</label>
    <div class="input-group">
        <input type="text" class="form-control bg-light" id="distance_km_display" name="distance_km_display" value="{{ $distanceDisplayValue }}" readonly>
        <span class="input-group-text">km</span>
    </div>
    <input type="hidden" id="distance_km" name="distance_km" value="{{ $distanceValue }}">
    <div class="form-text">Origin: Maramag, Bukidnon, Philippines</div>
</div>
<div class="col-12 col-lg-4 destination-distance-group">
    <label for="round_trip_distance_km_display" class="form-label">Estimated Round-Trip Distance</label>
    <div class="input-group">
        <input type="text" class="form-control bg-light" id="round_trip_distance_km_display" value="{{ $roundTripDistanceDisplayValue }}" readonly>
        <span class="input-group-text">km</span>
    </div>
    <div class="form-text">Computed as outbound and return travel.</div>
</div>
