<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
  public function authorize(): bool
  {
    $category = $this->route('category');

    return $category instanceof Category && ($this->user()?->can('update', $category) ?? false);
  }

  /**
   * @return array<string, array<int, mixed>>
   */
  public function rules(): array
  {
    /** @var Category $category */
    $category = $this->route('category');

    return [
      'name' => ['required', 'string', 'max:100', Rule::unique(Category::class)->ignore($category)],
      'is_active' => ['required', 'boolean'],
    ];
  }
}
