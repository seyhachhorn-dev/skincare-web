<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sort = $request->string('sort')->toString();

        $products = Product::query()
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when(
                $sort === 'trending',
                fn (Builder $query) => $query
                    ->withSum([
                        'orderItems as sales_count' => fn (Builder $itemQuery) => $itemQuery
                            ->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('payment_status', 'paid')),
                    ], 'quantity')
                    ->orderByDesc('sales_count')
                    ->latest(),
                fn (Builder $query) => $query->latest(),
            )
            ->get();

        return $this->respond(ProductResource::collection($products), 'Products retrieved successfully');
    }

    public function show(Product $product): JsonResponse
    {
        return $this->respond(new ProductResource($product));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::query()->create([
            ...$request->safe()->except('image'),
            'image' => $request->file('image')->store('products', 'public'),
        ]);

        return $this->respond(new ProductResource($product), 'Product created', 201);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return $this->respond(new ProductResource($product->fresh()), 'Product updated');
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return $this->respond(null, 'Product deleted');
    }
}
