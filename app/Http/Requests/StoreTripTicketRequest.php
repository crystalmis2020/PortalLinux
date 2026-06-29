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
            'destination_region' => ['required', 'string', Rule::in(array_keys($locations))],
            'destination_province' => ['required', 'string'],
            'destination_city' => ['required', 'string'],
            'trip_ticket_location_id' => ['required', 'integer', 'exists:trip_ticket_locations,id'],
            'destination' => ['required', 'string', Rule::in(TripTicketLocation::destinations())],
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
            'destination_region.required' => 'Please select a Mindanao region.',
            'destination_province.required' => 'Please select a province.',
            'destination_city.required' => 'Please select a city or municipality.',
            'trip_ticket_location_id.required' => 'Please select a valid Mindanao city or municipality.',
            'trip_ticket_location_id.exists' => 'Please select a valid Mindanao city or municipality.',
            'destination.in' => 'Please select a valid Mindanao city or municipality from the list.',
            'distance_km.required' => 'The road distance is required. Please select a destination and wait for the KM calculation.',
            'distance_km.numeric' => 'The road distance must be numeric.',
            'distance_km.min' => 'The road distance must be at least 0 km.',
        ];
    }
}
