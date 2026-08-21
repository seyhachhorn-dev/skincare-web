<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// order_number/total/points_earned are only ever set by OrderService's
// trusted server-side create() call — PlaceOrderRequest never accepts
// them as client input in the first place, so there's nothing to mass-
// assign from an untrusted request even though they're fillable here.
#[Fillable(['user_id', 'address_id', 'status', 'order_number', 'total', 'points_earned', 'payment_method', 'payment_status', 'khqr_md5', 'stripe_payment_intent_id', 'shipping_method'])]
class Order extends Model
{
    use HasFactory;

    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Statuses an administrator may set while fulfilling a paid order.
     * Initial and cancellation statuses are set only by the checkout and
     * payment flows, respectively.
     */
    public const FULFILLMENT_STATUSES = [
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    protected $appends = ['item_count'];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'points_earned' => 'integer',
        ];
    }

    protected function itemCount(): Attribute
    {
        return Attribute::get(fn () => $this->items->sum('quantity'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
