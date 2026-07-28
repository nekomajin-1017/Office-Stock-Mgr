<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['SUP-001', '東京文具株式会社', '100-0001', '東京都千代田区千代田1-1', '03-1111-1111', 'sales@tokyo-bungu.example.com', '佐藤 一郎'],
            ['SUP-002', '日本紙業株式会社', '104-0061', '東京都中央区銀座2-2-2', '03-2222-2222', 'order@nihon-shigyo.example.com', '鈴木 花子'],
            ['SUP-003', 'オフィスファイル株式会社', '220-0012', '神奈川県横浜市西区みなとみらい3-3-3', '045-333-3333', 'sales@office-file.example.com', '高橋 健'],
            ['SUP-004', 'デスクサプライ株式会社', '330-0854', '埼玉県さいたま市大宮区桜木町4-4-4', '048-444-4444', 'info@desk-supply.example.com', '田中 美咲'],
            ['SUP-005', 'テックアクセサリー株式会社', '261-0023', '千葉県千葉市美浜区中瀬5-5-5', '043-555-5555', 'business@tech-accessory.example.com', '伊藤 拓也'],
        ];

        foreach ($suppliers as [$code, $name, $postalCode, $address, $phone, $email, $contactPerson]) {
            Supplier::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'postal_code' => $postalCode,
                    'address' => $address,
                    'phone' => $phone,
                    'email' => $email,
                    'contact_person' => $contactPerson,
                    'is_active' => true,
                ],
            );
        }
    }
}
