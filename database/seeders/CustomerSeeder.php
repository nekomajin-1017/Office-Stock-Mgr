<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
  public function run(): void
  {
    $customers = [
      ['CUS-001', '株式会社青葉商事', '160-0022', '東京都新宿区新宿1-10-1', '03-6111-1111', 'office@aoba.example.com', '山田 太郎'],
      ['CUS-002', 'みなとシステム株式会社', '231-0005', '神奈川県横浜市中区本町2-20-2', '045-622-2222', 'purchase@minato-system.example.com', '中村 裕子'],
      ['CUS-003', '北関東物流株式会社', '330-0063', '埼玉県さいたま市浦和区高砂3-30-3', '048-633-3333', 'admin@kitakanto-logistics.example.com', '小林 誠'],
      ['CUS-004', '千葉デザイン事務所', '260-0013', '千葉県千葉市中央区中央4-40-4', '043-644-4444', 'info@chiba-design.example.com', '加藤 恵'],
      ['CUS-005', '多摩コンサルティング株式会社', '190-0012', '東京都立川市曙町5-50-5', '042-655-5555', 'general@tama-consulting.example.com', '吉田 直樹'],
    ];

    foreach ($customers as [$code, $name, $postalCode, $address, $phone, $email, $contactPerson]) {
      Customer::updateOrCreate(
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
