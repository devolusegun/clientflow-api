<?php
// app/Http/Requests/Client/StoreClientRequest.php
namespace App\Http\Requests\Client;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'city'    => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'notes'   => 'nullable|string|max:1000',
        ];
    }
}
