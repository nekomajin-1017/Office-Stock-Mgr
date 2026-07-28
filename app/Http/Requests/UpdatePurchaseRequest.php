<?php

namespace App\Http\Requests;

use App\Models\Purchase;

class UpdatePurchaseRequest extends StorePurchaseRequest
{
    public function authorize(): bool
    {
        $purchase = $this->route('purchase');

        return $purchase instanceof Purchase && $purchase->isDraft() && ($this->user()?->can('update', $purchase) ?? false);
    }
}
