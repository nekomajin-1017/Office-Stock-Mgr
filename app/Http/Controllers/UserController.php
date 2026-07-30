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
        // 管理権限を確認し、ユーザーをID順で取得して一覧画面へ渡す。
        $this->authorize('viewAny', User::class);

        return view('users.index', [
            'users' => User::query()->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        // ユーザー登録権限を確認し、空のユーザーモデルを登録画面へ渡す。
        $this->authorize('create', User::class);

        return view('users.form', ['user' => new User]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        // 検証済み入力値からユーザーを登録し、一覧画面へ戻す。
        User::create($request->validated());

        return to_route('users.index')->with('status', 'ユーザーを登録しました。');
    }

    public function edit(User $user): View
    {
        // 対象ユーザーの更新権限を確認し、現在値を編集画面へ渡す。
        $this->authorize('update', $user);

        return view('users.form', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // 入力値を検証し、空のパスワードは除外して既存ユーザー情報を更新する。
        $attributes = $request->validated();

        if ($attributes['password'] === null) {
            unset($attributes['password']);
        }

        $user->update($attributes);

        return to_route('users.index')->with('status', 'ユーザー情報を更新しました。');
    }
}
