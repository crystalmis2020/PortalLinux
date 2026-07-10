<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EncodeTripTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canEncodeTripTickets();
    }

    public function rules(): array
    {
        $tripTicket = $this->route('tripTicket');

        return [
            'ticket_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('trip_tickets', 'ticket_number')->ignore($tripTicket?->id),
            ],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
