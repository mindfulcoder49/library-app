<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    use HasFactory;

    protected $appends = [
        'display_name',
    ];

    protected $fillable = [
        'first_name',
        'last_name',
    ];

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class)->withTimestamps();
    }

    public function getDisplayNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.$this->last_name);
    }
}
