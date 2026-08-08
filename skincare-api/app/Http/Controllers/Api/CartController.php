<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->cartItems()->with('product')->get();

        return $this->respond([
            'items' => CartItemResource::collection($items),
            'subtotal' => $items->sum('total'),
            'points_earned' => (int) round($items->sum('total') * 0.1),
        ], 'Cart retrieved successfully');
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $quantity = $request->validated('quantity') ?? 1;

        $cartItem = $request->user()->cartItems()->where('product_id', $request->validated('product_id'))->first();

        if ($cartItem) {
            $cartItem->increment('quantity', $quantity);
        } else {
            $cartItem = $request->user()->cartItems()->create([
                'product_id' => $request->validated('product_id'),
                'quantity' => $quantity,
            ]);
        }

        return $this->respond(new CartItemResource($cartItem->load('product')), 'Item added to cart', 201);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $this->authorize('update', $cartItem);

        $cartItem->update(['quantity' => $request->validated('quantity')]);

        return $this->respond(new CartItemResource($cartItem->load('product')), 'Cart item updated');
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->authorize('delete', $cartItem);

        $cartItem->delete();

        return $this->respond(null, 'Item removed from cart');
    }
}
