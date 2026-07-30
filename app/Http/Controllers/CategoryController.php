<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function index(): View
    {
        // 一覧表示権限を確認し、カテゴリを名前順で取得して一覧画面へ渡す。
        $this->authorize('viewAny', Category::class);

        return view('categories.index', [
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        // 検証済み入力値からカテゴリを登録し、完了メッセージ付きで一覧へ戻す。
        Category::create($request->validated());

        return to_route('categories.index')->with('status', 'カテゴリを登録しました。');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        // 対象カテゴリを検証済み入力値で更新し、完了メッセージ付きで一覧へ戻す。
        $category->update($request->validated());

        return to_route('categories.index')->with('status', 'カテゴリを更新しました。');
    }
}
