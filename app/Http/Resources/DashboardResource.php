<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'stats' => [
                'total invoices' => (int) $this->resource['stats']->total_invoices,
                'draft_count' => (int) $this->resource['stats']->draft_count,
                'sent_count' => (int) $this->resource['stats']->sent_count,
                'paid_count' => (int) $this->resource['stats']->paid_count,
                'overdue_count' => (int) $this->resource['stats']->overdue_count,
                'total_paid' => (float) $this->resource['stats']->total_paid,
                'total_outstanding' => (float) $this->resource['stats']->total_outstanding,
                'total_overdue' => (float) $this->resource['stats']->total_overdue,
            ],
            'recent_invoices' => InvoiceResource::collection($this->resource['recent_invoices']),
            'monthly_revenue' => $this->resource['monthly_revenue'],
        ];
    }


}