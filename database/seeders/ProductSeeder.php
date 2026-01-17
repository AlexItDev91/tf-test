<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Wireless Headphones', 'price_cents' => 9900, 'stock' => 50],
            ['name' => 'Smartphone Pro', 'price_cents' => 89900, 'stock' => 15],
            ['name' => 'Mechanical Keyboard', 'price_cents' => 12900, 'stock' => 30],
            ['name' => 'Gaming Mouse', 'price_cents' => 5900, 'stock' => 45],
            ['name' => '4K Monitor', 'price_cents' => 34900, 'stock' => 10],
            ['name' => 'USB-C Hub', 'price_cents' => 3900, 'stock' => 100],
            ['name' => 'Portable SSD 1TB', 'price_cents' => 11900, 'stock' => 25],
            ['name' => 'Laptop Stand', 'price_cents' => 2900, 'stock' => 60],
            ['name' => 'Bluetooth Speaker', 'price_cents' => 7900, 'stock' => 40],
            ['name' => 'Webcam 1080p', 'price_cents' => 6900, 'stock' => 20],
        ];

        foreach ($products as $productData) {
            Product::query()->updateOrCreate(
                ['name' => $productData['name']],
                $productData
            );
        }
    }
}
