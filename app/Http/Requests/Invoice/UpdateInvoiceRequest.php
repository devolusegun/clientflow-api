<?php
// app/Http/Requests/Invoice/UpdateInvoiceRequest.php
namespace App\Http\Requests\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'client_id'           => 'sometimes|exists:clients,id',
            'issue_date'          => 'sometimes|date',
            'due_date'            => 'sometimes|date|after_or_equal:issue_date',
            'tax_rate'            => 'sometimes|nullable|numeric|min:0|max:100',
            'discount_amount'     => 'sometimes|nullable|numeric|min:0',
            'currency'            => 'sometimes|string|in:USD,EUR,GBP,NGN,CAD,AUD',
            'notes'               => 'sometimes|nullable|string|max:2000',
            'payment_terms'       => 'sometimes|nullable|string|max:500',
            'items'               => 'sometimes|array|min:1',
            'items.*.description' => 'required_with:items|string|max:500',
            'items.*.quantity'    => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price'  => 'required_with:items|numeric|min:0',
        ];
    }
}
