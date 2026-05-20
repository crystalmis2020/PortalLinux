<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNetworkHostRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'ip_address'  => ['required', 'ip', 'unique:network_hosts,ip_address'],
            'server_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'host_category_id' => ['nullable', 'integer', 'exists:host_categories,id'],
        ];
    }
}
