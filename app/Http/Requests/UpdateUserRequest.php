<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
  public function authorize(): bool
  {
    $user = $this->route('user');

    return $user instanceof User && ($this->user()?->can('update', $user) ?? false);
  }

  /**
   * @return array<string, array<int, mixed>>
   */
  public function rules(): array
  {
    /** @var User $user */
    $user = $this->route('user');

    return [
      'name' => ['required', 'string', 'max:100'],
      'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
      'password' => ['nullable', 'string', Password::default(), 'confirmed'],
      'role' => ['required', Rule::in([User::ROLE_USER, User::ROLE_ADMIN])],
      'is_active' => ['required', 'boolean'],
    ];
  }

  public function withValidator(Validator $validator): void
  {
    $validator->after(function (Validator $validator): void {
      /** @var User $user */
      $user = $this->route('user');
      $becomesInactiveOrGeneralUser = ! $this->boolean('is_active')
        || $this->input('role') !== User::ROLE_ADMIN;

      if (! $becomesInactiveOrGeneralUser) {
        return;
      }

      if ($user->is($this->user())) {
        $validator->errors()->add('role', '自分自身を無効化または一般ユーザーへ変更することはできません。');
      }

      if ($user->isLastActiveAdmin()) {
        $validator->errors()->add('role', '最後の有効な管理者を無効化または一般ユーザーへ変更することはできません。');
      }
    });
  }
}
