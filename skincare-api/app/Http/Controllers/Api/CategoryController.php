<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->respond(
            CategoryResource::collection(Category::query()->orderBy('name')->get()),
            'Categories retrieved successfully'
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['icon'] = $request->hasFile('icon')
            ? $request->file('icon')->store('categories', 'public')
            : ($request->input('icon') ?? '');

        $category = Category::query()->create($data);

        return $this->respond(new CategoryResource($category), 'Category created', 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('icon')) {
            if ($category->icon) {
                Storage::disk('public')->delete($category->icon);
            }
            $data['icon'] = $request->file('icon')->store('categories', 'public');
        }

        $category->update($data);

        return $this->respond(new CategoryResource($category->fresh()), 'Category updated');
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->icon) {
            Storage::disk('public')->delete($category->icon);
        }
        $category->delete();

        return $this->respond(null, 'Category deleted');
    }
}
