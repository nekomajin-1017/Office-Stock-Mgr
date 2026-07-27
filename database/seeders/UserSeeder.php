<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
  private const DEFAULT_PASSWORD = 'Coachtech777';

  public function run(): void
  {
    $users = [
      ['name' => '管理者', 'email' => 'admin@example.com', 'role' => 'admin'],
      ['name' => '一般ユーザー1', 'email' => 'staff1@example.com', 'role' => 'user'],
      ['name' => '一般ユーザー2', 'email' => 'staff2@example.com', 'role' => 'user'],
      ['name' => '一般ユーザー3', 'email' => 'staff3@example.com', 'role' => 'user'],
    ];

    foreach ($users as $user) {
      User::updateOrCreate(
        ['email' => $user['email']],
        [
          'name' => $user['name'],
          'password' => Hash::make(self::DEFAULT_PASSWORD),
          'role' => $user['role'],
          'is_active' => true,
        ],
      );
    }
  }
}
