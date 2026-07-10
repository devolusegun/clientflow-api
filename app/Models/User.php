<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
//use Database\Factories\UserFactory;
//use Illuminate\Database\Eloquent\Attributes\Fillable;
//use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_name',
        'phone',
        'address',
        'currency',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    /**public function clients()
    {
        return $this->hasMany(Client::class);
    }**/
    public function clients(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }**/

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }
 
    // ── Accessors ─────────────────────────────────────────────────────────

    /**
     * Total revenue from all paid invoices.
     */
    public function getTotalRevenueAttribute(): float
    {
        return $this->invoices()
            ->where('status', Invoice::STATUS_PAID)
            ->sum('total_amount');
    }

    /**
     * Total outstanding (sent but unpaid) invoices.
     */
    public function getTotalOutstandingAttribute(): float
    {
        return $this->invoices()
            ->where('status', Invoice::STATUS_SENT)
            ->sum('total_amount');
    }
}
