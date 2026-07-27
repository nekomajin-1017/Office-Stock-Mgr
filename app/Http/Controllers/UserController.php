<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
  public function index(): View
  {
    $this->authorize('viewAny', User::class);

    return view('users.index', [
      'users' => User::query()->orderBy('id')->get(),
    ]);
  }

  public function create(): View
  {
    $this->authorize('create', User::class);

    return view('users.form', ['user' => new User()]);
  }

  public function store(StoreUserRequest $request): RedirectResponse
  {
    $this->authorize('create', User::class);

    User::create($request->validated());

    return to_route('users.index')->with('status', 'ユーザーを登録しました。');
  }

  public function edit(User $user): View
  {
    $this->authorize('update', $user);

    return view('users.form', compact('user'));
  }

  public function update(UpdateUserRequest $request, User $user): RedirectResponse
  {
    $this->authorize('update', $user);

    $attributes = $request->validated();

    if ($attributes['password'] === null) {
      unset($attributes['password']);
    }

    $user->update($attributes);

    return to_route('users.index')->with('status', 'ユーザー情報を更新しました。');
  }
}
