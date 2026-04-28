<?php
// app/Http/Requests/Client/UpdateClientRequest.php
namespace App\Http\Requests\Client;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'name'    => 'sometimes|string|max:255',
            'email'   => 'sometimes|nullable|email|max:255',
            'phone'   => 'sometimes|nullable|string|max:50',
            'company' => 'sometimes|nullable|string|max:255',
            'address' => 'sometimes|nullable|string|max:500',
            'city'    => 'sometimes|nullable|string|max:100',
            'country' => 'sometimes|nullable|string|max:100',
            'notes'   => 'sometimes|nullable|string|max:1000',
        ];
    }
}
