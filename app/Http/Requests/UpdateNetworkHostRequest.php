<?php

// app/Http/Requests/UpdateNetworkHostRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNetworkHostRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('networkHost')->id ?? null;
        return [
            //'ip_address'  => ['required', 'ip', "unique:network_hosts,ip_address,{$id}"],
            'server_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'host_category_id' => ['nullable', 'integer', 'exists:host_categories,id'],
        ];
    }
}
