<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
