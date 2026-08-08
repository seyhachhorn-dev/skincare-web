<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->latest()
            ->get();

        return $this->respond(ProductResource::collection($products), 'Products retrieved successfully');
    }

    public function show(Product $product): JsonResponse
    {
        return $this->respond(new ProductResource($product));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::query()->create($request->validated());

        return $this->respond(new ProductResource($product), 'Product created', 201);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return $this->respond(new ProductResource($product->fresh()), 'Product updated');
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->respond(null, 'Product deleted');
    }
}
