<?php

namespace App\Http\Requests;

class UpdateSaleRequest extends StoreSaleRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('sale')) ?? false;
    }
}
