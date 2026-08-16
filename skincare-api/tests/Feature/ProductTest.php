<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
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

    public function test_trending_products_are_sorted_by_paid_sales(): void
    {
        $mostSold = Product::factory()->create();
        $lessSold = Product::factory()->create();
        $unpaidSale = Product::factory()->create();

        $paidOrder = Order::factory()->create(['payment_status' => 'paid']);
        $unpaidOrder = Order::factory()->create(['payment_status' => 'pending']);

        OrderItem::query()->create([
            'order_id' => $paidOrder->id,
            'product_id' => $mostSold->id,
            'quantity' => 5,
            'unit_price' => $mostSold->price,
        ]);
        OrderItem::query()->create([
            'order_id' => $paidOrder->id,
            'product_id' => $lessSold->id,
            'quantity' => 2,
            'unit_price' => $lessSold->price,
        ]);
        OrderItem::query()->create([
            'order_id' => $unpaidOrder->id,
            'product_id' => $unpaidSale->id,
            'quantity' => 10,
            'unit_price' => $unpaidSale->price,
        ]);

        $response = $this->getJson('/api/products?sort=trending');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', (string) $mostSold->id)
            ->assertJsonPath('data.1.id', (string) $lessSold->id);
    }

    public function test_new_products_are_sorted_by_creation_date(): void
    {
        $oldest = Product::factory()->create();
        $oldest->forceFill(['created_at' => now()->subDay()])->saveQuietly();
        $newest = Product::factory()->create();

        $response = $this->getJson('/api/products?sort=new');

        $response->assertOk()->assertJsonPath('data.0.id', (string) $newest->id);
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
