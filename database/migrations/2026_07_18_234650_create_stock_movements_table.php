<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('stock_movements', function (Blueprint $table) {
      $table->id();
      $table->foreignId('product_id')->constrained()->restrictOnDelete();
      $table->string('movement_type', 20);
      $table->string('reference_type', 50);
      $table->unsignedBigInteger('reference_id');
      $table->integer('quantity_change');
      $table->decimal('unit_cost', 8, 2)->nullable();
      $table->timestamp('occurred_at');
      $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
      $table->timestamp('created_at')->nullable();

      $table->index(['reference_type', 'reference_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('stock_movements');
  }
};
