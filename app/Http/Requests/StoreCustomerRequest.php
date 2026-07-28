<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()?->can('create', Customer::class) ?? false;
  }

  /**
   * @return array<string, array<int, mixed>>
   */
  public function rules(): array
  {
    return [
      'code' => ['required', 'string', 'max:50', Rule::unique(Customer::class)],
      'name' => ['required', 'string', 'max:150'],
      'postal_code' => ['nullable', 'string', 'max:20'],
      'address' => ['nullable', 'string', 'max:255'],
      'phone' => ['nullable', 'string', 'max:30'],
      'email' => ['nullable', 'email', 'max:255'],
      'contact_person' => ['nullable', 'string', 'max:100'],
      'is_active' => ['required', 'boolean'],
    ];
  }
}
