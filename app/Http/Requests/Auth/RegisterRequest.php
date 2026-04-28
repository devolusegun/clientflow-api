<?php
// app/Http/Requests/Auth/RegisterRequest.php
namespace App\Http\Requests\Auth;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:8|confirmed',
            'company_name' => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'address'      => 'nullable|string|max:500',
            'currency'     => 'nullable|string|in:USD,EUR,GBP,NGN,CAD,AUD',
        ];
    }
}
