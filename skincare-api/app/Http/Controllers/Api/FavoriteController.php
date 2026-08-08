<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFavoriteRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = $request->user()->favorites()->with('product')->get()->pluck('product');

        return $this->respond(ProductResource::collection($products), 'Favorites retrieved successfully');
    }

    public function store(StoreFavoriteRequest $request): JsonResponse
    {
        $favorite = $request->user()->favorites()->firstOrCreate([
            'product_id' => $request->validated('product_id'),
        ]);

        return $this->respond(new ProductResource($favorite->product), 'Product saved', 201);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $request->user()->favorites()->where('product_id', $product->id)->delete();

        return $this->respond(null, 'Product removed from favorites');
    }
}
