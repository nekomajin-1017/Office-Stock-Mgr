<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
  public function authorize(): bool { return $this->user()?->can('create', Sale::class) ?? false; }

  public function rules(): array
  {
    return [
      'customer_id' => ['required', Rule::exists(Customer::class, 'id')->where('is_active', true)],
      'sale_date' => ['required', 'date'],
      'items' => ['required', 'array', 'min:1'],
      'items.*.product_id' => ['required', 'distinct', Rule::exists(Product::class, 'id')->where('is_active', true)],
      'items.*.quantity' => ['required', 'integer', 'min:1'],
      'items.*.unit_price' => ['required', 'numeric', 'min:0'],
    ];
  }
}
