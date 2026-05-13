<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'company'        => $this->company,
            'address'        => $this->address,
            'city'           => $this->city,
            'country'        => $this->country,
            'notes'          => $this->notes,
            'invoices_count' => $this->whenCounted('invoices'),
            'invoices_sum_total_amount' => $this->whenAggregated('invoices', 'total_amount', 'sum'),
            'total_billed'   => $this->when(
                $this->relationLoaded('invoices'),
                fn () => $this->total_billed
            ),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}