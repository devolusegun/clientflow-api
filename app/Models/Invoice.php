<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    // ── Status constants ──────────────────────────────────────────────────
    const STATUS_DRAFT    = 'draft';
    const STATUS_SENT     = 'sent';
    const STATUS_PAID     = 'paid';
    const STATUS_OVERDUE  = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_PAID,
        self::STATUS_OVERDUE,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'invoice_number',
        'status',
        'issue_date',
        'due_date',
        'paid_at',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'notes',
        'payment_terms',
    ];

    protected $casts = [
        'issue_date'      => 'date',
        'due_date'        => 'date',
        'paid_at'         => 'datetime',
        'subtotal'        => 'decimal:2',
        'tax_rate'        => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'deleted_at'      => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query
            ->where('status', self::STATUS_SENT)
            ->where('due_date', '<', now()->toDateString());
    }

    // ── Business logic ────────────────────────────────────────────────────

    /**
     * Recalculates subtotal, tax_amount, and total_amount
     * from the current line items and saves the invoice.
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('line_total');
        $taxAmount = round($subtotal * ($this->tax_rate / 100), 2);
        $total = $subtotal + $taxAmount - ($this->discount_amount ?? 0);

        $this->update([
            'subtotal'     => $subtotal,
            'tax_amount'   => $taxAmount,
            'total_amount' => max(0, $total),
        ]);
    }

    /**
     * Marks the invoice as overdue if past due_date and still sent.
     */
    public function checkAndMarkOverdue(): bool
    {
        if (
            $this->status === self::STATUS_SENT &&
            $this->due_date->isPast()
        ) {
            $this->update(['status' => self::STATUS_OVERDUE]);
            return true;
        }
        return false;
    }

    /**
     * Marks invoice as paid and records payment timestamp.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status'  => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    /**
     * Generates a unique invoice number for the given user.
     * Format: INV-{YEAR}-{ZERO_PADDED_SEQUENCE}
     * e.g. INV-2025-0042
     */
    public static function generateNumber(int $userId): string
    {
        $year = now()->year;
        $count = static::where('user_id', $userId)
            ->whereYear('created_at', $year)
            ->count();
        return sprintf('INV-%d-%04d', $year, $count + 1);
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_SENT
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return (int) $this->due_date->diffInDays(now());
    }
}
