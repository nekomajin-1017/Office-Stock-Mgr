<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('purchases', function (Blueprint $table) {
      $table->id();
      $table->string('purchase_number', 50)->unique();
      $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
      $table->date('purchase_date');
      $table->string('status', 20)->default('draft');
      $table->decimal('subtotal', 12, 2)->default(0);
      $table->decimal('tax_amount', 12, 2)->default(0);
      $table->decimal('total_amount', 12, 2)->default(0);
      $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
      $table->timestamp('confirmed_at')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('purchases');
  }
};
