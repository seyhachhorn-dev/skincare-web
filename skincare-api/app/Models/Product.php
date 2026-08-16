<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category_id', 'name', 'description', 'price', 'image', 'brand', 'size'])]
class Product extends Model
{
    use HasFactory;

    // Same reasoning as User::$attributes — without this, a freshly created
    // product that omits description/brand/size would serialize as null
    // instead of the DB's '' default (visible immediately in the create
    // response, before any later fresh() re-fetch corrects it).
    protected $attributes = [
        'description' => '',
        'brand' => '',
        'size' => '',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
