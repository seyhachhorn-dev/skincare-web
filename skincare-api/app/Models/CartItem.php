<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'product_id', 'quantity'])]
class CartItem extends Model
{
    use HasFactory;

    protected $appends = ['total'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    protected function total(): Attribute
    {
        return Attribute::get(fn () => $this->quantity * $this->product->price);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
