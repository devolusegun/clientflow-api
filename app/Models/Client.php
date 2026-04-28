<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'company',
        'address',
        'city',
        'country',
        'notes',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    /**
     * Total invoiced to this client (all statuses).
     */
    public function getTotalBilledAttribute(): float
    {
        return $this->invoices()->sum('total_amount');
    }

    /**
     * Total paid by this client.
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->invoices()
            ->where('status', Invoice::STATUS_PAID)
            ->sum('total_amount');
    }

    /**
     * Number of invoices per status for this client.
     */
    public function getInvoiceSummaryAttribute(): array
    {
        return $this->invoices()
            ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->toArray();
    }
}
