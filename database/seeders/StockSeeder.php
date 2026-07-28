<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    private const INITIAL_QUANTITY = 0;

    private const INITIAL_AVERAGE_COST = 0;

    public function run(): void
    {
        Product::query()->eachById(function (Product $product) {
            Stock::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'quantity' => self::INITIAL_QUANTITY,
                    'average_cost' => self::INITIAL_AVERAGE_COST,
                ],
            );
        });
    }
}
