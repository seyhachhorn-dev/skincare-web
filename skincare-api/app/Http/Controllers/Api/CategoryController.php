<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

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
        $category = Category::query()->create($request->validated());

        return $this->respond(new CategoryResource($category), 'Category created', 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return $this->respond(new CategoryResource($category->fresh()), 'Category updated');
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return $this->respond(null, 'Category deleted');
    }
}
