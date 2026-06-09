<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'exists:departments,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'purpose' => ['required', 'string'],
            'destination' => ['required', 'string', 'max:255'],
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
            'requested_end_datetime.after' => 'The return date/time must be after the departure date/time.',
        ];
    }
}
