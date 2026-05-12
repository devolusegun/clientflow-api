<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'invoice_number'  => $this->invoice_number,
            'status'          => $this->status,
            'issue_date'      => $this->issue_date?->toDateString(),
            'due_date'        => $this->due_date?->toDateString(),
            'paid_at'         => $this->paid_at?->toISOString(),
            'subtotal'        => (float) $this->subtotal,
            'tax_rate'        => (float) $this->tax_rate,
            'tax_amount'      => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount'    => (float) $this->total_amount,
            'currency'        => $this->currency,
            'notes'           => $this->notes,
            'payment_terms'   => $this->payment_terms,
            'is_overdue'      => $this->is_overdue,
            'days_overdue'    => $this->days_overdue,
            'client'          => new ClientResource($this->whenLoaded('client')),
            'items'           => InvoiceItemResource::collection($this->whenLoaded('items')),
            'items_count'     => $this->whenCounted('items'),
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}