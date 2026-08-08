<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 'role' isn't mass-assignable (by design, see User::$fillable's
        // comment) — direct property assignment is the correct way for
        // trusted server-side code like this seeder to set it.
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->role = 'admin';
        $admin->save();

        // Matches the category chips already drawn in explore_screen.dart.
        $categories = [
            'Toner' => '💧',
            'Serum' => '🌸',
            'Face Oil' => '🫗',
            'Cleanser' => '🧼',
            'Suncare' => '☀️',
            'Makeup' => '💄',
        ];

        foreach ($categories as $name => $icon) {
            Category::query()->create(['name' => $name, 'icon' => $icon]);
        }

        $serum = Category::query()->where('name', 'Serum')->first();

        // Matches the mock products already in home_screen.dart / explore_screen.dart,
        // so Stage B integration swaps in real IDs without changing the visible catalog.
        $products = [
            ['name' => 'Granactive Retinoid 5%', 'description' => 'This water-free solution contains a 5% concentration of retinoid.', 'price' => 699, 'image' => 'assets/images/pro1.png'],
            ['name' => 'Niacinamide 10% + Zinc', 'description' => 'A high-strength vitamin and mineral blemish formula.', 'price' => 549, 'image' => 'assets/images/pro2.png'],
            ['name' => 'Hyaluronic Acid 2% + B5', 'description' => 'A hydration support formula with ultra-pure hyaluronic acid.', 'price' => 629, 'image' => 'assets/images/pro3.png'],
            ['name' => 'Buffet + Copper Peptides', 'description' => 'Multi-technology peptide serum for visible signs of aging.', 'price' => 899, 'image' => 'assets/images/pro4.jpg'],
            ['name' => 'Ascorbyl Glucoside 12%', 'description' => 'A stable vitamin C solution to brighten and even skin tone.', 'price' => 749, 'image' => 'assets/images/pro2.png'],
        ];

        foreach ($products as $product) {
            Product::query()->create([
                ...$product,
                'category_id' => $serum?->id,
                'brand' => 'The Ordinary',
                'size' => '30 ml',
            ]);
        }
    }
}
