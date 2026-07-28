<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $productsByCategory = [
            '筆記用具' => ['SUP-001', [
                ['PEN-001', '油性ボールペン（黒）', '本', 120, 20],
                ['PEN-002', '油性ボールペン（赤）', '本', 120, 15],
                ['PEN-003', 'シャープペンシル', '本', 350, 10],
                ['PEN-004', '蛍光マーカー 5色セット', 'セット', 600, 8],
                ['PEN-005', 'ホワイトボードマーカー', '本', 180, 12],
            ]],
            'ノート・紙製品' => ['SUP-002', [
                ['PAP-001', 'A4コピー用紙 500枚', '冊', 650, 10],
                ['PAP-002', 'A5方眼ノート', '冊', 300, 10],
                ['PAP-003', '大学ノート B5', '冊', 220, 15],
                ['PAP-004', '付箋 75×75mm', '個', 380, 12],
                ['PAP-005', '封筒 長形3号 100枚', '箱', 900, 5],
            ]],
            'ファイル用品' => ['SUP-003', [
                ['FIL-001', 'A4クリアファイル 10枚', 'セット', 450, 10],
                ['FIL-002', 'リングファイル A4', '冊', 550, 8],
                ['FIL-003', 'パイプ式ファイル A4', '冊', 850, 6],
                ['FIL-004', 'ドキュメントケース A4', '個', 700, 5],
                ['FIL-005', '名刺ホルダー 300枚', '冊', 1200, 4],
            ]],
            'デスク用品' => ['SUP-004', [
                ['DSK-001', '卓上ステープラー', '個', 900, 5],
                ['DSK-002', 'ステープラー針 1000本', '箱', 180, 10],
                ['DSK-003', 'はさみ', '本', 650, 5],
                ['DSK-004', 'テープカッター', '個', 780, 5],
                ['DSK-005', 'デスクトレー A4', '個', 1100, 4],
            ]],
            'PC周辺用品' => ['SUP-005', [
                ['PCP-001', 'USBキーボード', '台', 2800, 3],
                ['PCP-002', 'ワイヤレスマウス', '個', 2200, 5],
                ['PCP-003', 'USB Type-Cケーブル 1m', '本', 1200, 8],
                ['PCP-004', 'USBメモリ 32GB', '個', 1500, 5],
                ['PCP-005', 'HDMIケーブル 2m', '本', 1800, 5],
            ]],
            '消耗品' => ['SUP-001', [
                ['CON-001', '黒トナーカートリッジ', '本', 9800, 2],
                ['CON-002', 'カラーインク 4色セット', 'セット', 5200, 2],
                ['CON-003', '単3アルカリ乾電池 10本', 'パック', 800, 5],
                ['CON-004', '布粘着テープ', '巻', 480, 8],
                ['CON-005', 'ラベルシール A4 20枚', '冊', 1300, 5],
            ]],
        ];

        foreach ($productsByCategory as $categoryName => [$supplierCode, $products]) {
            $categoryId = Category::where('name', $categoryName)->value('id');
            $supplierId = Supplier::where('code', $supplierCode)->value('id');

            foreach ($products as [$code, $name, $unit, $price, $reorderLevel]) {
                Product::updateOrCreate(
                    ['code' => $code],
                    [
                        'category_id' => $categoryId,
                        'supplier_id' => $supplierId,
                        'name' => $name,
                        'unit' => $unit,
                        'standard_sale_price' => $price,
                        'reorder_level' => $reorderLevel,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
