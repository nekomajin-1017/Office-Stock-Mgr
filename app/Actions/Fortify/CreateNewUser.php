<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
  use PasswordValidationRules;

  private const PUBLIC_USER_ROLE = 'user';

  /**
   * @param array<string, string> $input
   */
  public function create(array $input): User
  {
    Validator::make($input, [
      'name' => ['required', 'string', 'max:100'],
      'email' => [
        'required',
        'string',
        'email',
        'max:255',
        Rule::unique(User::class),
      ],
      'password' => $this->passwordRules(),
    ])->validate();

    return User::create([
      'name' => $input['name'],
      'email' => $input['email'],
      'password' => Hash::make($input['password']),
      'role' => self::PUBLIC_USER_ROLE,
      'is_active' => true,
    ]);
  }
}
