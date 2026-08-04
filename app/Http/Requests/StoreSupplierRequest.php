<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ContactValidationRules;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    use ContactValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Supplier::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->contactRules(Supplier::class);
    }
}
