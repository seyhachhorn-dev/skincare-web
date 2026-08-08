<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'province', 'district', 'commune', 'house_no', 'pickup_point', 'location', 'type', 'is_default'])]
class Address extends Model
{
    use HasFactory;

    // Same reasoning as User::$attributes — without this, a freshly
    // created address that omits is_default would serialize as null
    // instead of false.
    protected $attributes = [
        'is_default' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
