<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('purchases', function (Blueprint $table) {
      $table->foreignId('confirmed_by')
        ->nullable()
        ->after('confirmed_at')
        ->constrained('users')
        ->restrictOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('purchases', function (Blueprint $table) {
      $table->dropConstrainedForeignId('confirmed_by');
    });
  }
};
