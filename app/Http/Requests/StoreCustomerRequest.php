<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ContactValidationRules;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    use ContactValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Customer::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->contactRules(Customer::class);
    }
}
