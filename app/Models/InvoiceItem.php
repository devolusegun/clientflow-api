<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
        'sort_order',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // ── Auto-calculate line_total ─────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            $item->line_total = round(
                (float) $item->quantity * (float) $item->unit_price,
                2
            );
        });

        // After any item is saved or deleted, recalculate parent invoice totals
        static::saved(function (InvoiceItem $item) {
            $item->invoice->recalculateTotals();
        });

        static::deleted(function (InvoiceItem $item) {
            $item->invoice->recalculateTotals();
        });
    }
}
