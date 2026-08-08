<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'total' => $this->total,
            'points_earned' => $this->points_earned,
            'payment_method' => $this->payment_method,
            'shipping_method' => $this->shipping_method,
            'item_count' => $this->item_count,
            'date' => $this->created_at->toIso8601String(),
            'address' => new AddressResource($this->whenLoaded('address')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
