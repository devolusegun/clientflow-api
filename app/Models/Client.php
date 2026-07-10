<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices(): HasMany
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

    /**
     * Search clients by name, email, or company.
     */
    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('company', 'like', "%{$term}%");
        });
    }

    /**
     * Apply sort with allowlist validation.
     */
    public function scopeSorted($query, string $by = 'created_at', string $dir = 'desc')
    {
        $allowed = ['name', 'email', 'company', 'created_at'];
        return $query->orderBy(
            in_array($by, $allowed) ? $by : 'created_at',
            $dir === 'asc' ? 'asc' : 'desc'
        );
    }
}
