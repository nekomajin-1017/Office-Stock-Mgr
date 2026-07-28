<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categoryNames = [
            '筆記用具',
            'ノート・紙製品',
            'ファイル用品',
            'デスク用品',
            'PC周辺用品',
            '消耗品',
        ];

        foreach ($categoryNames as $categoryName) {
            Category::updateOrCreate(
                ['name' => $categoryName],
                ['is_active' => true],
            );
        }
    }
}
