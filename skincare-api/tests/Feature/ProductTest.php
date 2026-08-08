<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_are_publicly_listable(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_products_can_be_searched_by_name(): void
    {
        Product::factory()->create(['name' => 'Granactive Retinoid 5%']);
        Product::factory()->create(['name' => 'Niacinamide 10%']);

        $response = $this->getJson('/api/products?search=Retinoid');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Granactive Retinoid 5%');
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->create();

        $response = $this->getJson("/api/products?category_id={$category->id}");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_single_product_can_be_retrieved(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertOk()->assertJsonPath('data.id', (string) $product->id);
    }

    public function test_categories_are_publicly_listable(): void
    {
        Category::factory()->count(2)->create();

        $response = $this->getJson('/api/categories');

        $response->assertOk()->assertJsonCount(2, 'data');
    }
}
