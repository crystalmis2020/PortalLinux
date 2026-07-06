<?php

namespace App\Http\Requests;

use App\Models\TripTicketLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('destination_mode') === 'local_maramag') {
            $localDestination = trim((string) $this->input('local_destination'));

            if ($localDestination !== '') {
                $this->merge([
                    'trip_ticket_location_id' => null,
                    'destination' => sprintf('%s, Maramag, Bukidnon, Philippines', $localDestination),
                    'distance_km' => 0,
                ]);
            }

            return;
        }

        $region = $this->input('destination_region');
        $province = $this->input('destination_province');
        $city = $this->input('destination_city');
        $location = TripTicketLocation::query()
            ->where('active', true)
            ->where('region_name', $region)
            ->where('province_name', $province)
            ->where('city_municipality_name', $city)
            ->first();

        if ($location) {
            $this->merge([
                'trip_ticket_location_id' => $location->id,
                'destination' => $location->destination,
            ]);
        } elseif ($region && $province && $city) {
            $this->merge([
                'destination' => sprintf('%s, %s, %s', $city, $province, $region),
            ]);
        }
    }

    public function rules(): array
    {
        $locations = TripTicketLocation::locationTree();

        return [
            'department_id' => ['nullable', 'exists:departments,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'purpose' => ['required', 'string'],
            'destination_mode' => ['required', Rule::in(['mindanao', 'local_maramag'])],
            'local_destination' => ['required_if:destination_mode,local_maramag', 'nullable', 'string', 'max:255'],
            'destination_region' => ['required_if:destination_mode,mindanao', 'nullable', 'string', Rule::in(array_keys($locations))],
            'destination_province' => ['required_if:destination_mode,mindanao', 'nullable', 'string'],
            'destination_city' => ['required_if:destination_mode,mindanao', 'nullable', 'string'],
            'trip_ticket_location_id' => ['required_if:destination_mode,mindanao', 'nullable', 'integer', 'exists:trip_ticket_locations,id'],
            'destination' => ['required', 'string'],
            'distance_km' => ['required', 'numeric', 'min:0'],
            'requested_start_datetime' => ['required', 'date'],
            'requested_end_datetime' => ['required', 'date', 'after:requested_start_datetime'],
            'passengers' => ['nullable', 'string'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'requested_end_datetime.after' => 'The return date must be after the departure date.',
            'destination_mode.required' => 'Please select a destination type.',
            'local_destination.required_if' => 'Please specify the destination within Maramag.',
            'destination_region.required' => 'Please select a Mindanao region.',
            'destination_region.required_if' => 'Please select a Mindanao region.',
            'destination_province.required' => 'Please select a province.',
            'destination_province.required_if' => 'Please select a province.',
            'destination_city.required' => 'Please select a city or municipality.',
            'destination_city.required_if' => 'Please select a city or municipality.',
            'trip_ticket_location_id.required' => 'Please select a valid Mindanao city or municipality.',
            'trip_ticket_location_id.required_if' => 'Please select a valid Mindanao city or municipality.',
            'trip_ticket_location_id.exists' => 'Please select a valid Mindanao city or municipality.',
            'distance_km.required' => 'The road distance is required. Please select a destination and wait for the KM calculation.',
            'distance_km.numeric' => 'The road distance must be numeric.',
            'distance_km.min' => 'The road distance must be at least 0 km.',
        ];
    }
}
