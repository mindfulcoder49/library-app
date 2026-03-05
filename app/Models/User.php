<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $appends = [
        'display_name',
    ];

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'employee_id',
        'email',
        'office_location_id',
        'is_lender',
        'is_borrower',
        'agree_lender_guidelines',
        'agree_borrower_guidelines',
        'is_administrator',
        'is_site_owner',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_lender' => 'boolean',
            'is_borrower' => 'boolean',
            'agree_lender_guidelines' => 'boolean',
            'agree_borrower_guidelines' => 'boolean',
            'is_administrator' => 'boolean',
            'is_site_owner' => 'boolean',
        ];
    }

    public function officeLocation(): BelongsTo
    {
        return $this->belongsTo(OfficeLocation::class);
    }

    public function shareLocations(): BelongsToMany
    {
        return $this->belongsToMany(OfficeLocation::class, 'user_share_locations')->withTimestamps();
    }

    public function lentBookItems(): HasMany
    {
        return $this->hasMany(BookItem::class, 'lender_id');
    }

    public function lenderLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'lender_id');
    }

    public function borrowerLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'borrower_id');
    }

    public function waitingListEntries(): HasMany
    {
        return $this->hasMany(WaitingListEntry::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $name !== '' ? $name : $this->name;
    }
}
