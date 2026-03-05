<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'lender_id',
        'unique_key',
        'lender_comments',
        'status',
        'expected_return_date',
        'verified_at',
        'removed_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_return_date' => 'date',
            'verified_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function lender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lender_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
