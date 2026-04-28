<?php
// app/Http/Requests/Invoice/StoreInvoiceRequest.php
namespace App\Http\Requests\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'client_id'       => 'required|exists:clients,id',
            'issue_date'      => 'required|date',
            'due_date'        => 'required|date|after_or_equal:issue_date',
            'tax_rate'        => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'currency'        => 'nullable|string|in:USD,EUR,GBP,NGN,CAD,AUD',
            'notes'           => 'nullable|string|max:2000',
            'payment_terms'   => 'nullable|string|max:500',
            'items'           => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ];
    }
}
